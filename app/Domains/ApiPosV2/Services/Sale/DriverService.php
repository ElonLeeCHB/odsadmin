<?php

namespace App\Domains\ApiPosV2\Services\Sale;

use App\Services\Service;

class DriverService extends Service
{
    public $modelName;
    public $model;
    public $table;
    public $lang;

	public function __construct()
	{
        $this->modelName = "\App\Models\Sale\Driver";
        $this->model = new $this->modelName;
    }
}

