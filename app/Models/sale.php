<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\sale_item as SaleItem;

class Sale extends Model
{
    protected $table = 'sales';
    
    protected $fillable = [
        'invoice_no',
        'customer_id',
        'sale_date',
        'subtotal',
        'discount',
        'tax',
        'total',
        'paid_amount',
        'due_amount',
        'payment_status',
        'payment_method',
        'notes',
        'status',
        'created_by'
    ];
    
    protected $casts = [
        'sale_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
    ];
    
    protected $attributes = [
        'discount' => 0,
        'tax' => 0,
        'paid_amount' => 0,
        'due_amount' => 0,
        'payment_status' => 'unpaid',
        'payment_method' => 'cash',
        'status' => 'completed'
    ];
    
    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    
    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }
    
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}