<?php

namespace App\Domains\Admin\Services\Inventory;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Repositories\Eloquent\Inventory\ReceivingOrderRepository;
use App\Repositories\Eloquent\Catalog\ProductRepository;
use App\Repositories\Eloquent\Inventory\UnitRepository;
use App\Repositories\Eloquent\Inventory\ReceivingOrderProductRepository;
use App\Helpers\Classes\UnitConverter;
use App\Models\Inventory\BomProduct;
use App\Models\Catalog\Product;
use Carbon\Carbon;
use App\Helpers\Classes\OrmHelper;

class ReceivingOrderService extends Service
{
    protected $modelName = "\App\Models\Inventory\ReceivingOrder";

    public function __construct(protected ReceivingOrderRepository $ReceivingOrderRepository, private ProductRepository $ProductRepository
    , private UnitRepository $UnitRepository
    , private ReceivingOrderProductRepository $ReceivingOrderProductRepository)
    {
        $this->repository = $ReceivingOrderRepository;
    }
    
    public function getReceivingOrders($data=[], $debug=0)
    {
        $data = $this->resetQueryData($data);

        if(!empty($data['filter_product_name'])){
            $data['whereHas'] = ['receivingOrderProducts' => ['product_name' => $data['filter_product_name']]];
            unset($data['filter_product_name']);
        }

        $rows = $this->getRows($data, $debug);

        return $this->unsetRelations($rows, ['status']);
    }

    public function findIdOrFailOrNew($id, $params = null, $debug = 0)
    {
        //find
        if(!empty(trim($id))){
            $params['equal_id'] = $id;
            $row = $this->getRow($params, $debug);

            if(empty($row)){
                throw new \Exception ('Record not found!');
            }
        }
        //new
        else{
            $row = $this->newModel();
        }

        return $row;
    }

    //進貨作業
    public function saveReceivingOrder($data)
    {
        try {
            DB::beginTransaction();

            $receiving_order_id = $data['receiving_order_id'] ?? null;

            $receiving_order = $this->findIdOrFailOrNew($receiving_order_id);

            $receiving_order->form_type_code = $data['form_type_code'] ?? null;
            $receiving_order->location_id = $data['location_id'] ?? 0;
            $receiving_order->receiving_date = $data['receiving_date']; //不可以是空值
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

                    if (empty($db_coded_products[$product_id]['usage_unit_code'])){
                        throw new \Exception('用量單位不能是空值');
                    }

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
                $this->ReceivingOrderProductRepository->upsert($update_receiving_products,['id']);
                }

                if($data['form_type_code'] != 'EXP'){ //費用類不回寫
                    $products = [];
                    foreach ($update_receiving_products ?? [] as $row) {
                        foreach ($now_receiving_order as $nowData){
                            // 將進貨單價更新到料件資料表 product
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
                            // 
                            
                            // 將進貨單價更新到 bom 表 bom_products
                            $query = BomProduct::query();
                            $bomProducts =    $query->where('sub_product_id', $product_id)
                                                    ->where(function($qry) use ($data) {
                                                        $qry->whereDate('updated_at', '<', Carbon::parse($data['receiving_date'])->toDateString())
                                                            ->orWhereNull('updated_at');
                                                    })
                                                    ->get();
                            // OrmHelper::showSqlContent($bomProducts);

                            foreach ($bomProducts as $bomProduct) {
                                $bomProduct->price = $db_coded_products[$product_id]['usage_price'] ?? 0;
                                $bomProduct->amount = $bomProduct->quantity * $bomProduct->price; //暫不考慮損耗
                                $bomProduct->save();
                            }
                        }
                    }

                    if(!empty($products)){
                        Product::upsert($products, ['product_id']);
                    }
                }

                // 將進貨單價更新到 bom 表 bom_products
                if($data['form_type_code'] != 'EXP'){ //費用類不回寫
                }


            }else{
                // Deleta receiving_products
                $this->ReceivingOrderProductRepository->deleteByReceivingOrderById($receiving_order->id);
            }

            $result['data'] = [
                'receiving_order_id' => $receiving_order->id,
                'code' => $receiving_order->code
            ];

            DB::commit();

            return $result;


        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    public function getProducts($filter_data)
    {
        $query = Product::query();

        $query->with(['productUnits']);

        OrmHelper::applyFilters($query, $filter_data);
        $products = OrmHelper::getResult($query, $filter_data);

        return $products;
    }
}
