<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\Model\ModelTrait;

class Unit extends Model
{
    use ModelTrait;
    
    protected $guarded = [];
    public $timestamps = false;
    protected $appends = ['name'];
    public $translation_keys = ['name'];

    
    public function scopeActive($query)
    {
        $query->where('is_active', 1);
    }

    // Attribute

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translation->name ?? '',
        );
    }
    
}
