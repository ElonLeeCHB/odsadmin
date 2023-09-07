<?php

namespace App\Domains\Admin\Services\Localization;

use App\Services\Service;
use App\Repositories\Eloquent\Localization\RoadRepository;
use App\Repositories\Eloquent\Localization\DivisionRepository;

class RoadService extends Service
{
    protected $modelName = "\App\Models\Localization\Road";

	public function __construct(protected RoadRepository $repository
	, private DivisionRepository $DivisionRepository)
	{
	}
}