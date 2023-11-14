<?php

namespace App\Repositories\Eloquent\Inventory;

use App\Repositories\Eloquent\Repository;
use App\Models\Sale\OrderProductIngredient;
use App\Models\Sale\OrderProductIngredientDaily;
use App\Models\Inventory\MaterialRequirementsDaily;
use App\Models\Common\Term;
use App\Models\Common\TermTranslation;
use Maatwebsite\Excel\Facades\Excel;
use App\Domains\Admin\ExportsLaravelExcel\CommonExport;

class MaterialRequirementRepository extends Repository
{
    public $modelName = "\App\Models\Inventory\MaterialRequirementsDaily";


    public function getRequirementsDaily($data=[], $debug=0)
    {
        $data = $this->resetQueryData($data);

        $rows = $this->getRows($data, $debug);

        return $rows;
    }


    public function resetQueryData($data)
    {
        // 需求日
        if(!empty($data['filter_required_date'])){
            $rawSql = $this->parseDateToSqlWhere('required_date', $data['filter_required_date']);
            if($rawSql){
                $data['whereRawSqls'][] = $rawSql;
            }
            unset($data['filter_required_date']);
        }

        return $data;
    }


    public function anylize()
    {
        
    }


    public function exportList($post_data = [], $debug = 0)
    {
        $post_data = $this->resetQueryData($post_data);

        if(empty($post_data['sort'])){
            $post_data['sort'] = 'id';
            $post_data['order'] = 'ASC';
        }

        $post_data['pagination'] = false;
        $post_data['limit'] = 1000;
        $post_data['extra_columns'] = [];

        $tmprows = $this->getRequirementsDaily($post_data);

        $data = [];
        $rows = [];

        foreach ($tmprows as $row) {
            $rows[] = [
                'required_date' => $row->required_date,
                'product_name' => $row->product_name,
                'supplier_short_name' => $row->supplier_short_name,
                'supplier_own_product_code' => $row->supplier_own_product_code,

                'stock_quantity' => $row->stock_quantity,
                'stock_unit_name' => $row->stock_unit->name,
                
                'id' => $row->id,
                'product_id' => $row->product_id,
                'supplier_id' => $row->supplier_id,
                'created_at' => $row->created_at,
            ];
        }
        $data['collection'] = collect($rows);

        $data['headings'] = ['需求日', '品名', '廠商簡稱' , '廠商品號'
                             , '庫存單位', '庫存數量'

                             , 'ID', '料件號碼', '廠商號碼', '創建時間'
                            ];

        return Excel::download(new CommonExport($data), 'inventory_products.xlsx');
    }
}
