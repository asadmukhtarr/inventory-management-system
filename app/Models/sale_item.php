<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class sale_item extends Model
{
    //
    protected $fillable = [
        'sale_id',
        'product_id',
        'sku',
        'quantity',
        'price',
        'total'
    ];
    
    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'total' => 'decimal:2'
    ];
    
    // Relationships
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
