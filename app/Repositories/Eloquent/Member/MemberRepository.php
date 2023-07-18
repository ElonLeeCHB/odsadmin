<?php

namespace App\Repositories\Eloquent\Member;

use App\Repositories\Eloquent\Repository;
use App\Traits\EloquentTrait;

class MemberRepository extends Repository
{
    use EloquentTrait;

    public $modelName = "\App\Models\Member\Member";
}

