<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductTranslation;
use App\Models\Inventory\Bom;

class BomProduct extends Model
{
    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
    
    public function translation() {
        return $this->hasOne(ProductTranslation::class, 'product_id', 'sub_product_id')->where('locale', app()->getLocale());
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function sub_product()
    {
        return $this->belongsTo(Product::class, 'sub_product_id');
    }

    public function subProduct()
    {
        return $this->belongsTo(Product::class, 'sub_product_id');
    }
}
