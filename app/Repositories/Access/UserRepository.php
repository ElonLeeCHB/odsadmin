<?php

namespace App\Repositories\Access;

use App\Repositories\Repository;
use App\Models\User\User;

class UserRepository extends Repository
{
    /**
     * š„ Model
     */
    protected function model(): string
    {
        return User::class;
    }
}
