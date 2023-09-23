<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\Model\Translatable;

class Unit extends Model
{
    use Translatable;
    
    public $translated_attributes = ['name'];
    public $timestamps = false;
    protected $guarded = [];
    protected $appends = ['name'];


    public function scopeActive($query)
    {
        $query->where('is_active', 1);
    }


    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translation->name ?? '',
        );
    }
}
