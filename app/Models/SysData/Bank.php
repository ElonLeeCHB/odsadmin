<?php

namespace App\Models\SysData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Bank extends Model
{
    protected $guarded = [];
    public $connection = 'sysdata'; //會用在 EloquentTrait, 必須是 public
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
    
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id', 'id');
    }
    
}
