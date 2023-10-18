<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use App\Models\Catalog\Product;
use App\Models\Inventory\BomProduct;

class Bom extends Model
{
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sub_products()
    {
        return $this->hasMany(BomProduct::class, 'bom_id', 'id');
    }
}
