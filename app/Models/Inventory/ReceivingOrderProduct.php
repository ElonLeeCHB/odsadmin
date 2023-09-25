<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\Catalog\Product;
use App\Models\Inventory\ReceivingOrder;
use App\Traits\Model\Translatable;

class ReceivingOrderProduct extends Model
{
    protected $guarded = [];

    use Translatable;


    // Relation

    public function purchasing()
    {
        return $this->belongsTo(ReceivingOrder::class, 'receiving_order_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }


    //Attribute

    
}
