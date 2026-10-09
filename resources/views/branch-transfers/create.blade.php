<x-app-layout>
<x-slot name="header">New Batch Dispatch</x-slot>
<x-slot name="subheader">Record products sent from Matina to Jacinto</x-slot>

<style>
.card{background:#fff;border-radius:14px;box-shadow:0 1px 6px rgba(0,0,0,.07);padding:24px;margin-bottom:20px;}
.card-title{font-size:15px;font-weight:800;color:#2E1C10;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #EDE0D0;}
.grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;}
label{display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:6px;}
input,select{width:100%;padding:10px 13px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;background:#f8fafc;}
table{width:100%;border-collapse:collapse;font-size:13px;}
th{text-align:left;font-size:11px;text-transform:uppercase;color:#8A7460;background:#FDF6EC;padding:9px 12px;}
td{padding:8px 12px;border-bottom:1px solid #f3f4f6;vertical-align:top;}
.btn{padding:10px 20px;border-radius:9px;font-size:13px;font-weight:700;border:none;cursor:pointer;text-decoration:none;}
.btn-gold{background:#D9782C;color:#fff;}
.btn-outline{background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;}
.addrow{padding:8px 14px;border:1.5px dashed #EDE0D0;border-radius:9px;background:#FDF6EC;color:#8A7460;font-size:13px;font-weight:600;cursor:pointer;}
.alert-err{background:#FBE4DA;border:1px solid #fecaca;color:#991b1b;padding:12px 16px;border-radius:9px;margin-bottom:16px;font-size:13px;}
.rm{background:none;border:none;color:#ef4444;cursor:pointer;font-size:15px;}
.avail{font-size:11px;margin-top:5px;font-weight:600;}
.avail.ok{color:#166534;} .avail.low{color:#C2410C;}
.hint{font-size:12px;color:#8A7460;margin-bottom:14px;}
</style>

@php
    $productData = $products->map(function ($p) {
        return [
            'id'    => $p->id,
            'name'  => $p->product_name,
            'price' => $p->price,
            'stock' => (int) $p->stock_quantity,
        ];
    });
@endphp

<div style="max-width:920px;margin:0 auto;">
@if($errors->any())
<div class="alert-err"><strong>Please fix:</strong>
<ul style="margin:6px 0 0 18px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif
@if(session('error'))<div class="alert-err">{{ session('error') }}</div>@endif

<form method="POST" action="{{ route('branch-transfers.store') }}">
@csrf

<div class="card">
    <div class="card-title">Batch Details</div>
    <div class="grid">
        <div>
            <label>From (Source)</label>
            @php $matina = $sites->first(fn ($s) => stripos($s->site_name, 'matina') !== false); @endphp
            <input type="text" value="{{ $matina->site_name ?? 'Matina' }}" readonly style="background:#f3f4f6;color:#6b7280;">
            <input type="hidden" name="source_site_id" value="{{ $matina->id ?? '' }}">
        </div>
        <div>
            <label>To (Destination) *</label>
            <select name="destination_site_id" required>
                <option value="">- Select destination -</option>
                @foreach($sites->where('id', '!=', $matina->id ?? null) as $s)
                    <option value="{{ $s->id }}" {{ old('destination_site_id') == $s->id ? 'selected' : '' }}>{{ $s->site_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Batch Date *</label>
            <input type="date" name="batch_date" value="{{ old('batch_date', date('Y-m-d')) }}" required>
        </div>
    </div>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <div class="card-title" style="margin:0;padding:0;border:none;">Products Sent</div>
        <button type="button" class="addrow" onclick="addRow()">+ Add Product</button>
    </div>
    <p class="hint">You can only dispatch up to the finished stock on hand. The system blocks over-dispatching so the records never mismatch.</p>
    <table>
        <thead><tr><th style="width:42%">Product</th><th>Qty Sent</th><th>Unit Price (&#8369;)</th><th></th></tr></thead>
        <tbody id="rows"></tbody>
    </table>
</div>

<div style="display:flex;gap:12px;justify-content:flex-end;">
    <a href="{{ route('branch-transfers.index') }}" class="btn btn-outline">Cancel</a>
    <button type="submit" class="btn btn-gold">Dispatch Batch</button>
</div>
</form>

<script>
const products = {!! $productData->toJson() !!};
let i = 0;
function opts(){ return products.map(function(p){ return '<option value="'+p.id+'" data-price="'+p.price+'" data-stock="'+p.stock+'">'+p.name+' ('+p.stock+' on hand)</option>'; }).join(''); }
function addRow(){
    const n = i++;
    const tr = document.createElement('tr');
    tr.id = 'r'+n;
    tr.innerHTML =
        '<td>' +
            '<select name="items['+n+'][product_id]" onchange="pick(this,'+n+')" required>' +
                '<option value="">- Select -</option>' + opts() +
            '</select>' +
            '<div class="avail" id="avail'+n+'"></div>' +
        '</td>' +
        '<td><input type="number" id="qty'+n+'" name="items['+n+'][qty_sent]" min="1" value="1" required></td>' +
        '<td><input type="number" step="0.01" min="0" id="price'+n+'" name="items['+n+'][unit_price]" required></td>' +
        '<td><button type="button" class="rm" onclick="document.getElementById(\'r'+n+'\').remove()">&times;</button></td>';
    document.getElementById('rows').appendChild(tr);
}
function pick(sel,n){
    const o = sel.options[sel.selectedIndex];
    const price = o.dataset.price;
    const stock = parseInt(o.dataset.stock || '0', 10);
    if(price) document.getElementById('price'+n).value = price;
    const qty = document.getElementById('qty'+n);
    const av = document.getElementById('avail'+n);
    if(sel.value){
        qty.max = stock;
        if(stock <= 0){ av.textContent = 'Out of stock - cannot dispatch'; av.className='avail low'; qty.value = 0; }
        else { av.textContent = 'Available to dispatch: ' + stock + ' pcs'; av.className='avail ok'; if(parseInt(qty.value) > stock) qty.value = stock; }
    } else { av.textContent=''; qty.max=''; }
}
addRow();
</script>
</x-app-layout>