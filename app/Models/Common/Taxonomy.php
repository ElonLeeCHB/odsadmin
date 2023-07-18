<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\Model\Translatable;

class Taxonomy extends Model
{
    use Translatable;
    
    public $translated_attributes = ['name',];
    protected $appends = ['name'];
    protected $guarded = [];
   

    // Attributes

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translation->name ?? '',
        );
    }
    

}
