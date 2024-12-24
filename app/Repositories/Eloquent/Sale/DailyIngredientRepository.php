<?php

namespace App\Repositories\Eloquent\Sale;

use App\Helpers\Classes\DataHelper;
use App\Traits\Model\EloquentTrait;
use App\Domains\Admin\Exports\SaleOrderRequisitionMatrixListExport;
use App\Domains\Admin\Exports\SaleDailyRequisitionMatrixListExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class DailyIngredientRepository
{
    use EloquentTrait;

    public $modelName = "\App\Models\Sale\DailyIngredient";


    public function getRecords($params)
    {
        $params = $this->resetQueryData($params);

        $rows = $this->getRows($params);

        if(!empty($params['extra_columns'])){
            if(in_array('product_name', $params['extra_columns'])){
                foreach ($rows as $row) {
                    $row->product_name = $row->product->name ?? ' - ';
                }
            }
        }

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

        // 未來
        if (isset($params['equal_future_days'])) {
            $equalFutureDays = $params['equal_future_days'];
            $start_date = Carbon::tomorrow();
            $last_date = $start_date->copy()->addDays($equalFutureDays)->subDay();
            $params['whereRawSqls'][] = "`required_date` BETWEEN '$start_date' AND '$last_date'";
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

    public function exportMatrixList($post_data = [], $debug = 0)
    {
        $filename = '備料表多日距陣_'.date('Y-m-d_H-i-s').'.xlsx';

        return Excel::download(new SaleDailyRequisitionMatrixListExport($post_data, $this), $filename);
    }

}

