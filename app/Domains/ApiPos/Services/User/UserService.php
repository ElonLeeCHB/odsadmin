<?php

namespace App\Domains\ApiPos\Services\User;

use App\Services\Service;

class UserService extends Service
{
    public $modelName = "\App\Models\User\User";

    public function getUser($params)
    {
        return $this->getRow($params);
    }
}
