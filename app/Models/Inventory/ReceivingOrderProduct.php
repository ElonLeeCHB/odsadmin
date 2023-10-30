<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\Catalog\Product;
use App\Models\Inventory\ReceivingOrder;
use App\Models\Catalog\ProductUnit;
use App\Traits\Model\Translatable;

class ReceivingOrderProduct extends Model
{
    use Translatable;

    protected $guarded = [];


    // Relation

    public function purchasing()
    {
        return $this->belongsTo(ReceivingOrder::class, 'receiving_order_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function product_units()
    {
        return $this->hasMany(ProductUnit::class,'product_id', 'product_id');
    }


    //Attribute

    protected function price(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => number_format(round($value)),
        );
    }

    protected function amount(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => number_format(round($value)),
        );
    }

    protected function receivingQuantity(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => number_format(round($value)),
        );
    }

    protected function stockPrice(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => number_format(round($value)),
        );
    }

    

}
