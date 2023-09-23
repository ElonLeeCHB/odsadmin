<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use App\Models\SysData\Division;

class UserAddress extends Model
{
    protected $guarded = [];
    
    public function zone()
    {
        return $this->belongsTo(Division::class, 'zone_id', 'id');
    }
    
    public function city()
    {
        return $this->belongsTo(Division::class, 'zone_id', 'id');
    }
}
