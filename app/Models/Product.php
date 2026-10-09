<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'product_name',
        'description',
        'category',
        'price',
        'stock_quantity',
        'status',
        'site_id',
        'image_path',
    ];

    protected $casts = [
        'price' => 'float',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    // Materials (inventory items) used in this product - the recipe.
    // Live database table: product_raw_materials (quantity_needed = amount per 1 unit).
    public function materials()
    {
        return $this->belongsToMany(Inventory::class, 'product_raw_materials', 'product_id', 'inventory_id')
            ->withPivot('quantity_needed')
            ->withTimestamps();
    }

    /**
     * How many units of this product can be made from the raw materials
     * currently in inventory (limited by the scarcest ingredient).
     * Returns null when there is no recipe.
     */
    public function maxProducible(): ?int
    {
        $recipe = $this->materials->map(fn ($m) => [
            'available' => (float) $m->quantity_on_hand, // damaged items are already removed from on-hand
            'needed'    => (float) $m->pivot->quantity_needed,
        ])->all();

        return static::maxProducibleFrom($recipe);
    }

    /**
     * @param  array<int,array{available:float|int|string,needed:float|int|string}>  $recipe
     */
    public static function maxProducibleFrom(array $recipe): ?int
    {
        $caps = [];
        foreach ($recipe as $row) {
            $needed = (float) $row['needed'];
            if ($needed <= 0) {
                continue;
            }
            $caps[] = (int) floor(round(max(0, (float) $row['available']) / $needed, 6));
        }

        return $caps ? min($caps) : null;
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}