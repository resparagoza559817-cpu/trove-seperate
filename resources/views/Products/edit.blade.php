<x-app-layout>
<x-slot name="header">Edit Product</x-slot>
<x-slot name="subheader">Update product details, photo &amp; materials</x-slot>

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
.btn-gold{background:#D9782C;color:#fff;} .btn-outline{background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;}
.alert-err{background:#FBE4DA;border:1px solid #fecaca;color:#991b1b;padding:12px 16px;border-radius:9px;margin-bottom:16px;font-size:13px;}
.thumb{width:110px;height:110px;object-fit:cover;border-radius:10px;border:1px solid #EDE0D0;}
.preview{margin-top:10px;width:110px;height:110px;border-radius:10px;object-fit:cover;border:1px solid #EDE0D0;display:none;}
</style>

<div style="max-width: 900px; margin: 0 auto;">
@if ($errors->any())
    <div class="alert-err"><strong>Errors:</strong><ul style="margin:8px 0 0;padding-left:20px;">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data">
@csrf
@method('PUT')

<div class="card">
    <div class="card-title">Product Details</div>
    <div class="form-grid">
        <div style="grid-column:1/-1;">
            <label>Product Name <span style="color:red">*</span></label>
            <input type="text" name="product_name" value="{{ old('product_name', $product->product_name) }}" required>
        </div>
        <div style="grid-column:1/-1;">
            <label>Description</label>
            <textarea name="description" rows="2" style="width:100%;padding:10px 13px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;background:#f8fafc;font-family:inherit;resize:vertical;" placeholder="Short description shown on the product card...">{{ old('description', $product->description) }}</textarea>
        </div>
        <div>
            <label>Category</label>
            <select name="category" required>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" {{ old('category', $product->category) === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Price (&#8369;) <span style="color:red">*</span></label>
            <input type="number" name="price" step="0.01" min="0" value="{{ old('price', $product->price) }}" required>
        </div>
        <div>
            <label>Status</label>
            <select name="status">
                <option value="active" {{ old('status', $product->status) === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ old('status', $product->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <div style="grid-column:1/-1;">
            <label>Product Photo</label>
            <div style="display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap;">
                <div>
                    <div style="font-size:11px;color:#8A7460;margin-bottom:5px;">Current</div>
                    @if($product->image_path)
                        <img src="{{ asset('storage/'.$product->image_path) }}" class="thumb" alt="current">
                    @else
                        <div class="thumb" style="background:#FDF6EC;display:flex;align-items:center;justify-content:center;color:#C9B9A6;font-size:12px;">None</div>
                    @endif
                </div>
                <div style="flex:1;min-width:200px;">
                    <div style="font-size:11px;color:#8A7460;margin-bottom:5px;">Upload new (replaces current)</div>
                    <input type="file" name="image" accept="image/*" onchange="previewImg(this)">
                    <img id="preview" class="preview" alt="preview">
                    <p style="font-size:11px;color:#8A7460;margin-top:6px;">JPG or PNG, up to 2MB. Leave empty to keep current.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-title">Materials Used in This Product</div>
    @if ($product->materials && $product->materials->count())
        <table>
            <thead><tr><th>Material</th><th>Quantity Used (per unit)</th><th>Unit</th></tr></thead>
            <tbody>
                @foreach ($product->materials as $material)
                    <tr><td>{{ $material->item_name }}</td><td>{{ number_format($material->pivot->quantity_needed, 2) }}</td><td>{{ $material->unit }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="color:#8A7460;margin:0;">No materials defined for this product.</p>
    @endif
</div>

<div style="display:flex;gap:12px;justify-content:flex-end;">
    <a href="{{ route('products.index') }}" class="btn btn-outline">Cancel</a>
    <button type="submit" class="btn btn-gold">Save Changes</button>
</div>
</form>
</div>

<script>
function previewImg(input){
    const img = document.getElementById('preview');
    if(input.files && input.files[0]){ img.src = URL.createObjectURL(input.files[0]); img.style.display='block'; }
    else { img.style.display='none'; }
}
</script>
</x-app-layout>