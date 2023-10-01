<?php

namespace App\Domains\Admin\Services\Sale;

use App\Services\Service;

class MaterialRequisitionService extends Service
{

	public function export($data)
	{
		$this->getQuery($data);
	}

}