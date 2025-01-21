<?php

namespace App\Repositories\Eloquent\Material;

use App\Repositories\Eloquent\Repository;

class ProductOptionValueRepository extends Repository
{
    public $modelName = "\App\Models\Material\ProductOptionValue";

    public function getOptionValuesByProductOption($product_id, $option_id)
    {
        $filter_data = [
            'equal_product_id' => $product_id,
            'equal_option_id' => $option_id,
            'equal_is_active' => 1,
            'pagination' => 0,
            'limit' => 0,
            'sort' => 'sort_order',
            'order' => 'ASC',
        ];
        $productOptionValues = $this->getRows($filter_data);

        foreach($productOptionValues as $row){
            $rows[] = (object)[
                'option_id' => $row->option_id,
                'option_value_id' => $row->option_value_id,
                'name' => $row->name,
                'short_name' => $row->short_name,
                'is_active' => $row->is_active,
            ];
        }

        return $rows;
    }
}

