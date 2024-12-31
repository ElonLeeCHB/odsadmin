<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Model\ModelTrait;

class ProductTranslation extends Model
{
    use ModelTrait;
    
    public $timestamps = false;
    public $foreign_key = 'product_id';
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function master()
    {
        return $this->product();
    }
}
