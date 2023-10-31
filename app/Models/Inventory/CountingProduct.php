<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\ModelTrait;

class CountingProduct extends Model
{
    use ModelTrait;
    
    public $table = 'inventory_counting_products';
    public $timestamps = false;
    protected $guarded = [];

    
    // Relation
    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Attribute

    protected function quantity(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => number_format(round($value)),
        );
    }
    
}
