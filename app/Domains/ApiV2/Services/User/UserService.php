<?php

namespace App\Domains\ApiV2\Services\User;

use App\Repositories\Eloquent\User\UserRepository;
use App\Services\Service;

class UserService extends Service
{
    public $modelName = "\App\Models\User\User";

    public function getUser($params)
    {
        return $this->getRow($params);
    }
}
