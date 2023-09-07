<?php

namespace App\Domains\Admin\Services\Sale;

use App\Services\Service;
use Illuminate\Support\Facades\DB;

class OrderProductIngredientService extends Service
{
    public $modelName = "\App\Models\Sale\OrderProductIngredientService";

    // public function updateOrCreate($data)
    // {
    //     DB::beginTransaction();

    //     try {
    //         // 儲存主記錄

    //     } catch (\Exception $ex) {
    //         DB::rollback();
    //         return ['error' => $ex->getMessage()];

    //     }
        
    //     return false;
    // }
}