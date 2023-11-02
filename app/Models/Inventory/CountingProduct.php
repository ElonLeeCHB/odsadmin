<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\ModelTrait;
use App\Models\Catalog\Product;
use App\Models\Common\Unit;
use App\Collections\CountingProductCollection;

class CountingProduct extends Model
{
    use ModelTrait;
    
    public $table = 'inventory_counting_products';
    public $timestamps = false;
    protected $guarded = [];
    protected $appends = ['product'];

    
    // Relation
    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_code', 'code');
    }

    // Attribute

    protected function quantity(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => number_format(round($value)),
        );
    }


    // Custom Collection
    public function CountingProductCollection(array $models = [])
    {
        return new CountingProductCollection($models);
    }

    
}
