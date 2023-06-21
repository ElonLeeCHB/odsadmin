<?php

namespace App\Domains\Api\Services\Localization;

use App\Domains\Api\Services\Service;
use App\Traits\Model\EloquentTrait;

class DivisionService extends Service
{
    use EloquentTrait;

    public $modelName;
    public $lang;

    public function __construct()
    {
        $this->modelName = "\App\Models\Localization\Division";
    }
}