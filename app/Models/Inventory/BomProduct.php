<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use App\Models\Catalog\Product;
use App\Models\Inventory\Bom;

class BomProduct extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function sub_product()
    {
        return $this->belongsTo(Product::class, 'sub_product_id');
    }
}
