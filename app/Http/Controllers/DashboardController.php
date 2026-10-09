<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Product;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        //  This week's batches (Mon-Sun) 
        $weekStart = Carbon::now()->startOfWeek()->toDateString();
        $weekEnd   = Carbon::now()->endOfWeek()->toDateString();

        $weekBatches = Batch::with('items')
            ->whereBetween('batch_date', [$weekStart, $weekEnd])
            ->get();

        $batchSent      = $weekBatches->sum(fn ($b) => $b->totalSent());
        $returnedQty    = $weekBatches->sum(fn ($b) => $b->totalReturned());
        $netSoldQty     = $weekBatches->sum(fn ($b) => $b->totalNetSold());
        $netSoldRevenue = $weekBatches->sum(fn ($b) => $b->totalRevenue());

        //  Inventory (raw materials) 
        $inventories = Inventory::orderBy('item_name')->get();
        $lowCount    = $inventories->filter(fn ($i) => $i->isLowStock())->count();

        // lowest-stock first, show up to 6
        $stockLevels = $inventories
            ->sortBy(fn ($i) => $i->minimum_stock > 0 ? $i->usableQuantity() / $i->minimum_stock : 999)
            ->take(6)
            ->values();

        //  Finished products 
        $finishedStock = (int) Product::sum('stock_quantity');
        $totalProducts = Product::count();

        //  Latest batch (for reconciliation card) 
        $latestBatch = Batch::with(['items.product', 'sourceSite', 'destinationSite'])
            ->orderBy('batch_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        //  Can Still Bake (limiting-ingredient math from recipes) 
        $canBake = [];
        foreach (Product::with('materials')->where('status', 'active')->get() as $product) {
            if ($product->materials->isEmpty()) {
                continue;
            }
            $caps = [];
            foreach ($product->materials as $mat) {
                $need = (float) $mat->pivot->quantity_needed;
                if ($need > 0) {
                    $caps[] = (int) floor($mat->quantity_on_hand / $need);
                }
            }
            if (! empty($caps)) {
                $canBake[$product->product_name] = min($caps);
            }
        }
        arsort($canBake);
        $canBake = array_slice($canBake, 0, 5, true);
        $maxBake = ! empty($canBake) ? max(array_values($canBake)) : 0;

        //  Recent stock movements (ingredient audit trail) 
        $movements = InventoryLog::with('inventory')
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        return view('dashboard', compact(
            'batchSent', 'returnedQty', 'netSoldQty', 'netSoldRevenue',
            'lowCount', 'stockLevels', 'finishedStock', 'totalProducts',
            'latestBatch', 'canBake', 'maxBake', 'movements'
        ));
    }
}