<?php

namespace App\Repositories\Eloquent\Catalog;

use App\Repositories\Eloquent\Repository;

class ProductUnitRepository extends Repository
{
    public $modelName = "\App\Models\Catalog\ProductUnit";


    public function getProductUnits($data = [], $debug = 0)
    {
        $rows = $this->getRows($data);
        
        return $rows;
    }
}