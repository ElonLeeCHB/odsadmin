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
            get: fn ($value, $attributes) => rtrim(rtrim($value, '0'), '.'),
            set: fn ($value) => empty($value) ? 0 : str_replace(',', '', $value),
        );
    }

}
