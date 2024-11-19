<?php

namespace App\Domains\ApiV2\Services\Member;

use App\Services\Service;
use App\Repositories\Eloquent\Member\MemberRepository;

class MemberService extends Service
{
    public $modelName = "\App\Models\Member\Member";

    public function __construct(private MemberRepository $MemberRepository)
    {
        $this->repository = $MemberRepository;
    }
}
