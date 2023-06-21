<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use App\Models\Catalog\Product;

class ProductBom extends Model
{
    protected $guarded = [];

    public function subProducts()
    {
        //return $this->hasMany(Product::class, 'product_id', 'id');
        return $this->hasOne(Product::class, 'sub_product_id');
    }
}
