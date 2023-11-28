<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;

class ProductTranslation extends Model
{
    //public $timestamps = false;
    public $foreign_key = 'product_id';
    protected $guarded = [];
    public $timestamps = false;

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function master()
    {
        return $this->product();
    }
}
