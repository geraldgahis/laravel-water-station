<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'stock_count',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
