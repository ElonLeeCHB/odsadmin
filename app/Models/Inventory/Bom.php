<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;
use App\Models\Catalog\Product;
use App\Models\Inventory\BomProduct;
use App\Traits\ModelTrait;

class Bom extends Model
{
    use ModelTrait;
    
    protected $guarded = [];
    protected $appends = ['effective_date_ymd','expiry_date_ymd'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function bom_products()
    {
        return $this->hasMany(BomProduct::class, 'bom_id', 'id');
    }


    // Attribute
    
    public function effectiveDateYmd(): Attribute
    {
        if(!empty($this->effective_date)){
            $newValue = Carbon::parse($this->effective_date)->format('Y-m-d');
        }else if(empty($this->id) && empty($this->effective_date)) {
            $newValue = Carbon::now()->format('Y-m-d');
        }

        return Attribute::make(
            get: fn ($value) => $newValue ?? '',
        );
    }
    
    public function expiryDateYmd(): Attribute
    {
        if(!empty($this->purchasing_date)){
            $newValue = Carbon::parse($this->expiry_date)->format('Y-m-d');
        }
        return Attribute::make(
            get: fn ($value) => $newValue ?? '',
        );
    }


}
