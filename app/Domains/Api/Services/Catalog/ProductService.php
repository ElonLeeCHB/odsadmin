<?php

namespace App\Domains\Api\Services\Catalog;

use App\Services\Catalog\ProductService as GlobalProductService;

class ProductService extends GlobalProductService
{
    public $modelName = "\App\Models\Catalog\Product";
}
