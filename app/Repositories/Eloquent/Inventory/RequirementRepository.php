<?php

namespace App\Repositories\Eloquent\Inventory;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Models\Sale\OrderProductIngredient;
use App\Models\Sale\OrderProductIngredientDaily;
use App\Models\Inventory\Requirement;
use App\Models\Common\Term;
use App\Models\Common\TermTranslation;
use Maatwebsite\Excel\Facades\Excel;
use App\Domains\Admin\ExportsLaravelExcel\CommonExport;

class RequirementRepository extends Repository
{
    public $modelName = "\App\Models\Inventory\Requirement";


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
        
        // 昨天以前
        if(isset($params['equal_days_before']) && $params['equal_days_before'] == 0){
            $yesterday = date("Y-m-d", strtotime("-1 day"));
            $params['whereRawSqls'][] = "`required_date` > '$yesterday'";
            unset($params['equal_days_before']);
        }

        return $data;
    }

    public function saveDailyRequirements($data)
    {
        try{
            DB::beginTransaction();

            // reset() 可以取得第一筆
            $first = reset($data);
            $required_date = $first['required_date'];
            
            foreach ($data as $row) {
                $arr = [
                    'required_date' => $row['required_date'],
                    'product_id' => $row['product_id'],
                    'product_name' => $row['product_name'],
                    'stock_unit_code' => $row['stock_unit_code'],
                    'stock_quantity' => $row['stock_quantity'],
                    'supplier_id' => $row['supplier_id'],
                    'supplier_short_name' => $row['supplier_short_name'],
                    'supplier_own_product_code' => $row['supplier_own_product_code'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $upsert_data[] = $arr;
            }

            if(!empty($upsert_data)){
                Requirement::where('required_date', $first['required_date'])->delete();
                $result = Requirement::upsert($upsert_data, ['required_date', 'product_id']);
            }
            
            DB::commit();

            return $result;

        } catch (\Exception $ex) {
            DB::rollback();
            $msg = ['error' => $ex->getMessage()];
            return $msg;
        } 
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
