<?php

namespace App\Repositories\Eloquent\Inventory;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Models\Common\Term;
use App\Models\Catalog\Product;
use App\Repositories\Eloquent\Inventory\UnitRepository;
use App\Repositories\Eloquent\Inventory\ReceivingOrderProductRepository;
use App\Repositories\Eloquent\Catalog\ProductRepository;

use Maatwebsite\Excel\Facades\Excel;
use App\Domains\Admin\Exports\InventoryReceivingReport;

class ReceivingOrderRepository extends Repository
{
    public $modelName = "\App\Models\Inventory\ReceivingOrder";

    public function __construct(private UnitRepository $UnitRepository
        , private ReceivingOrderProductRepository $ReceivingOrderProductRepository
        , private ProductRepository $ProductRepository
    )
    {}

    public function getReceivingOrders($data=[], $debug=0)
    {
        $data = $this->resetQueryData($data);

        if(!empty($data['filter_product_name'])){
            $data['whereHas'] = ['receiving_products' => ['product_name' => $data['filter_product_name']]];
            unset($data['filter_product_name']);
        }

        $rows = $this->getRows($data, $debug);

        return $this->unsetRelations($rows, ['status']);
    }

    public function saveReceivingOrder($data)
    {
        DB::beginTransaction();

        try {
            $receiving_order_id = $data['receiving_order_id'] ?? null;

            $receiving_order = $this->findIdOrFailOrNew($receiving_order_id);

            $receiving_order->form_type_code = $data['form_type_code'] ?? null;
            $receiving_order->location_id = $data['location_id'] ?? 0;
            $receiving_order->receiving_date = $data['receiving_date'] ?? null;
            $receiving_order->supplier_id = $data['supplier_id'] ?? 0;
            $receiving_order->supplier_name = $data['supplier_name'] ?? null;
            $receiving_order->tax_id_num = $data['tax_id_num'] ?? null;
            $receiving_order->amount = $data['amount'] ?? 0;
            $receiving_order->before_tax = $data['before_tax'] ?? 0;
            $receiving_order->tax_rate = !empty($data['formatted_tax_rate']) ? $data['formatted_tax_rate'] / 100 : 0;
            $receiving_order->tax = $data['tax'] ?? 0;
            $receiving_order->after_tax = $data['after_tax'] ?? 0;
            $receiving_order->total = $data['total'] ?? 0;
            $receiving_order->status_code = $data['status_code'] ?? 'P';
            $receiving_order->tax_type_code = $data['tax_type_code'] ?? null;
            $receiving_order->comment = $data['comment'] ?? null;
            $receiving_order->save();

            // db_products
            $product_ids = [];
            $db_coded_products = [];

            if(!empty($data['products'])){
                $product_ids = array_column($data['products'], 'id');
            
                $params = [
                    'select' => ['id','stock_unit_code','usage_unit_code'],
                    'whereIn' => ['id' => $product_ids],
                    'pagination' => false,
                    'limit' => 0,
                    'keyBy' => 'id',
                    'with' => ['stock_unit','usage_unit'],
                ];
                $db_coded_products = $this->ProductRepository->getProducts($params)->toArray();
            }

            // receiving_products
            if(!empty($data['products'])){

                $units = $this->UnitRepository->getCodeKeyedActiveUnits();

                // Deleta receiving_products
                $this->ReceivingOrderProductRepository->deleteByReceivingOrderById($receiving_order->id);

                $sort_order = 1;
                $new_sort_order = 200;

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
                            $receiving_unit_name = $arr[1] ?? '';
                        }
                    }

                    $stock_quantity = empty($stock_quantity) ? 0 : $stock_quantity;
                    $stock_quantity = number_format((float) str_replace(',', '', $fm_receiving_product['stock_quantity']), 3);

                    $stock_price = empty($stock_price) ? 0 : $stock_price;
                    $stock_price = number_format((float) str_replace(',', '', $fm_receiving_product['stock_price']), 3);

                    $receiving_quantity = 0;
                    if(!empty($fm_receiving_product['receiving_quantity'])){
                        $receiving_quantity = str_replace(',', '', $fm_receiving_product['receiving_quantity']);
                    }

                    //換算用量單位
                    $params = [
                        'product_id' => $product_id,
                        'from_unit_code' => $db_coded_products[$product_id]['stock_unit_code'],
                        'to_unit_code' => $db_coded_products[$product_id]['usage_unit_code'],
                        'from_quantity' => 1,
                    ];
                    $usage_factor = $this->UnitRepository->calculateQty($params);

                    if(!empty($usage_factor['error'])){
                        throw new \Exception($usage_factor['error']);
                    }

                    $db_coded_products[$product_id]['usage_price'] = $stock_price / $usage_factor;

                    $row = [
                        'id' => $fm_receiving_product['receiving_product_id'] ?? null,
                        'receiving_order_id' => $receiving_order->id,
                        'product_id' => $product_id,
                        'product_name' => $fm_receiving_product['name'],
                        'product_specification' => $fm_receiving_product['specification'] ?? '',

                        'receiving_unit_code' => $receiving_unit_code ?? '',
                        'receiving_unit_name' => $units[$receiving_unit_code]->name,
                        'receiving_quantity' => $receiving_quantity,

                        'stock_unit_code' => $fm_receiving_product['stock_unit_code'] ?? '',
                        'stock_unit_name' => $fm_receiving_product['stock_unit_name'] ?? '',
                        'stock_quantity' => $fm_receiving_product['stock_quantity'],
                        'stock_price' => $fm_receiving_product['stock_price'],
                        'price' => $fm_receiving_product['price'],
                        'amount' => $fm_receiving_product['amount'],
                        
                        'comment' => $fm_receiving_product['comment'] ?? '',
                        'sort_order' => $fm_receiving_product['sort_order'], //此時 sort_order 必定是從1遞增
                    ];

                    $update_receiving_products[$sort_order] = $row;
                    $sort_order++;
                    
                }
                //Upsert
                if(!empty($update_receiving_products)){
                    $this->ReceivingOrderProductRepository->upsert($update_receiving_products,['id']);
                }
            }

            // 將進貨單價回寫料件資料表
            if($data['form_type_code'] != 'EXP'){ //費用類不回寫
                $products = [];
                foreach ($update_receiving_products ?? [] as $row) {
                    $product_id = $row['product_id'];
                    $products[$product_id]['id'] = $row['product_id'];
                    $products[$product_id]['stock_price'] = $row['stock_price'] ?? 0;
                    $products[$product_id]['usage_price'] = $db_coded_products[$product_id]['usage_price'] ?? 0;
                }
    
                if(!empty($products)){
                    Product::upsert($products, ['product_id']);
                }
            }

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


    public function resetQueryData($data)
    {
        // 採購日
        if(!empty($data['filter_receiving_date'])){
            $rawSql = $this->parseDateToSqlWhere('receiving_date', $data['filter_receiving_date']);
            if($rawSql){
                $data['whereRawSqls'][] = $rawSql;
            }
            unset($data['filter_receiving_date']);
        }

        // 收貨日
        if(!empty($data['filter_receiving_date'])){
            $rawSql = $this->parseDateToSqlWhere('receiving_date', $data['filter_receiving_date']);
            if($rawSql){
                $data['whereRawSqls'][] = $rawSql;
            }
            unset($data['filter_receiving_date']);
        }

        // 狀態
        if(!empty($data['filter_status_code']) && $data['filter_status_code'] == 'withoutV'){
            $data['whereNotIn'] = ['status_code' => ['V']];
            unset($data['filter_status_code']);
        }
        
        return $data;
    }


    // 尋找關聯，並將關聯值賦予記錄
    public function optimizeRow($row)
    {
        if(!empty($row->status)){
            $row->status_name = $row->status->name;
        }

        return $row;
    }


    // 刪除關聯
    public function sanitizeRow($row)
    {
        $arrOrder = $row->toArray();

        if(!empty($arrOrder['status'])){
            unset($arrOrder['status']);
        }

        return (object) $arrOrder;
    }

    public function getReceivingOrderStatuses($data = [])
    {
        $query = Term::where('taxonomy_code', 'receiving_order_status');

        if(!empty($data['equal_is_active'])){
            $query->where('is_active', 1);
        }

        $rows = $query->get()->toArray();

        $new_rows = [];

        foreach ($rows as $key => $row) {
            unset($row['translation']);
            unset($row['taxonomy']);
            $code = $row['code'];
            $new_rows[$code] = (object) $row;
        }

        return $new_rows;
    }


    public function getCachedActiveReceivingOrderStatuses($reset = false)
    {
        $cachedStatusesName = app()->getLocale() . '_receiving_order_statuses';

        // 不重設
        if($reset == false){
            $statuses = cache()->get($cachedStatusesName);

            if(!empty($statuses)){
                return $statuses;
            }
        }


        // 重設
        $filter_data = [
            'equal_is_active' => true,
        ];

        $statuses = $this->getReceivingOrderStatuses($filter_data);
        
        cache()->forget($cachedStatusesName);
        cache()->put($cachedStatusesName, $statuses, $seconds = 60*60*24*90);

        return $statuses;
    }


    public function export01($post_data = [], $debug = 0)
    {
        $filename = '進貨報表_'.date('Y-m-d_H-i-s').'.xlsx';

        return Excel::download(new InventoryReceivingReport($post_data, $this, new ReceivingOrderProductRepository), $filename);
    }
}
