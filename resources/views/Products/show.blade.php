<x-app-layout>
<x-slot name="header">{{ $product->product_name }}</x-slot>
<x-slot name="subheader">Product Details &amp; Materials</x-slot>

<style>
.card { background: var(--white); border-radius: 12px; padding: 24px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
.card-title { font-size: 15px; font-weight: 800; color: var(--text); margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border); }
.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.info-label { font-size: 12px; color: var(--muted); font-weight: 600; text-transform: uppercase; margin-bottom: 6px; }
.info-value { font-size: 18px; font-weight: 800; color: var(--text); }
table { width: 100%; border-collapse: collapse; font-size: 13px; }
th { padding: 12px; text-align: left; font-size: 11px; text-transform: uppercase; color: var(--muted); background: var(--bg); }
td { padding: 12px; border-bottom: 1px solid var(--border); }
.btn { display: inline-block; padding: 10px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 13px; border: none; cursor: pointer; }
.btn-edit { background: var(--gold); color: var(--white); } .btn-delete { background: var(--red); color: var(--white); } .btn-back { background: var(--navy); color: var(--white); }
.status-badge { display: inline-block; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; }
.status-active { background: var(--green); color: var(--white); } .status-inactive { background: #e5e7eb; color: var(--text); }
.hero { width:100%; max-width:360px; height:240px; object-fit:cover; border-radius:12px; border:1px solid var(--border); }
.hero-ph { width:100%; max-width:360px; height:240px; border-radius:12px; border:1px solid var(--border); background:#FDF6EC; display:flex; align-items:center; justify-content:center; color:#C9B9A6; }
</style>

<div style="max-width: 1000px; margin: 0 auto;">
    <div class="card">
        <div style="display:flex; gap:24px; flex-wrap:wrap; align-items:flex-start;">
            <div>
                @if($product->image_path)
                    <img src="{{ asset('storage/'.$product->image_path) }}" class="hero" alt="{{ $product->product_name }}">
                @else
                    <div class="hero-ph">No photo</div>
                @endif
            </div>
            <div style="flex:1; min-width:240px;">
                <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:18px;">
                    <h1 style="font-size:24px; font-weight:800; margin:0;">{{ $product->product_name }}</h1>
                    <span class="status-badge {{ $product->status == 'active' ? 'status-active' : 'status-inactive' }}">{{ ucfirst($product->status) }}</span>
                </div>
                <div class="info-grid">
                    <div><div class="info-label">Price</div><div class="info-value">&#8369;{{ number_format($product->price, 2) }}</div></div>
                    <div><div class="info-label">Category</div><div class="info-value">{{ $product->category ?? 'N/A' }}</div></div>
                </div>
            </div>
        </div>
    </div>

    @if ($product->materials && $product->materials->count())
        <div class="card">
            <div class="card-title">Materials Used (Per Unit)</div>
            <table>
                <thead><tr><th>Material</th><th style="text-align:right;">Quantity Used</th><th style="text-align:center;">Unit</th></tr></thead>
                <tbody>
                    @foreach ($product->materials as $material)
                        <tr><td style="font-weight:500;">{{ $material->item_name }}</td><td style="text-align:right;">{{ number_format($material->pivot->quantity_needed, 2) }}</td><td style="text-align:center;">{{ $material->unit }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="card"><p style="color: var(--muted); margin: 0;">No materials defined for this product yet.</p></div>
    @endif

    <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
        <a href="{{ route('products.index') }}" class="btn btn-back">&larr; Back to Products</a>
        @can('admin')
            <a href="{{ route('products.edit', $product) }}" class="btn btn-edit">Edit</a>
            <form action="{{ route('products.destroy', $product) }}" method="POST" style="display: inline;">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-delete" onclick="return confirm('Delete this product? This action cannot be undone.')">Delete</button>
            </form>
        @endcan
    </div>
</div>
</x-app-layout>