<?php

namespace App\Domains\Admin\Services\Localization;

use App\Domains\Admin\Services\Service;
use App\Repositories\Eloquent\Localization\RoadRepository;
use App\Repositories\Eloquent\Localization\DivisionRepository;

class RoadService extends Service
{
    private $modelName = "\App\Models\Localization\Road";
	private $lang;

	public function __construct(protected RoadRepository $repository
	, private DivisionRepository $DivisionRepository)
	{
	}
}