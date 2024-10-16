<?php

namespace App\Models\SysData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Bank extends Model
{
    protected $guarded = [];
    public $connection = 'sysdata'; //會用在 EloquentTrait, 必須是 public

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id', 'id');
    }
    
}
