<?php

namespace App\Domains\ApiWwwV2\Services\Catalog;

use App\Helpers\Classes\DataHelper;
use App\Helpers\Classes\OrmHelper;
use App\Services\Service;
use App\Traits\Model\EloquentTrait;
use App\Models\Catalog\Product;
use App\Helpers\Classes\RowsArrayHelper;
use App\Caches\Catalog\Product\Sale\ProductByLocaleWithOptionsIndexedByOptionCode;

class ProductService extends Service
{
    use EloquentTrait;

    public $modelName = "\App\Models\Catalog\Product";

    public function getProductById($product_id)
    {
        return ProductByLocaleWithOptionsIndexedByOptionCode::getById($product_id, app()->getLocale(), 3600);
    }


    public function getList($filters)
    {
        return $this->getRows($filters);
    }
}
