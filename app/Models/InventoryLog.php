<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryLog extends Model
{
    use HasFactory;

    protected $table = 'inventory_logs';

    // ← FIX: was wrong before — had Inventory fields instead of InventoryLog fields
    protected $fillable = [
        'inventory_id',
        'order_id',
        'type',        // received | used | damaged | adjustment
        'quantity',
        'ref_note',
        'notes',
        'user_id',
    ];

    public const TYPES = [
        'received'   => ['label' => 'Received',   'color' => 'text-green-700'],
        'used'       => ['label' => 'Used',        'color' => 'text-blue-700'],
        'damaged'    => ['label' => 'Damaged',     'color' => 'text-red-700'],
        'adjustment' => ['label' => 'Adjustment',  'color' => 'text-gray-700'],
    ];

    public function inventory() { return $this->belongsTo(Inventory::class); }
    public function user()      { return $this->belongsTo(User::class); }
}