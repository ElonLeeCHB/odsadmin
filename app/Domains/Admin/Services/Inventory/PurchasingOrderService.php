<?php

namespace App\Domains\Admin\Services\Inventory;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Repositories\Eloquent\Inventory\PurchasingOrderRepository;

class PurchasingOrderService extends Service
{
    protected $modelName = "\App\Models\Inventory\PurchasingOrder";

    public function __construct(protected PurchasingOrderRepository $PurchasingOrderRepository)
    {}


    public function getPurchasingOrders($data=[], $debug=0)
    {
        return $this->PurchasingOrderRepository->getPurchasingOrders($data, $debug);
    }




    public function updateOrCreate($data)
    {
        DB::beginTransaction();
        
        try {
            $purchasing_order_id = $data['purchasing_order_id'] ?? null;

            $purchasing_order = $this->findIdOrFailOrNew($purchasing_order_id);

            $purchasing_order->code = $this->PurchasingOrderRepository->getYmSnCode($this->modelName);

            $purchasing_order->location_id = $data['location_id'] ?? 0;
            $purchasing_order->purchasing_date = $data['purchasing_date'] ?? null;
            $purchasing_order->receiving_date = $data['receiving_date'] ?? null;
            $purchasing_order->supplier_id = $data['supplier_id'] ?? 0;
            $purchasing_order->supplier_name = $data['supplier_name'] ?? null;
            $purchasing_order->tax_id_num = $data['tax_id_num'] ?? null;
            $purchasing_order->before_tax = $data['before_tax'] ?? 0;
            $purchasing_order->tax = $data['tax'] ?? 0;
            $purchasing_order->after_tax = $data['after_tax'] ?? 0;
            $purchasing_order->status_code = $data['status_code'] ?? null;

            $purchasing_order->save();

            DB::commit();

            $result['data'] = [
                'purchasing_order_id' => $purchasing_order->id,
                'code' => $purchasing_order->code
            ];
            return $result;

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }



    public function getActivePurchasingOrderStatuses()
    {
        return $this->PurchasingOrderRepository->getActivePurchasingOrderStatuses();
    }
}
