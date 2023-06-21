<?php

namespace App\Models\Localization;

use Illuminate\Database\Eloquent\Model;

class Road extends Model
{
    
    public $timestamps = false;

    public function city()
    {
        return $this->belongsTo(Division::class, 'city_id', 'id');
    }

    // public function scopeStates($query, $country_code)
    // {
    //     $query->where('country_code', $country_code)->where('level',1);
    // }
}
