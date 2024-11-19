<?php

namespace App\Domains\ApiV2\Services\Catalog;

use App\Services\Service;
use App\Repositories\Eloquent\Catalog\ProductRepository;
use App\Traits\EloquentTrait;

class ProductService extends Service
{
    use EloquentTrait;

    public $modelName = "\App\Models\Catalog\Product";

	public function __construct(protected ProductRepository $ProductRepository)
	{
        $this->repository = $ProductRepository;
    }
}
