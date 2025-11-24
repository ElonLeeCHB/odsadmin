<?php

namespace App\Domains\Admin\Services\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Services\Service;
use App\Models\User\UserMeta;

class PermissionService extends Service
{
    protected $modelName = "\App\Models\Access\Permission";

    public function getPermissions($data, $debug=0)
    {
        $permissions = $this->getRows($data, $debug);

        return $permissions;
    }

}