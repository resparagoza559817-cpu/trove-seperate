<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('materials')->orderBy('created_at', 'desc')->get();
        return view('products.index', compact('products'));
    }

    public const DEFAULT_CATEGORIES = ['Cake', 'Pastry', 'Coffee'];

    /** Defaults plus any category already used by a product. */
    private function categoryOptions(): array
    {
        $used = Product::whereNotNull('category')->where('category', '!=', '')
            ->distinct()->pluck('category')->all();

        return collect(self::DEFAULT_CATEGORIES)->merge($used)->unique()->values()->all();
    }

    public function create()
    {
        $inventoryItems = Inventory::whereNull('archived_at')->orderBy('item_name')->get();
        $categories = $this->categoryOptions();
        return view('products.create', compact('inventoryItems', 'categories'));
    }

    public function store(Request $request)
    {
        // A "new category" typed by the user replaces the dropdown value.
        if ($request->input('category') === '__new__') {
            $request->merge(['category' => trim((string) $request->input('new_category'))]);
        }

        $validated = $request->validate([
            'product_name'     => 'required|string|max:255|unique:products',
            'description'      => 'nullable|string|max:1000',
            'category'         => 'required|string|max:50',
            'price'            => 'required|numeric|min:0',
            'stock_quantity'   => 'nullable|integer|min:0',
            'image'            => 'nullable|image|max:2048',
            // Every product MUST have a recipe (at least one ingredient).
            'recipe'                   => 'required|array|min:1',
            'recipe.*.inventory_id'    => 'required|distinct|exists:inventory,id',
            'recipe.*.quantity_needed' => 'required|numeric|min:0.01',
        ], [
            'recipe.required'                   => 'Add at least one ingredient - every product needs a recipe.',
            'recipe.min'                        => 'Add at least one ingredient - every product needs a recipe.',
            'recipe.*.inventory_id.required'    => 'Pick an inventory item for every ingredient row.',
            'recipe.*.inventory_id.distinct'    => 'The same ingredient is listed more than once.',
            'recipe.*.quantity_needed.required' => 'Enter the quantity needed for every ingredient row.',
        ]);

        $startingStock = (int) ($validated['stock_quantity'] ?? 0);

        try {
            DB::beginTransaction();

            // Lock the ingredient rows so two requests can't spend the same flour.
            $inventory = Inventory::whereIn('id', collect($validated['recipe'])->pluck('inventory_id'))
                ->lockForUpdate()->get()->keyBy('id');

            // Max units the current raw materials can make.
            $maxMakeable = Product::maxProducibleFrom(collect($validated['recipe'])->map(fn ($r) => [
                'available' => $inventory[$r['inventory_id']]->quantity_on_hand,
                'needed'    => $r['quantity_needed'],
            ])->all());

            if ($startingStock > $maxMakeable) {
                throw new \Exception("Starting stock of {$startingStock} is more than your raw materials can make. You can make at most {$maxMakeable}.");
            }

            $imagePath = $request->hasFile('image')
                ? $request->file('image')->store('products', 'public')
                : null;

            // Every product starts at the Matina commissary; branch transfers move it later.
            $product = Product::create([
                'product_name'   => $validated['product_name'],
                'description'    => $validated['description'] ?? null,
                'category'       => $validated['category'],
                'price'          => $validated['price'],
                'stock_quantity' => $startingStock,
                'site_id'        => Site::matina()?->id,
                'status'         => 'active',
                'image_path'     => $imagePath,
            ]);

            $materials = [];
            foreach ($validated['recipe'] as $row) {
                $materials[$row['inventory_id']] = ['quantity_needed' => $row['quantity_needed']];
            }
            $product->materials()->attach($materials);

            // Starting finished stock = units already baked, so use up their ingredients.
            if ($startingStock > 0) {
                foreach ($validated['recipe'] as $row) {
                    $item = $inventory[$row['inventory_id']];
                    $used = round($row['quantity_needed'] * $startingStock, 2);

                    $item->decrement('quantity_on_hand', $used);

                    InventoryLog::create([
                        'inventory_id' => $item->id,
                        'type'         => 'used',
                        'quantity'     => $used,
                        'ref_note'     => 'PRODUCT-CREATE',
                        'notes'        => "Used for {$startingStock} x {$product->product_name} (starting stock)",
                        'user_id'      => auth()->id(),
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('products.index')
                ->with('success', "Product '{$product->product_name}' created."
                    . ($startingStock > 0 ? ' Raw materials for the starting stock were deducted from inventory.' : ''));
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Product creation failed: ' . $e->getMessage());
            return back()->withInput()
                ->with('error', 'Failed to create product: ' . $e->getMessage());
        }
    }

    public function show(Product $product)
    {
        $product->load('materials');
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $product->load('materials');
        $categories = $this->categoryOptions();
        return view('products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        try {
            $validated = $request->validate([
                'product_name'  => 'required|string|max:255|unique:products,product_name,' . $product->id,
                'description'   => 'nullable|string|max:1000',
                'category'      => 'required|string|max:50',
                'price'         => 'required|numeric|min:0',
                'status'        => 'nullable|in:active,inactive',
                'image'         => 'nullable|image|max:2048',
            ]);

            if ($request->hasFile('image')) {
                if ($product->image_path && Storage::disk('public')->exists($product->image_path)) {
                    Storage::disk('public')->delete($product->image_path);
                }
                $validated['image_path'] = $request->file('image')->store('products', 'public');
            }

            unset($validated['image']);
            $product->update($validated);

            return redirect()->route('products.index')
                ->with('success', 'Product updated successfully.');
        } catch (\Exception $e) {
            \Log::error('Product update failed: ' . $e->getMessage());
            return back()->withInput()
                ->with('error', 'Failed to update product. Please try again.');
        }
    }

    public function destroy(Product $product)
    {
        try {
            if ($product->image_path && Storage::disk('public')->exists($product->image_path)) {
                Storage::disk('public')->delete($product->image_path);
            }
            $product->delete();
            return redirect()->route('products.index')
                ->with('success', 'Product deleted successfully.');
        } catch (\Exception $e) {
            \Log::error('Product deletion failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete product. Please try again.');
        }
    }
}