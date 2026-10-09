<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    protected $fillable = ['site_name', 'street', 'city'];

    /** The Matina commissary: every product and inventory item starts here. */
    public static function matina(): ?self
    {
        return static::where('site_name', 'like', '%matina%')->first();
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function rawMaterials()
    {
        return $this->hasMany(RawMaterial::class);
    }
}