<?php

namespace App\Repositories\Eloquent\Sale;

use App\Traits\EloquentTrait;

class OrderProductIngredientDailyRepository
{
    use EloquentTrait;

    public $modelName = "\App\Models\Sale\OrderProductIngredientDaily";


    public function getDailyRequisitions($params, $debug = 0)
    {
        $rows = $this->getRows($params, $debug);

        return $rows;
    }







}

