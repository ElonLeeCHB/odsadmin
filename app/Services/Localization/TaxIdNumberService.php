<?php

namespace App\Services\Localization;

use App\Services\Service;
use App\Repositories\Eloquent\Localization\TaxIdNumRepository;

class TaxIdNumberService extends Service
{
    protected $modelName = "\App\Models\SysData\TwTaxIdNum";

	public function __construct(TaxIdNumRepository $repository)
	{
        $this->repository = $repository;
    }

}
