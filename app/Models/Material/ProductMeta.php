<?php

namespace App\Models\Material;

use Illuminate\Database\Eloquent\Model;
use App\Models\Material\Product;

class ProductMeta extends Model
{
    protected $guarded = [];
    public $timestamps = false;

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
