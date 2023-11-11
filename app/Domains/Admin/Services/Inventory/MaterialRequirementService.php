<?php

namespace App\Domains\Admin\Services\Inventory;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Repositories\Eloquent\Inventory\MaterialRequirementRepository;

class MaterialRequirementService extends Service
{

    public function __construct(private MaterialRequirementRepository $MaterialRequirementRepository)
    {
		$this->repository = $MaterialRequirementRepository;
	}

	public function getRequirementsDaily($data, $debug = 0)
	{
		return $this->repository->getRequirementsDaily($data, $debug);
	}

}