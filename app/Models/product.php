<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';
    
    protected $fillable = [
        'name',
        'image',
        'description',
        'sku',
        'category_id',
        'brand_id',
        'supplier_id',
        'quantity',
        'sale_price',
        'purchase_price',
        'status'
    ];
    
    protected $attributes = [
        'status' => 1,
        'quantity' => 0,
        'sale_price' => 0,
        'purchase_price' => 0
    ];
    
    // Accessor for image URL
    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        return null;
    }
    
}