<?php

namespace App\Repositories\Eloquent\Inventory;

use Illuminate\Support\Facades\DB;
use App\Helpers\Classes\UnitConverter;
use App\Repositories\Eloquent\Repository;
use App\Models\Common\Term;
use App\Models\Material\Product;
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
    //進貨作業
    public function saveReceivingOrder($data)
    {
        try {
            DB::beginTransaction();

            $receiving_order_id = $data['receiving_order_id'] ?? null;

            $result = $this->findIdOrFailOrNew($receiving_order_id);
            if(!empty($result['data'])){
                //修改情境
                $receiving_order = $result['data'];
            }else if($result['error']){
                throw new \Exception($result['error']);
            }
            unset($result);

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
            $receiving_order->invoice_num = $data['invoice_num'] ?? null;
            $receiving_order->invoice_type = $data['invoice_type'] ?? null;
            $receiving_order->save();

            $product_ids = [];
            $db_coded_products = [];
            if(!empty($data['products'])){

                // db_products

                $product_ids = array_column($data['products'], 'id');

                $params = [
                    'select' => ['id','stock_unit_code','usage_unit_code','quantity','receiving_product_id'],
                    'whereIn' => ['id' => $product_ids],
                    'pagination' => false,
                    'limit' => 0,
                    'keyBy' => 'id',
                    'with' => ['stock_unit','usage_unit'],
                ];
                $db_coded_products = $this->ProductRepository->getProducts($params)->toArray();
                $units = $this->UnitRepository->getCodeKeyedActiveUnits();
                $now_receiving_order = $this->ReceivingOrderProductRepository->getReceivingOrderById($receiving_order->id);
                // Delete receiving_products
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
                    // 取得product.quantity和 receiving_order_products的歷史紀錄
                    $stock_quantity = empty($fm_receiving_product['stock_quantity']) ? 0 : $fm_receiving_product['stock_quantity'];
                    $stock_quantity = str_replace(',', '', $stock_quantity);
                    $stock_price = empty($fm_receiving_product['stock_price']) ? 0 : $fm_receiving_product['stock_price'];
                    $stock_price = str_replace(',', '', $stock_price);

                    $receiving_quantity = empty($fm_receiving_product['receiving_quantity']) ? 0 : $fm_receiving_product['receiving_quantity'];
                    $receiving_quantity = str_replace(',', '', $receiving_quantity);

                    $price = empty($fm_receiving_product['price']) ? 0 : $fm_receiving_product['price'];
                    $price = str_replace(',', '', $price);

                    $amount = empty($fm_receiving_product['amount']) ? 0 : $fm_receiving_product['amount'];
                    $amount = str_replace(',', '', $amount);

                    //換算用量單位
                    $usage_factor = UnitConverter::build()->qty(1)
                                        ->from($db_coded_products[$product_id]['stock_unit_code'])
                                        ->to($db_coded_products[$product_id]['usage_unit_code'])
                                        ->product($product_id)
                                        ->get();
                    if(!empty($usage_factor['error'])){
                        throw new \Exception($usage_factor['error']);
                    }
                    if($usage_factor == 0){
                        throw new \Exception("換算單位異常");
                    }
                            //fucking shit
                            if ($data['tax_type_code']==1){
                                $db_coded_products[$product_id]['usage_price'] = $stock_price / $usage_factor / 1.05 ;
                            }else{
                                $db_coded_products[$product_id]['usage_price'] = $stock_price / $usage_factor;
                            }

                    // $db_coded_products[$product_id]['usage_price'] = $stock_price / $usage_factor;
                    $row = [
                        'id' => $fm_receiving_product['receiving_product_id'] ?? null,
                        'receiving_order_id' => $receiving_order->id,
                        'product_id' => $product_id,
                        'product_name' => $fm_receiving_product['name'],
                        'product_specification' => $fm_receiving_product['specification'] ?? '',

                        'receiving_unit_code' => $receiving_unit_code ?? '',
                        'receiving_unit_name' => $units[$receiving_unit_code]->name,
                        'receiving_quantity' => $receiving_quantity,
                        'price' => $price,
                        'amount' => $amount,

                        'stock_unit_code' => $fm_receiving_product['stock_unit_code'] ?? '',
                        'stock_unit_name' => $fm_receiving_product['stock_unit_name'] ?? '',
                        'stock_quantity' => $stock_quantity,
                        'stock_price' => $stock_price,

                        'comment' => $fm_receiving_product['comment'] ?? '',
                        'sort_order' => $fm_receiving_product['sort_order'], //此時 sort_order 必定是從1遞增
                    ];

                    $update_receiving_products[$sort_order] = $row;
                    $sort_order++;

                }
                //Upsert  receiving_order_products
                if(!empty($update_receiving_products)){
                   $upsert= $this->ReceivingOrderProductRepository->upsert($update_receiving_products,['id']);
                }
                // 將進貨單價回寫料件資料表product
                if($data['form_type_code'] != 'EXP'){ //費用類不回寫
                    $products = [];
                    foreach ($update_receiving_products ?? [] as $row) {
                        foreach ($now_receiving_order as $nowData){
                            $product_id = $row['product_id'];
                            //比對這筆進貨資料是否已經增加至product的數量 以product的receiving_product_id判斷   $nowData['stock_quantity'] == $row['stock_quantity'] 
                            if($row['receiving_order_id']!=$db_coded_products[$product_id]['receiving_product_id']){
                                $products[$product_id]['quantity'] = $row['stock_quantity'] + $db_coded_products[$product_id]['quantity'];
                            }else{
                                $products[$product_id]['quantity'] = $db_coded_products[$product_id]['quantity'];
                            }
                            $products[$product_id]['receiving_product_id'] = $row['receiving_order_id'];
                            $products[$product_id]['id'] = $row['product_id'];
                            // $products[$product_id]['quantity'] = $row['stock_quantity'];
                            $products[$product_id]['stock_price'] = $row['stock_price'] ?? 0;
                            $products[$product_id]['usage_price'] = $db_coded_products[$product_id]['usage_price'] ?? 0;
                        }
                    }
                    if(!empty($products)){

                        // 其它沒處理到的資料
                        foreach ($products as $key =>$product) {
                            $products[$key] = (new Product)->prepareArrayData($product);
                        }

                        Product::upsert($products, ['product_id']);
                    }
                }


            }else{

                // Deleta receiving_products
                $this->ReceivingOrderProductRepository->deleteByReceivingOrderById($receiving_order->id);
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


    public function export01($post_data = [], $debug = 0)
    {
        $filename = '進貨報表_'.date('Y-m-d_H-i-s').'.xlsx';

        return Excel::download(new InventoryReceivingReport($post_data, $this, new ReceivingOrderProductRepository), $filename);
    }
}
