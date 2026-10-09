<x-app-layout>
<x-slot name="header">Add Product</x-slot>
<x-slot name="subheader">Create a product and define what it's made from</x-slot>

<style>
.card{background:#fff;border-radius:14px;box-shadow:0 1px 6px rgba(0,0,0,.07);padding:24px;margin-bottom:20px;}
.card-title{font-size:15px;font-weight:800;color:#2E1C10;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #EDE0D0;}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
label{display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:6px;}
input,select{width:100%;padding:10px 13px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;background:#f8fafc;}
input[type=file]{padding:8px;}
table{width:100%;border-collapse:collapse;font-size:13px;}
th{padding:9px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:#8A7460;background:#FDF6EC;}
td{padding:10px 12px;border-bottom:1px solid #f9fafb;}
.btn{padding:10px 20px;border-radius:9px;font-size:13px;font-weight:700;border:none;cursor:pointer;text-decoration:none;}
.btn-gold{background:#D9782C;color:#fff;}
.btn-outline{background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;}
.add-row-btn{padding:8px 14px;border:1.5px dashed #EDE0D0;border-radius:9px;background:#FDF6EC;color:#8A7460;font-size:13px;font-weight:600;cursor:pointer;}
.preview{margin-top:10px;width:110px;height:110px;border-radius:10px;object-fit:cover;border:1px solid #EDE0D0;display:none;}
</style>

@php
    $invData = $inventoryItems->map(function ($i) {
        return ['id' => $i->id, 'name' => $i->item_name, 'unit' => $i->unit, 'onHand' => (float) $i->quantity_on_hand];
    });
@endphp

@if($errors->any())
<div style="background:#FBE4DA;border:1px solid #fecaca;color:#991b1b;padding:12px 16px;border-radius:9px;margin-bottom:16px;font-size:13px;">
<strong>Please fix:</strong><ul style="margin:6px 0 0 18px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

@if(session('error'))
<div style="background:#FBE4DA;border:1px solid #fecaca;color:#991b1b;padding:12px 16px;border-radius:9px;margin-bottom:16px;font-size:13px;">{{ session('error') }}</div>
@endif

<form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">
@csrf

<div class="card">
    <div class="card-title">Product Details</div>
    <div class="form-grid">
        <div style="grid-column:1/-1;">
            <label>Product Name <span style="color:red">*</span></label>
            <input type="text" name="product_name" value="{{ old('product_name') }}" placeholder="e.g. Banana Cake" required>
        </div>
        <div style="grid-column:1/-1;">
            <label>Description</label>
            <textarea name="description" rows="2" style="width:100%;padding:10px 13px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;background:#f8fafc;font-family:inherit;resize:vertical;" placeholder="Short description shown on the product card...">{{ old('description') }}</textarea>
        </div>
        <div>
            <label>Category <span style="color:red">*</span></label>
            <select name="category" id="categorySel" onchange="toggleNewCat()" required>
                <option value="">- Select -</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
                <option value="__new__" {{ old('category') === '__new__' ? 'selected' : '' }}>+ Add new category...</option>
            </select>
            <input type="text" name="new_category" id="newCat" maxlength="50" placeholder="New category name" value="{{ old('new_category') }}" style="display:none;margin-top:8px;">
        </div>
        <div>
            <label>Price (&#8369;) <span style="color:red">*</span></label>
            <input type="number" name="price" step="0.01" min="0" value="{{ old('price') }}" required>
        </div>
        <div style="grid-column:1/-1;">
            <label>Starting Finished Stock</label>
            <input type="number" name="stock_quantity" id="stockQty" min="0" step="1" oninput="recalc()" value="{{ old('stock_quantity', 0) }}">
            <p id="makeable" style="font-size:12px;margin-top:6px;font-weight:600;color:#8A7460;">Add ingredients below to see how many you can make.</p>
            <p style="font-size:11px;color:#8A7460;margin-top:2px;">Ingredients for the starting stock are deducted from inventory when you save.</p>
        </div>
        <div style="grid-column:1/-1;">
            <label>Product Photo</label>
            <input type="file" name="image" accept="image/*" onchange="previewImg(this)">
            <img id="preview" class="preview" alt="preview">
            <p style="font-size:11px;color:#8A7460;margin-top:6px;">Optional. JPG or PNG, up to 2MB.</p>
        </div>
    </div>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div class="card-title" style="margin:0;padding:0;border:none;">Recipe (Raw Materials Needed)</div>
        <button type="button" class="add-row-btn" onclick="addRow()">+ Add Ingredient</button>
    </div>
    <p style="font-size:12px;color:#8A7460;margin-bottom:12px;"><strong>Required</strong> - add at least one ingredient and how much of it is needed to make <strong>one unit</strong> of this product (e.g. a cake needs 2 kg flour). The Starting Finished Stock above is limited by what inventory can supply.</p>
    <table>
        <thead><tr><th style="width:50%">Inventory Item</th><th>Qty Needed (per unit)</th><th>In Stock</th><th></th></tr></thead>
        <tbody id="recipeBody"></tbody>
    </table>
</div>

<div style="display:flex;gap:12px;justify-content:flex-end;">
    <a href="{{ route('products.index') }}" class="btn btn-outline">Cancel</a>
    <button type="submit" class="btn btn-gold">Save Product</button>
</div>
</form>

<script>
const inventoryItems = {!! $invData->toJson() !!};
const oldRecipe = {!! json_encode(old('recipe', [])) !!};
let rowIndex = 0;

function toggleNewCat(){
    const isNew = document.getElementById('categorySel').value === '__new__';
    const f = document.getElementById('newCat');
    f.style.display = isNew ? 'block' : 'none';
    f.required = isNew;
}
function previewImg(input){
    const img = document.getElementById('preview');
    if(input.files && input.files[0]){
        img.src = URL.createObjectURL(input.files[0]);
        img.style.display = 'block';
    } else { img.style.display='none'; }
}
function itemOptions(selected){
    return inventoryItems.map(function(i){
        return '<option value="'+i.id+'"'+(String(selected)===String(i.id)?' selected':'')+'>'+i.name+' ('+i.unit+')</option>';
    }).join('');
}
function addRow(item, qty){
    const i = rowIndex++;
    const tr = document.createElement('tr');
    tr.id = 'recipe_row_'+i;
    tr.innerHTML =
        '<td><select name="recipe['+i+'][inventory_id]" required onchange="recalc()"><option value="">- Select item -</option>'+itemOptions(item)+'</select></td>' +
        '<td><input type="number" name="recipe['+i+'][quantity_needed]" step="0.01" min="0.01" value="'+(qty||'')+'" required oninput="recalc()"></td>' +
        '<td class="onhand" style="color:#8A7460;white-space:nowrap;">-</td>' +
        '<td><button type="button" onclick="removeRow('+i+')" style="background:none;border:none;color:#ef4444;cursor:pointer;" title="Remove">&times;</button></td>';
    document.getElementById('recipeBody').appendChild(tr);
    recalc();
}
function removeRow(i){
    const rows = document.querySelectorAll('#recipeBody tr');
    if(rows.length <= 1) return; // keep at least one ingredient row - recipe is required
    document.getElementById('recipe_row_'+i).remove();
    recalc();
}
// How many units the raw materials can make = scarcest ingredient (on hand / needed per unit)
function recalc(){
    let max = null, limiting = '';
    document.querySelectorAll('#recipeBody tr').forEach(function(tr){
        const sel = tr.querySelector('select');
        const qty = parseFloat(tr.querySelector('input').value);
        const cell = tr.querySelector('.onhand');
        const inv = inventoryItems.find(function(x){ return String(x.id) === sel.value; });
        cell.textContent = inv ? (inv.onHand + ' ' + inv.unit) : '-';
        if(inv && qty > 0){
            const cap = Math.floor(Math.round(inv.onHand / qty * 1e6) / 1e6);
            if(max === null || cap < max){ max = cap; limiting = inv.name; }
        }
    });
    const stock = document.getElementById('stockQty');
    const msg = document.getElementById('makeable');
    if(max === null){
        stock.removeAttribute('max');
        msg.textContent = 'Add ingredients below to see how many you can make.';
        msg.style.color = '#8A7460';
        return;
    }
    stock.max = max;
    if(parseInt(stock.value || '0', 10) > max) stock.value = max;
    const qty = parseInt(stock.value || '0', 10) || 0;
    msg.textContent = max > 0
        ? 'You can make up to ' + max + ' (limited by ' + limiting + ').'
            + (qty > 0 ? ' After ' + qty + ', you can still make ' + (max - qty) + ' more.' : '')
        : 'Not enough ' + limiting + ' in inventory to make even 1.';
    msg.style.color = max > 0 ? '#166534' : '#C2410C';
}

toggleNewCat();
const keys = Object.keys(oldRecipe);
if(keys.length){ keys.forEach(function(k){ addRow(oldRecipe[k].inventory_id, oldRecipe[k].quantity_needed); }); }
else { addRow(); }
</script>
</x-app-layout>
