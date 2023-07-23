<?php

namespace App\Repositories\Eloquent\Member;

use App\Traits\EloquentTrait;

class MemberRepository
{
    use EloquentTrait;

    public $modelName = "\App\Models\Member\Member";
}

