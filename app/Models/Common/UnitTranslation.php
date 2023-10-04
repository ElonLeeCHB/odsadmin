<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Model\Translatable;
use Illuminate\Database\Eloquent\Casts\Attribute;

class UnitTranslation extends Model
{
    use Translatable;
    
    public $translated_attributes = ['name'];
    public $timestamps = false;
    protected $guarded = [];


    public function scopeActive($query)
    {
        $query->where('is_active', 1);
    }


    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id', 'id');
    }

}
