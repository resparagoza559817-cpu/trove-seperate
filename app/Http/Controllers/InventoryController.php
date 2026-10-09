<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Site;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $view = $request->get('view') === 'archived' ? 'archived' : 'active';

        $base = Inventory::query();
        if ($request->search) {
            $base->where('item_name', 'like', '%' . $request->search . '%');
        }

        $activeCount   = (clone $base)->whereNull('archived_at')->count();
        $archivedCount = (clone $base)->whereNotNull('archived_at')->count();

        $q = clone $base;
        if ($view === 'archived') { $q->whereNotNull('archived_at'); }
        else { $q->whereNull('archived_at'); }

        $inventories = $q->latest()->get();
        $lowStock = $inventories->filter(fn($i) => $i->isLowStock())->count();
        $damaged  = $inventories->sum('quantity_damaged');

        return view('inventory.index', compact('inventories', 'lowStock', 'damaged', 'view', 'activeCount', 'archivedCount'));
    }

    public function create()
    {
        $categories = ['Baking Essentials', 'Dairy & Eggs', 'Flavoring & Fillings', 'Packaging', 'Coffee & Beverage', 'Other'];
        $units = ['g', 'kg', 'ml', 'liters', 'pcs', 'dozen', 'pack'];
        return view('inventory.create', compact('categories', 'units'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_name'        => 'required|string|max:255',
            'category'         => 'required|string|max:255',
            'unit'             => 'required|string|max:50',
            'quantity_on_hand' => 'required|numeric|min:0',
            'quantity_damaged' => 'nullable|numeric|min:0',
            'minimum_stock'    => 'required|numeric|min:0',
            'notes'            => 'nullable|string',
        ]);

        $inventory = Inventory::create([
            'item_name'        => $validated['item_name'],
            'category'         => $validated['category'],
            'unit'             => $validated['unit'],
            'quantity_on_hand' => $validated['quantity_on_hand'],
            'quantity_damaged' => $validated['quantity_damaged'] ?? 0,
            'minimum_stock'    => $validated['minimum_stock'],
            'site_id'          => Site::matina()?->id, // everything starts at Matina; branch transfers move it
            'notes'            => $validated['notes'] ?? null,
        ]);

        InventoryLog::create([
            'inventory_id' => $inventory->id,
            'type'         => 'adjustment',
            'quantity'     => $inventory->quantity_on_hand,
            'ref_note'     => 'INITIAL-STOCK',
            'notes'        => 'Initial inventory record created',
            'user_id'      => auth()->id(),
        ]);

        return redirect()->route('inventory.index')->with('success', 'Inventory item added successfully.');
    }

    public function show(Inventory $inventory)
    {
        $inventory->load(['site', 'logs.user']);
        return view('inventory.show', compact('inventory'));
    }

    public function adjust(Request $request, Inventory $inventory)
    {
        $validated = $request->validate([
            'type'     => 'required|in:used,damaged,adjustment',
            'quantity' => 'required|numeric|min:0.01',
            'notes'    => 'nullable|string',
        ]);

        if ($validated['type'] === 'used') {
            $inventory->decrement('quantity_on_hand', $validated['quantity']);
        } elseif ($validated['type'] === 'damaged') {
            $inventory->decrement('quantity_on_hand', $validated['quantity']);
            $inventory->increment('quantity_damaged', $validated['quantity']);
        } else {
            $inventory->increment('quantity_on_hand', $validated['quantity']);
        }

        InventoryLog::create([
            'inventory_id' => $inventory->id,
            'type'         => $validated['type'],
            'quantity'     => $validated['quantity'],
            'notes'        => $validated['notes'] ?? null,
            'user_id'      => auth()->id(),
        ]);

        return back()->with('success', 'Inventory adjusted successfully.');
    }

    public function edit(Inventory $inventory)
    {
        $categories = ['Baking Essentials', 'Dairy & Eggs', 'Flavoring & Fillings', 'Packaging', 'Coffee & Beverage', 'Other'];
        $units = ['g', 'kg', 'ml', 'liters', 'pcs', 'dozen', 'pack'];
        return view('inventory.edit', compact('inventory', 'categories', 'units'));
    }

    public function update(Request $request, Inventory $inventory)
    {
        $validated = $request->validate([
            'item_name'     => 'required|string|max:255',
            'category'      => 'required|string|max:255',
            'unit'          => 'required|string|max:50',
            'minimum_stock' => 'required|numeric|min:0',
        ]);

        $inventory->update($validated);

        return redirect()->route('inventory.index')->with('success', 'Item updated successfully.');
    }

    public function archive(Inventory $inventory)
    {
        $inventory->archived_at = now();
        $inventory->save();
        return redirect()->route('inventory.index')->with('success', $inventory->item_name . ' archived.');
    }

    public function restore(Inventory $inventory)
    {
        $inventory->archived_at = null;
        $inventory->save();
        return redirect()->route('inventory.index', ['view' => 'archived'])->with('success', $inventory->item_name . ' restored.');
    }

    public function destroy(Inventory $inventory)
    {
        if (auth()->user()->role !== 'Owner') {
            return back()->with('error', 'Only the Owner can permanently delete records.');
        }
        $inventory->delete();
        return redirect()->route('inventory.index', ['view' => 'archived'])->with('success', 'Item permanently deleted.');
    }
}