<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Model;
use App\Models\User\UserMeta;

class MemberMeta extends UserMeta
{
    public $timestamps = false;
    public $table = 'user_metas';
    protected $guarded = [];
}