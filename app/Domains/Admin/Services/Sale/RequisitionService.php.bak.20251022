<?php

namespace App\Domains\Admin\Services\Sale;

use App\Services\Service;
use App\Repositories\Eloquent\Sale\OrderIngredientRepository;
use App\Repositories\Eloquent\Sale\DailyIngredientRepository;
use App\Repositories\Eloquent\Sale\OrderDailyRequisitionRepository;

/**
 * 名稱解釋：
 *     Requisition 備料表
 *     Requirements 需求表
 * 
 */
class RequisitionService extends Service
{
    public function __construct(
        protected OrderIngredientRepository $OrderIngredientRepository
      , protected DailyIngredientRepository $DailyIngredientRepository
    )
    {
        $this->repository = $OrderIngredientRepository;
    }

    public function getStaticsByRequiredDate($required_date, $force_update = 0)
    {
        return (new OrderDailyRequisitionRepository)->getStatisticsByDate($required_date, $force_update);
    }

    public function exportMatrixList($post_data = [], $debug = 0)
    {
        return (new DailyIngredientRepository)->exportMatrixList($post_data);
    }
}
