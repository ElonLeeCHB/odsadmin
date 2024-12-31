<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use App\Models\Catalog\Product;
use App\Traits\Model\ModelTrait;

class ProductMeta extends Model
{
    use ModelTrait;
    
    protected $guarded = [];
    public $timestamps = false;

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
