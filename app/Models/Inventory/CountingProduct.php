<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\ModelTrait;
use App\Models\Catalog\Product;
use App\Models\Inventory\Unit;
//use App\Collections\CountingProductCollection;
use App\Repositories\Eloquent\Common\TermRepository;

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

    public function usage_unit()
    {
        return $this->belongsTo(Unit::class, 'unit_code', 'code');
    }


    // Attribute

    protected function quantity(): Attribute
    {
        return $this->setNumberAttribute($this->attributes['quantity']);
    }

    protected function stockQuantity(): Attribute
    {
        return $this->setNumberAttribute($this->attributes['stock_quantity']);
    }

    protected function price(): Attribute
    {
        return $this->setNumberAttribute($this->attributes['price']);
    }

    protected function amount(): Attribute
    {
        return $this->setNumberAttribute($this->attributes['amount']);
    }

    public function usageUnitName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->usage_unit->name ?? '',
        );
    }

    // Custom Collection
    // public function CountingProductCollection(array $models = [])
    // {
    //     return new CountingProductCollection($models);
    // }

    
}
