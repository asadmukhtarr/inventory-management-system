<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'title',
        'status'
    ];
    
    protected $attributes = [
        'status' => 'active'
    ];
    public function product(){
        return $this->hasMany(product::class);
    }
}