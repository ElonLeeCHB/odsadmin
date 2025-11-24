<?php

namespace App\Repositories\Access;

use App\Repositories\Repository;
use App\Models\Access\SystemUser;

class SystemUserRepository extends Repository
{
    /**
     * 指定 Model
     */
    protected function model(): string
    {
        return SystemUser::class;
    }
}
