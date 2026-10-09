<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-extrabold text-[#0f2d52]">{{ $inventory->item_name }}</h2>
                <p class="text-sm text-slate-500">
                    {{ \App\Models\Supplier::CATEGORIES[$inventory->category] ?? $inventory->category }} ·
                    Supplier: {{ $inventory->supplier?->name ?? 'N/A' }}
                </p>
            </div>
            @if($inventory->isLowStock())
                <span class="px-3 py-1 rounded-full bg-orange-100 text-orange-700 font-bold text-sm">⚠ Low Stock</span>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg">{{ session('success') }}</div>
            @endif

            <!-- Stock Summary -->
            <div class="bg-white rounded-2xl shadow p-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-slate-50 rounded-xl p-4">
                    <p class="text-xs text-slate-500 mb-1">On Hand</p>
                    <p class="text-2xl font-extrabold text-[#0f2d52]">{{ $inventory->quantity_on_hand }}</p>
                    <p class="text-xs text-slate-400">{{ $inventory->unit }}</p>
                </div>
                <div class="bg-red-50 rounded-xl p-4">
                    <p class="text-xs text-slate-500 mb-1">Damaged</p>
                    <p class="text-2xl font-extrabold text-red-600">{{ $inventory->quantity_damaged }}</p>
                    <p class="text-xs text-slate-400">{{ $inventory->unit }}</p>
                </div>
                <div class="bg-green-50 rounded-xl p-4">
                    <p class="text-xs text-slate-500 mb-1">Usable</p>
                    <p class="text-2xl font-extrabold text-green-700">{{ $inventory->usableQuantity() }}</p>
                    <p class="text-xs text-slate-400">{{ $inventory->unit }}</p>
                </div>
                <div class="bg-orange-50 rounded-xl p-4">
                    <p class="text-xs text-slate-500 mb-1">Min. Stock</p>
                    <p class="text-2xl font-extrabold text-orange-600">{{ $inventory->minimum_stock }}</p>
                    <p class="text-xs text-slate-400">{{ $inventory->unit }}</p>
                </div>
            </div>

            <!-- Adjust Inventory (Admin only) -->
            @can('admin')
                <div class="bg-white rounded-2xl shadow p-6">
                    <h3 class="font-bold text-[#0f2d52] mb-4">Adjust Inventory</h3>
                    <form action="{{ route('inventory.adjust', $inventory) }}" method="POST"
                          class="flex flex-wrap items-end gap-4">
                        @csrf @method('PATCH')
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Type *</label>
                            <select name="type" required
                                    class="rounded-lg border-gray-300 text-sm focus:border-[#f0ad1f] focus:ring-[#f0ad1f]">
                                <option value="used">Used / Consumed</option>
                                <option value="damaged">Report Damaged</option>
                                <option value="adjustment">Manual Adjustment (+)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Quantity *</label>
                            <input type="number" name="quantity" required min="0.01" step="0.01"
                                   class="rounded-lg border-gray-300 text-sm focus:border-[#f0ad1f] focus:ring-[#f0ad1f] w-32"
                                   placeholder="0">
                        </div>
                        <div class="flex-1 min-w-48">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Notes</label>
                            <input type="text" name="notes"
                                   class="w-full rounded-lg border-gray-300 text-sm focus:border-[#f0ad1f] focus:ring-[#f0ad1f]"
                                   placeholder="Reason for adjustment...">
                        </div>
                        <button type="submit"
                                class="px-5 py-2 bg-[#0f2d52] text-white font-bold rounded-lg hover:opacity-90 text-sm">
                            Apply
                        </button>
                    </form>
                </div>
            @endif

            <!-- Movement Log -->
            <div class="bg-white rounded-2xl shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="font-bold text-[#0f2d52]">Movement History</h3>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-slate-600 text-xs uppercase tracking-wide">
                            <th class="px-5 py-3">Date</th>
                            <th class="px-5 py-3">Type</th>
                            <th class="px-5 py-3">Quantity</th>
                            <th class="px-5 py-3">Reference</th>
                            <th class="px-5 py-3">Notes</th>
                            <th class="px-5 py-3">By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($inventory->logs->sortByDesc('created_at') as $log)
                            @php $typeInfo = \App\Models\InventoryLog::TYPES[$log->type] ?? ['label'=>$log->type,'color'=>'bg-gray-100 text-gray-700']; @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3 text-slate-500">{{ $log->created_at->format('M d, Y h:i A') }}</td>
                                <td class="px-5 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-bold {{ $typeInfo['color'] }}">
                                        {{ $typeInfo['label'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 font-bold
                                    {{ in_array($log->type, ['used','damaged']) ? 'text-red-600' : 'text-green-700' }}">
                                    {{ in_array($log->type, ['used','damaged']) ? '-' : '+' }}{{ $log->quantity }} {{ $inventory->unit }}
                                </td>
                                <td class="px-5 py-3 font-mono text-slate-500 text-xs">{{ $log->ref_note ?? '—' }}</td>
                                <td class="px-5 py-3 text-slate-500">{{ $log->notes ?? '—' }}</td>
                                <td class="px-5 py-3 text-slate-500">{{ $log->user?->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-slate-400">No movement recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <a href="{{ route('inventory.index') }}" class="inline-block text-[#0f2d52] font-semibold hover:underline text-sm">
                ← Back to Inventory
            </a>

        </div>
    </div>
</x-app-layout>