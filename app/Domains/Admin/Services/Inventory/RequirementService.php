<?php

namespace App\Domains\Admin\Services\Inventory;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Repositories\Eloquent\Inventory\RequirementRepository;

class RequirementService extends Service
{

    public function __construct(private RequirementRepository $RequirementRepository)
    {
		$this->repository = $RequirementRepository;
	}

	public function getRequirementsDaily($data, $debug = 0)
	{
		return $this->repository->getRequirementsDaily($data, $debug);
	}

}