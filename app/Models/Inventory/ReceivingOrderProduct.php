<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\Catalog\Product;
use App\Models\Inventory\ReceivingOrder;
use App\Models\Catalog\ProductUnit;
use App\Traits\ModelTrait;

class ReceivingOrderProduct extends Model
{
    use ModelTrait;

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


    public function price(): Attribute
    {
        return $this->setNumberAttribute($this->attributes['price'],4);
    }

    public function amount(): Attribute
    {
        return $this->setNumberAttribute($this->attributes['amount'],4);
    }

    public function receivingQuantity(): Attribute
    {
        return $this->setNumberAttribute($this->attributes['receiving_quantity'],4);
    }

    public function stockPrice(): Attribute
    {
        return $this->setNumberAttribute($this->attributes['stock_price'],4);
    }

    public function stockQuantity(): Attribute
    {
        return $this->setNumberAttribute($this->attributes['stock_quantity'],4);
    }

    

}
