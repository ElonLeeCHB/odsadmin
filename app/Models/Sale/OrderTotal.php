<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class OrderTotal extends Model
{    
    protected $guarded = [];
    public $timestamps = false;


    protected function value(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => number_format($value),
        );
    }

}
