<?php

namespace App\Domains\Api\Services\Catalog;

use Illuminate\Support\Facades\DB;
use App\Domains\Api\Services\Service;
use App\Services\Catalog\ProductService as GlobalProductService;
use App\Domains\Api\Services\Catalog\CategoryService;
use App\Domains\Api\Services\Catalog\ProductOptionService;

class ProductService extends GlobalProductService
{
    public $modelName = "\App\Models\Catalog\Product";
}
