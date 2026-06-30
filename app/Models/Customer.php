<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'address',
        'notes',
    ];

    // If you want to plan ahead for the relationships we discussed earlier:
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
