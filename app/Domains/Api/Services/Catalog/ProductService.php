<?php

namespace App\Domains\Api\Services\Catalog;

use App\Services\Service;
use App\Repositories\Eloquent\Catalog\ProductRepository;
use App\Traits\Model\EloquentTrait;

class ProductService extends Service
{
    use EloquentTrait;

    public $modelName = "\App\Models\Material\Product";

	public function __construct(protected ProductRepository $ProductRepository)
	{
        $this->repository = $ProductRepository;
    }
}
