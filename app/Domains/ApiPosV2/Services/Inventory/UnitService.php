<?php

namespace App\Domains\ApiPosV2\Services\Inventory;

use App\Services\Service;
use App\Repositories\Eloquent\Inventory\UnitRepository;

class UnitService extends Service
{
    protected $modelName = "\App\Models\Inventory\Unit";

	public function __construct(UnitRepository $repository)
	{
        $this->repository = $repository;
    }

}
