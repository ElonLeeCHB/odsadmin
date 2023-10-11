<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\Model\Translatable;

class Unit extends Model
{
    use Translatable;
    
    protected $guarded = [];
    public $timestamps = false;
    protected $appends = ['name'];
    public $translated_attributes = ['name'];

    

    // Attribute

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translation->name ?? '',
        );
    }
    
}
