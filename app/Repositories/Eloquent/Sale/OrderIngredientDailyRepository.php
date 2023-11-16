<?php

namespace App\Repositories\Eloquent\Sale;

use App\Traits\EloquentTrait;

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

        return $params;
    }
    




}

