<?php

namespace App\Domains\ApiV2\Services\Sale;

use Illuminate\Support\Facades\DB;
use App\Helpers\Classes\DataHelper;
use App\Services\Service;
use App\Services\Sale\OrderService as GlobalOrderService;
use App\Traits\Model\EloquentTrait;

use App\Models\Sale\OrderTag;
use App\Models\Sale\OrderTotal;
use App\Models\Sale\OrderProductOption;
use App\Models\Catalog\ProductTranslation;

class OrderService extends GlobalOrderService
{
    use EloquentTrait;

    protected $modelName = "\App\Models\Sale\Order";


    public function getSimplelist($filters)
    {
       try {

           $filters['with'] = [];

           $filters['select'] = ['id', 'code', 'personal_name', 'delivery_date'];

           return $this->getRows($filters);

       } catch (\Exception $ex) {
           return ['error' => $ex->getMessage()];
       }
    }


    public function getList($filters)
    {
        return $this->getRows($filters);
    }
}
