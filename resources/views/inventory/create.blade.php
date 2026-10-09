<x-app-layout>
<x-slot name="header">Add Inventory Item</x-slot>
<x-slot name="subheader">Record a new raw material or ingredient</x-slot>

<style>
.card{background:#fff;border-radius:14px;box-shadow:0 1px 6px rgba(0,0,0,.07);padding:24px;max-width:640px;}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
label{display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:6px;}
input,select,textarea{width:100%;padding:10px 13px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;background:#f8fafc;}
input:focus,select:focus{border-color:#D9782C;background:#fff;}
.btn{padding:10px 20px;border-radius:9px;font-size:13px;font-weight:700;border:none;cursor:pointer;text-decoration:none;}
.btn-gold{background:#D9782C;color:#fff;}
.btn-outline{background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;}
</style>

<form method="POST" action="{{ route('inventory.store') }}" class="card">
@csrf
<div class="form-grid">
    <div style="grid-column:1/-1;">
        <label>Item Name <span style="color:red">*</span></label>
        <input type="text" name="item_name" placeholder="e.g. All-Purpose Flour" required>
    </div>
    <div>
        <label>Category <span style="color:red">*</span></label>
        <select name="category" required>
            <option value="">— Select —</option>
            @foreach($categories as $cat)
                <option value="{{ $cat }}">{{ $cat }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Unit <span style="color:red">*</span></label>
        <select name="unit" required>
            <option value="">— Select —</option>
            @foreach($units as $u)
                <option value="{{ $u }}">{{ $u }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Starting Quantity <span style="color:red">*</span></label>
        <input type="number" name="quantity_on_hand" step="0.01" min="0" value="0" required>
    </div>
    <div>
        <label>Minimum Stock (low-stock alert) <span style="color:red">*</span></label>
        <input type="number" name="minimum_stock" step="0.01" min="0" value="0" required>
    </div>
    <div style="grid-column:1/-1;">
        <label>Notes</label>
        <textarea name="notes" rows="2"></textarea>
    </div>
</div>
<div style="display:flex;gap:12px;justify-content:flex-end;margin-top:20px;">
    <a href="{{ route('inventory.index') }}" class="btn btn-outline">Cancel</a>
    <button type="submit" class="btn btn-gold">Save Item</button>
</div>
</form>
</x-app-layout>