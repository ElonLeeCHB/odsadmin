<?php

namespace App\Domains\ApiWwwV2\Services\Catalog;

use App\Helpers\Classes\DataHelper;
use App\Helpers\Classes\OrmHelper;
use App\Services\Service;
use App\Repositories\Eloquent\Catalog\ProductRepository;
use App\Traits\Model\EloquentTrait;
use App\Helpers\Classes\RowsArrayHelper;
use App\Models\Catalog\ProductTag;
use App\Models\Catalog\Product;

class ProductService extends Service
{
    use EloquentTrait;

    public $modelName = "\App\Models\Catalog\Product";

    public function getProductById($product_id)
    {
        $product = (new Product)->getLocaleProductByIdForSale($product_id);

        return DataHelper::unsetArrayIndexRecursively($product->toArray(), ['translation']);
    }


    public function getList($filters)
    {
        return $this->getRows($filters);
    }
}
