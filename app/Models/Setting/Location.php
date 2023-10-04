<?php

namespace App\Models\Setting;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $guarded = [];


    public function scopeActive($query)
    {
        $query->where('is_active', 1);
    }

}
