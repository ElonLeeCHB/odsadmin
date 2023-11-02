<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;
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
        return $this->hasMany(CountingProduct::class, 'counting_id', 'id');
    }


    // Attribute

    public function formDateYmd(): Attribute
    {
        if(empty($this->id) && empty($this->form_date)) {
            $newValue = Carbon::now()->format('Y-m-d');
        }
        else if(!empty($this->form_date)){
            $newValue = Carbon::parse($this->form_date)->format('Y-m-d');
        } 

        return Attribute::make(
            get: fn ($value) => $newValue ?? '',
        );
    }
    
}
