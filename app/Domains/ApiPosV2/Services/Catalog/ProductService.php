<?php

namespace App\Domains\ApiPosV2\Services\Catalog;

use App\Helpers\Classes\DataHelper;
use App\Services\Service;
use App\Repositories\Eloquent\Catalog\ProductRepository;
use App\Traits\Model\EloquentTrait;
use App\Models\Material\Product;

class ProductService extends Service
{
    use EloquentTrait;

    public $modelName = "\App\Models\Catalog\Product";

    public function getInfo($product_id)
    {
        $productModel = new Product;
        return $productModel->getCache($product_id);
    }

    /**
     * simplelist
     * basictable
     */

     public function getSimplelist($filters)
     {
        try {

            $filters['with'] = [];

            $filters['select'] = ['id', 'code', 'model', 'name'];

            return $products = $this->getRows($filters);

        } catch (\Exception $ex) {
            return ['error' => $ex->getMessage()];
        }
     }


    public function getList($filters)
    {
        return $this->getRows($filters);
    }
}
