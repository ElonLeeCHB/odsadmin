<?php

namespace App\Models\SysData;

use Illuminate\Database\Eloquent\Model;

class RoadFirstWord extends Model
{
    protected $connection = 'sysdata'; 
    
    public $timestamps = false;

    public function city()
    {
        return $this->belongsTo(Division::class, 'city_id', 'id');
    }
}
