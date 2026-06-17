<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table = 'suppliers';
    
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'contact_person',
        'company_name',
        'balance',
        'status'
    ];
    
    protected $attributes = [
        'status' => 'active',
        'balance' => 0
    ];
    
    protected $casts = [
        'balance' => 'decimal:2'
    ];
    public function product(){
        return $this->hasMany(product::class);
    }
}