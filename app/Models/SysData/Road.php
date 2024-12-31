<?php

namespace App\Models\SysData;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Model\ModelTrait;

class Road extends Model
{
    use ModelTrait;
    public $connection = 'sysdata'; 
    
    public $timestamps = false;

    public function city()
    {
        return $this->belongsTo(Division::class, 'city_id', 'id');
    }
}
