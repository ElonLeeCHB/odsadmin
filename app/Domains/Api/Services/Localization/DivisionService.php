<?php

namespace App\Domains\Api\Services\Localization;

use App\Traits\EloquentTrait;

class DivisionService
{
    use EloquentTrait;

    public $modelName;

    public function __construct()
    {
        $this->modelName = "\App\Models\Localization\Division";
    }
}