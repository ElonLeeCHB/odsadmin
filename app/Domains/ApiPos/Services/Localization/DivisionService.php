<?php

namespace App\Domains\ApiPos\Services\Localization;

use App\Services\Service;

class DivisionService extends Service
{
    public $modelName;

    public function __construct()
    {
        $this->modelName = "\App\Models\SysData\Division";
    }
}
