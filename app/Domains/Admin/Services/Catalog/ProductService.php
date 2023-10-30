<?php

namespace App\Domains\Admin\Services\Catalog;

use App\Services\Service;
use App\Repositories\Eloquent\Catalog\ProductRepository;

class ProductService extends Service
{
    public $modelName = "\App\Models\Catalog\Product";
    protected $repository;

	public function __construct(protected ProductRepository $ProductRepository)
	{
        $this->repository = $ProductRepository;
    }

}
