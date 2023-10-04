<?php

namespace App\Services\Localization;

use App\Services\Service;
use App\Traits\EloquentTrait;
use App\Repositories\Eloquent\SysData\RoadFirstWordRepository;

class RoadService extends Service
{
    use EloquentTrait;

    public $modelName;
    public $model;
    public $table;

	public function __construct(private RoadFirstWordRepository $RoadFirstWordRepository)
	{
        $this->modelName = "\App\Models\SysData\Road";
	}


    public function getFirstWords($data = [], $debug = 0)
    {
        $rows = $this->RoadFirstWordRepository->getRows($data, $debug);

        return $rows;
    }
}