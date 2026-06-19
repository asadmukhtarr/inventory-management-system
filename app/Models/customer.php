<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'customers';
    
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'balance',
        'status'
    ];
    
    protected $attributes = [
        'status' => 'active',
        'balance' => 0
    ];
    
    protected $casts = [
        'balance' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    // Scope for active customers
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    // Scope for inactive customers
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }
    
    // Accessor for formatted balance
    public function getFormattedBalanceAttribute()
    {
        return '$' . number_format($this->balance, 2);
    }
    
    // Accessor for customer full info
    public function getFullInfoAttribute()
    {
        return $this->name . ' (' . $this->email . ')';
    }
    
    // Check if customer is active
    public function isActive()
    {
        return $this->status === 'active';
    }
    
    // Update balance
    public function updateBalance($amount)
    {
        $this->balance += $amount;
        $this->save();
        return $this->balance;
    }
}