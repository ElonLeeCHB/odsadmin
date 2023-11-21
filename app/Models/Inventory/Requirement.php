<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\Inventory\Unit;
use App\Traits\ModelTrait;

class Requirement extends Model
{
    use ModelTrait;
    
    protected $guarded = [];
    protected $appends = ['effective_date_ymd','expiry_date_ymd'];


    public function stock_unit()
    {
        return $this->belongsTo(Unit::class, 'stock_unit_code', 'code');
    }
    

    // Attribute
    protected function stockUnitName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->stock_unit->name ?? '',
        );
    }



}
