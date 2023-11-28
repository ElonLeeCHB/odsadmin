<?php

namespace App\Models\SysData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Bank extends Model
{
    protected $guarded = [];
    protected $connection = 'sysdata';

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id', 'id');
    }
    
}
