<?php

namespace App\Models\Localization;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    public $timestamps = false;
    
    protected $guarded = [];

    public function scopeActive($query)
    {
        $query->where('is_active', 1);
    }
}
