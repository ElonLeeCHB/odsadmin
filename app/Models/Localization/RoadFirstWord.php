<?php

namespace App\Models\Localization;

use Illuminate\Database\Eloquent\Model;

class RoadFirstWord extends Model
{
    public $timestamps = false;

    public function city()
    {
        return $this->belongsTo(Division::class, 'city_id', 'id');
    }
}
