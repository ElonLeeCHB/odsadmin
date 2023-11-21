<?php

namespace App\Repositories\Eloquent\Sale;

use App\Helpers\Classes\DataHelper;
use App\Traits\EloquentTrait;
use App\Domains\Admin\Exports\SaleOrderRequisitionDailyListExport;
use Maatwebsite\Excel\Facades\Excel;

class OrderIngredientDailyRepository
{
    use EloquentTrait;

    public $modelName = "\App\Models\Sale\OrderIngredientDaily";


    public function getDailyRequisitions($params, $debug = 0)
    {
        $rows = $this->getRows($params, $debug);

        return $rows;
    }


    public function getDailyIngredients($params, $debug = 0)
    {
        $params = $this->resetQueryData($params);
        $rows = $this->getRows($params, $debug);

        return $rows;
    }

    public function resetQueryData($params)
    {
        $locale = app()->getLocale();
        
        if(!empty($params['filter_required_date'])){
            $rawSql = $this->parseDateToSqlWhere('required_date', $params['filter_required_date']);
            if($rawSql){
                $params['whereRawSqls'][] = $rawSql;
            }
            unset($params['filter_required_date']);
        }

        if(!empty($params['filter_product_name'])){
            $params['whereHas']['product.translation'] = [
                'filter_name' => $params['filter_product_name'],
            ];
            unset($params['filter_product_name']);
        }

        if(isset($params['equal_days_before']) && $params['equal_days_before'] == 0){
            $today = date("Y-m-d");
            $params['whereRawSqls'][] = "DATE('required_date') > '$today'";
            unset($params['equal_days_before']);
        }


        // 依料件名稱排序
        if(!empty($params['sort']) && $params['sort'] == 'product_name'){
            $params['orderByRaw'] = "(SELECT name FROM product_translations WHERE locale='".$locale."' and product_translations.product_id = order_ingredients_dailies.product_id) " . $params['order'];
            unset($params['sort']);
            unset($params['order']);
        }

        // 依廠商名稱排序
        if(!empty($params['sort']) && $params['sort'] == 'supplier_short_name'){
            $params['orderByRaw'] = "(SELECT organizations.short_name FROM products,organizations WHERE products.supplier_id=organizations.id AND products.id = order_ingredients_dailies.product_id) " . $params['order'];
            unset($params['sort']);
            unset($params['order']);
        }



        return $params;
    }
    

    public function exportDailoyList($post_data = [], $debug = 0)
    {
        $filename = '備料表日匯總_'.date('Y-m-d_H-i-s').'.xlsx';

        //return Excel::download(new InventoryCountingListExport($post_data, $this->ProductRepository), $filename);
        return Excel::download(new SaleOrderRequisitionDailyListExport($post_data, $this), $filename);
    }


}

