<?php

namespace App\Domains\Admin\Services\Inventory;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Services\Inventory\GlobalReceivingOrderService;
use App\Repositories\Eloquent\Inventory\ReceivingOrderRepository;
use App\Repositories\Eloquent\Inventory\ReceivingOrderProductRepository;
use App\Repositories\Eloquent\Common\TermRepository;

class ReceivingOrderService extends Service
{
    protected $modelName = "\App\Models\Inventory\ReceivingOrder";

    public function __construct(protected ReceivingOrderRepository $ReceivingOrderRepository
    , protected ReceivingOrderProductRepository $ReceivingOrderProductRepository
    , protected TermRepository $TermRepository
    
    )
    {}


    public function getReceivingOrders($data=[], $debug=0)
    {
        return $this->ReceivingOrderRepository->getReceivingOrders($data, $debug);
    }


    public function updateOrCreate($data)
    {
        DB::beginTransaction();
        
        try {
            $receiving_order_id = $data['receiving_order_id'] ?? null;

            $receiving_order = $this->findIdOrFailOrNew($receiving_order_id);

            $receiving_order->code = $this->ReceivingOrderRepository->getYmSnCode($this->modelName);

            $receiving_order->location_id = $data['location_id'] ?? 0;
            $receiving_order->receiving_date = $data['receiving_date'] ?? null;
            $receiving_order->supplier_id = $data['supplier_id'] ?? 0;
            $receiving_order->supplier_name = $data['supplier_name'] ?? null;
            $receiving_order->tax_id_num = $data['tax_id_num'] ?? null;
            $receiving_order->before_tax = $data['before_tax'] ?? 0;
            $receiving_order->tax = $data['tax'] ?? 0;
            $receiving_order->after_tax = $data['after_tax'] ?? 0;
            $receiving_order->status_code = $data['status_code'] ?? null;
            $receiving_order->tax_type_code = $data['tax_type_code'] ?? null;

            $receiving_order->save();

            // receiving_products
            if(!empty($data['products'])){
                // Deleta receiving_products
                $this->ReceivingOrderProductRepository->deleteByReceivingOrderId($receiving_order->id);

                $sort_order = 1;
                $new_sort_order = 100; //前端只允許2位數，到99。這裡從100開始。

                //若無商品代號，則 unset()
                foreach ($data['products'] as $key => $fm_receiving_product) {
                    if(empty($fm_receiving_product['id']) || !is_numeric($fm_receiving_product['id'])){
                        unset($data['products'][$key]);
                        continue;
                    }

                    //若無排序則設定預設排序
                    if(empty($fm_receiving_product['sort_order'])){
                        $data['products'][$key]['sort_order'] = $new_sort_order;
                    }
                    $new_sort_order++;
                }

                //按照 sort_order 排序
                usort($data['products'], fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);

                //依照當前剛才整理後的陣列順序，重新設定排序欄位 sort_order , 從1遞增，並且不會重覆。
                $sort_order = 1;
                foreach ($data['products'] as $key => $fm_receiving_product) {
                    $data['products'][$key]['sort_order'] = $sort_order;
                    $sort_order++;
                }

                $sort_order = 1;
                $update_receiving_products = [];
                foreach ($data['products'] as $key => $fm_receiving_product) {
                    $product_id = $fm_receiving_product['id'];

                    if(!empty($fm_receiving_product['receiving_unit_code'])){
                        $arr = explode('_', $fm_receiving_product['receiving_unit_code']);
                        if(!empty($arr)){
                            $receiving_unit_code = $arr[0];
                            $receiving_unit_name = $arr[1];
                        }
                    }

                    $price = str_replace(',', '', $fm_receiving_product['price']);
                    if(empty($price)){
                        $price = 0;
                    }

                    $total = str_replace(',', '', $fm_receiving_product['total']);
                    if(empty($total)){
                        $total = 0;
                    }

                    $stock_quantity = str_replace(',', '', $fm_receiving_product['stock_quantity']);
                    if(empty($stock_quantity)){
                        $stock_quantity = 0;
                    }

                    $stock_price = str_replace(',', '', $fm_receiving_product['stock_price']);
                    if(empty($stock_price)){
                        $stock_price = 0;
                    }

                    

                    $row = [
                        'id' => $fm_receiving_product['receiving_product_id'] ?? null,
                        'receiving_order_id' => $receiving_order->id,
                        'product_id' => $product_id,
                        'product_name' => $fm_receiving_product['name'],
                        'product_specification' => $fm_receiving_product['specification'] ?? '',

                        'receiving_unit_code' => $receiving_unit_code ?? '',
                        'receiving_unit_name' => $receiving_unit_name ?? '',
                        'receiving_quantity' => str_replace(',', '', $fm_receiving_product['receiving_quantity']),

                        'stock_unit_code' => $fm_receiving_product['stock_unit_code'] ?? '',
                        'stock_unit_name' => $fm_receiving_product['stock_unit_name'] ?? '',
                        'stock_quantity' => $stock_quantity,
                        'stock_price' => $stock_quantity,
                        'price' => $price,
                        'total' => $total,
                        
                        'comment' => $fm_receiving_product['comment'] ?? '',
                        'sort_order' => $fm_receiving_product['sort_order'], //此時 sort_order 必定是從1遞增
                    ];

                    // if(!empty($fm_receiving_product['order_product_id'])){
                    //     $update_receiving_product['id'] = $fm_receiving_product['order_product_id'];
                    // }

                    $update_receiving_products[$sort_order] = $row;
                    $sort_order++;
                }

                //Upsert
                if(!empty($update_receiving_products)){
                    $this->ReceivingOrderProductRepository->upsert($update_receiving_products,['id']);
                    unset($update_receiving_products);
                }


            }
           // echo '<pre>', print_r($update_receiving_products, 1), "</pre>"; exit;




            DB::commit();

            $result['data'] = [
                'receiving_order_id' => $receiving_order->id,
                'code' => $receiving_order->code
            ];
            return $result;

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }



    public function getCachedActiveReceivingOrderStatuses($reset = false)
    {
        return $this->ReceivingOrderRepository->getCachedActiveReceivingOrderStatuses($reset);
    }

    public function getActiveTaxTypesIndexByCode()
    {
        $filter_data = [
            'equal_taxonomy_code' => 'tax_type',
            'pagination' => false,
            'limit' => 0,
            'sort' => 'code',
            'order' => 'ASC',
        ];
        
        $tax_types = $this->TermRepository->getTerms($filter_data)->toArray();

        foreach ($tax_types as $key => $tax_type) {
            unset($tax_type['translation']);
            unset($tax_type['taxonomy']);
            $tax_type_code = $tax_type['code'];
            $new_tax_types[$tax_type_code] = (object) $tax_type;
        }

        return $new_tax_types;
    }
}
