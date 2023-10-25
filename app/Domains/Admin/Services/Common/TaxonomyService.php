<?php

namespace App\Domains\Admin\Services\Common;

use App\Services\Service;
use App\Repositories\Eloquent\Common\TaxonomyRepository;

class TaxonomyService extends Service
{
    protected $modelName = "\App\Models\Common\Taxonomy";

	public function __construct(TaxonomyRepository $repository)
	{
        $this->repository = $repository;
    }

}
