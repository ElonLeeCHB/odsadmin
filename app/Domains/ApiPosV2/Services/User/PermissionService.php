<?php

namespace App\Domains\ApiPosV2\Services\User;

use App\Services\Service;

class PermissionService extends Service
{
    public $modelName = "\App\Models\User\Permission";

    public function getList($params)
    {
        return $this->getRows($params);
    }

    public function getInfo($params)
    {
        return $this->getRow($params);
    }
}
