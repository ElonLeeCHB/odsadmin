<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\ModelTrait;
use App\Models\Inventory\CountingProduct;

class Counting extends Model
{
    use ModelTrait;
    
    public $table = 'inventory_countings';
    protected $guarded = [];


    protected static function booted()
    {
        parent::boot();

        static::observe(\App\Observers\InventoryCountingObserver::class);
    }
    
    
    // Relation
    
    public function counting_products()
    {
        return $this->hasMany(CountingProduct::class, 'product_id', 'id');
    }

    // Attribute
    
}
