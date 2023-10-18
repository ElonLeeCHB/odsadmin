<?php

namespace App\Repositories\Eloquent\Inventory;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Repositories\Eloquent\Repository;
use App\Models\Inventory\Bom;
use App\Models\Inventory\BomProducts;

class BomRepository extends Repository
{
    public $modelName = "\App\Models\Inventory\Bom";


    public function getRows($data = [], $debug = 0)
    {
        $rows = parent::getRows($data, $debug);

        // 獲取關聯欄位
        if(!empty($data['select_relation_columns'])){
            $columns = $data['select_relation_columns'];

            foreach ($rows as $row) {
                if(in_array('product_name', $columns)){
                    $row->product_name = $row->product->name ?? '-- emtpy --';
                }
            }
        }
        

        return $rows;
    }


    public function getExtraColumns($row, $columns)
    {
        if(in_array('product_name', $columns)){
            $row->product_name = $row->product->name ?? 'No product name!!';
        }

        return $row;
    }

}