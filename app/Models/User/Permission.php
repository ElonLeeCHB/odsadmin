<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Permission extends Model
{
    public $timestamps = false;    
    protected $guarded = [];
}
