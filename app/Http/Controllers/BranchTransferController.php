<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Product;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchTransferController extends Controller
{
    public function index()
    {
        $batches = Batch::with(['items', 'sourceSite', 'destinationSite'])
            ->orderBy('batch_date', 'desc')
            ->get();

        return view('branch-transfers.index', compact('batches'));
    }

    public function create()
    {
        $sites = Site::orderBy('site_name')->get();
        $products = Product::where('status', 'active')->orderBy('product_name')->get();

        return view('branch-transfers.create', compact('sites', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'source_site_id'      => 'nullable|exists:sites,id',
            'destination_site_id' => 'required|exists:sites,id|different:source_site_id',
            'batch_date'          => 'required|date',
            'items'               => 'required|array|min:1',
            'items.*.product_id'  => 'required|exists:products,id',
            'items.*.qty_sent'    => 'required|integer|min:1',
            'items.*.unit_price'  => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // 1) Check stock FIRST so we never dispatch more than is on hand
            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                if ($item['qty_sent'] > $product->stock_quantity) {
                    throw new \Exception(
                        "Not enough {$product->product_name}: only {$product->stock_quantity} on hand, but you tried to dispatch {$item['qty_sent']}."
                    );
                }
            }

            // 2) Create the batch and its items, deducting finished stock
            $batch = Batch::create([
                'source_site_id'      => $validated['source_site_id'] ?? null,
                'destination_site_id' => $validated['destination_site_id'] ?? null,
                'batch_date'          => $validated['batch_date'],
                'status'              => 'sent',
                'created_by'          => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                BatchItem::create([
                    'batch_id'     => $batch->id,
                    'product_id'   => $item['product_id'],
                    'qty_sent'     => $item['qty_sent'],
                    'qty_returned' => 0,
                    'unit_price'   => $item['unit_price'],
                ]);

                // finished goods leave Matina
                Product::where('id', $item['product_id'])->decrement('stock_quantity', $item['qty_sent']);
            }

            DB::commit();

            return redirect()->route('branch-transfers.show', $batch)
                ->with('success', 'Batch dispatched and stock updated. Log returns here once Jacinto reports unsold items.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Batch dispatch failed: ' . $e->getMessage());
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(Batch $batch)
    {
        $batch->load(['items.product', 'sourceSite', 'destinationSite', 'creator']);
        return view('branch-transfers.show', compact('batch'));
    }

    public function logReturns(Request $request, Batch $batch)
    {
        $validated = $request->validate([
            'returns'   => 'required|array',
            'returns.*' => 'nullable|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            foreach ($batch->items as $item) {
                $newReturned = (int) ($validated['returns'][$item->id] ?? 0);
                $newReturned = min($newReturned, $item->qty_sent); // can't return more than sent

                // how much MORE is coming back vs. what was already logged
                $delta = $newReturned - $item->qty_returned;

                $item->update(['qty_returned' => $newReturned]);

                // returned items come back to Matina's finished stock
                if ($delta !== 0 && $item->product_id) {
                    Product::where('id', $item->product_id)->increment('stock_quantity', $delta);
                }
            }

            $batch->update(['status' => 'reconciled']);

            DB::commit();

            return redirect()->route('branch-transfers.show', $batch)
                ->with('success', 'Returns logged. Net Sold recalculated and returned stock restored to Matina.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Logging returns failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to log returns: ' . $e->getMessage());
        }
    }
}