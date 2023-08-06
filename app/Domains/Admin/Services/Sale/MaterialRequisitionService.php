<?php

namespace App\Domains\Admin\Services\Sale;

use App\Domains\Admin\Services\Service;

class MaterialRequisitionService extends Service
{

	public function __construct()
	{
	}


	public function export($data)
	{

		$this->getQuery($data);
	}

}