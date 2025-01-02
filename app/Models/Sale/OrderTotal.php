<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\Model\ModelTrait;

class OrderTotal extends Model
{    
    use ModelTrait;

    protected $guarded = [];
    public $timestamps = false;
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected function value(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => rtrim(rtrim($value, '0'), '.'),
            set: fn ($value) => empty($value) ? 0 : str_replace(',', '', $value),
        );
    }

}
