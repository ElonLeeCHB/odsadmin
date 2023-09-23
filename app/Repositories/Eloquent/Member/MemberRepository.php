<?php

namespace App\Repositories\Eloquent\Member;

use App\Repositories\Eloquent\User\UserRepository;

class MemberRepository extends UserRepository
{
    public $modelName = "\App\Models\Member\Member";


}

