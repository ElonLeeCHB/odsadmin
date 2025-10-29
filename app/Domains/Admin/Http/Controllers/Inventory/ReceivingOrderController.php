<?php

namespace App\Domains\Admin\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Domains\Admin\Services\Inventory\ReceivingOrderService;
use App\Repositories\Eloquent\Inventory\UnitRepository;
use App\Repositories\Eloquent\Inventory\ReceivingOrderProductRepository;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Models\Sale\Location;
use App\Models\Localization\Language;
use App\Models\Catalog\Product;
use App\Repositories\Eloquent\Catalog\ProductRepository;
use Illuminate\Support\Facades\DB;
use App\Helpers\Classes\UnitConverter;

class ReceivingOrderController extends BackendController
{
    public function __construct(
        private Request $request
        , private ReceivingOrderService $ReceivingOrderService
        , private UnitRepository $UnitRepository
        , private TermRepository $TermRepository
        , private ReceivingOrderProductRepository $ReceivingOrderProductRepository
        , private ProductRepository $ProductRepository
    )
    {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/inventory/receiving']);
    }


    public function index()
    {
        $data['lang'] = $this->lang;

        // Breadcomb
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_home,
            'href' => route('lang.admin.dashboard'),
        ];
        
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_inventory,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];
        
        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.inventory.receivings.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        // tax_types
        $data['tax_types'] = [];

        // statuses
        $data['statuses'] = $this->ReceivingOrderService->getCodeKeyedTermsByTaxonomyCode('common_form_status',toArray:false);

        // 單別
        $data['form_types'] = $this->ReceivingOrderService->getCodeKeyedTermsByTaxonomyCode('receiving_order_form_type',toArray:false);

        $data['list'] = $this->getList();

        $data['list_url']   =  route('lang.admin.inventory.receivings.list');
        $data['add_url']    = route('lang.admin.inventory.receivings.form');
        $data['delete_url'] = route('lang.admin.inventory.receivings.delete');
        $data['export01_url'] = route('lang.admin.inventory.receivings.export01');
        // dd($data);
        return view('admin.inventory.receiving_order', $data);
    }


    public function list()
    {
        return $this->getList();
    }


    private function getList()
    {
        $data['lang'] = $this->lang;


        // Prepare query_data for records
        $query_data  = $this->url_data;

        // Rows
        $receiving_orders = $this->ReceivingOrderService->getReceivingOrders($query_data);

        if(!empty($receiving_orders)){
            foreach ($receiving_orders as $row) {
                $row->edit_url = route('lang.admin.inventory.receivings.form', array_merge([$row->id], $query_data));
            }
        }

        $data['receiving_orders'] = $receiving_orders->withPath(route('lang.admin.inventory.receivings.list'))->appends($query_data);
        
        // Prepare links for list table's header
        if(isset($query_data['order']) && $query_data['order'] == 'ASC'){
            $order = 'DESC';
        }else{
            $order = 'ASC';
        }
        
        $data['sort'] = strtolower($query_data['sort'] ?? '');
        $data['order'] = strtolower($order);

        $query_data = $this->unsetUrlQueryData($query_data);

        $url = '';

        foreach($query_data as $key => $value){
            if(is_string($value)){
                $url .= "&$key=$value";
            }
        }
        
        
        // link of table header for sorting        
        $route = route('lang.admin.inventory.receivings.list');

        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
        $data['sort_code'] = $route . "?sort=code&order=$order" .$url;
        $data['sort_receiving_date'] = $route . "?sort=receiving_date&order=$order" .$url;
        $data['sort_receiving_date'] = $route . "?sort=receiving_date&order=$order" .$url;

        $data['list_url'] = route('lang.admin.inventory.receivings.list');

        return view('admin.inventory.receiving_order_list', $data);
    }


    public function form($receiving_order_id = null)
    {
        $data['lang'] = $this->lang;

        // Languages
        $data['languages'] = Language::active()->get();


        // Breadcomb
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_home,
            'href' => route('lang.admin.dashboard'),
        ];

        $breadcumbs[] = (object)[
            'text' => $this->lang->text_inventory,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];

        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.inventory.receivings.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;


        // Prepare link for save, back
        $query_data  = $this->url_data;

        $data['save_url'] = route('lang.admin.inventory.receivings.save', $receiving_order_id);
        $data['back_url'] = route('lang.admin.inventory.receivings.index', $query_data);
        // $data['product_autocomplete_url'] = route('lang.admin.inventory.products.autocomplete');
        $data['product_autocomplete_url'] = route('lang.admin.inventory.receivings.searchProduct');
        $data['status_save_url'] = route('lang.admin.inventory.receivings.saveStatusCode');
        

        // Get Record
        $receivingOrder = $this->ReceivingOrderService->findIdOrFailOrNew($receiving_order_id);

        if(empty($receivingOrder->receiving_date)){
            $receivingOrder->receiving_date = date('Y-m-d');
        }

        if(empty($receivingOrder->status_code)){
            $receivingOrder->status_code = 'P';
        }

        $data['receiving_order'] = $receivingOrder->toCleanObject();

        if(!empty($receivingOrder) && $receiving_order_id == $receivingOrder->id){
            $data['receiving_order_id'] = $receiving_order_id;
        }else{
            $data['receiving_order_id'] = null;
        }

        // location
        if(empty($receiving_order->location_id)){
            $data['location_id'] = 2;
            $data['location_name'] = '中華一餅和平門市';
        }else{
            $data['location_id'] = $receivingOrder->location_id;
            $data['location_name'] = $receivingOrder->location->name;
        }
        $data['locations'] = Location::active()->get();

        // supplier
        $data['supplier_autocomplete_url'] = route('lang.admin.counterparty.suppliers.autocomplete');

        // statuses
        $data['statuses'] = $this->ReceivingOrderService->getCodeKeyedTermsByTaxonomyCode('common_form_status',toArray:false);

        // receiving_products
        if (!empty($receivingOrder)) {
            $receivingOrder->load([
                'receivingOrderProducts' => function ($query) {
                    $query->with(['product','productUnits']);
                }
            ]);
        }

        foreach ($receivingOrder->receivingOrderProducts as $receivingOrderProduct) {
            $receivingOrderProduct->average_stock_price = round($receivingOrderProduct->product->average_stock_price, 2);
            $receivingOrderProduct->product_edit_url = route('lang.admin.inventory.products.form', $receivingOrderProduct->product_id);
        }
        $data['receiving_products'] = $receivingOrder->receivingOrderProducts;
        
        // units
        $filter_data = [
            'filter_keyword' => $this->request->filter_keyword,
            'pagination' => false,
        ];
        $data['units'] = $this->UnitRepository->getCodeKeyedActiveUnits($filter_data);

        // 稅別
        $data['tax_types'] = $this->ReceivingOrderService->getCodeKeyedTermsByTaxonomyCode('tax_type',toArray:false);
        $data['invoice_types'] = [
            1 => [
                'id' => 1369,
                'code' => '1',
                'is_active' => 1,
                'name' => '1:發票'
            ],
           2 => [
                'id' => 1370,
                'code' => '2',
                'is_active' => 1,
                'name' => '2:收據'
            ],
           3=> [
                'id' => 1371,
                'code' => '3',
                'is_active' => 1,
                'name' => '3:進貨單'
            ]
        ];
        if(empty(($data['receiving_order']->invoice_type))){
            $data['receiving_order']->invoice_type = 0;
        }
        if(empty(($data['receiving_order']->invoice_num))){
            $data['receiving_order']->invoice_num = '';
        }
        return view('admin.inventory.receiving_order_form', $data);
    }


    public function save($id = null)
    {
        try {
            $post_data = $this->request->post();
    
            $json = [];
            // * 檢查欄位
    
            $params = [
                'equal_id' => $id,
                'select' => ['id', 'status_code'],
            ];
            $result = $this->ReceivingOrderService->findIdOrFailOrNew($id, $params);
    
            if(!empty($result['data'])){
                $receiving = $result['data'];
            }else if(!empty($result['error'])){
                return response(json_encode(['error' => $result['error']]))->header('Content-Type','application/json');
            }
            unset($result);
    
    
            if(! (empty($receiving->status_code) || $receiving->status_code == 'P') ){
                $json['error']['status_code'] = '單據未確認才可修改。現在是 "' . $receiving->status_code. '-' . $receiving->status_name . '"';
                $json['error']['warning'] = '單據未確認才可修改。現在是 ' . $receiving->status_code. '-' . $receiving->status_name . '"';
            }
    
            if(empty($post_data['supplier_id'])){
                $json['error']['supplier_id'] = '請選擇廠商';
            }
    
            if(empty($post_data['form_type_code'])){
                $json['error']['form_type_code'] = '請選擇單別';
            }
            
            if(empty($post_data['tax_type_code'])){
                $json['error']['tax_type_code'] = '請選擇課稅別';
            }
    
            if(isset($json['error']) && !isset($json['error']['warning'])) {
                $json['error']['warning'] = '請再檢查紅框欄位資料！';
            }
            // end
            
            if(!$json) {
                $result = $this->ReceivingOrderService->saveReceivingOrder($post_data);
    
                $json = [
                    'receiving_order_id' => $result['data']['receiving_order_id'],
                    'code' => $result['data']['code'],
                    'success' => $this->lang->text_success,
                    'redirectUrl' => route('lang.admin.inventory.receivings.form', $result['data']['receiving_order_id']),
                ];

                return response()->json($json, 200);
            }

            return response()->json($json, 400);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }


    public function export01()
    {
        $query_data  = $this->url_data;

        return $this->ReceivingOrderService->export01($query_data);
    }


    public function saveStatusCode()
    {
        $json = [];

        if(auth()->user()->username != 'admin'){
            $json['error'] = $this->lang->error_permission;
        }

        if(!$json){
            $post_data = request()->all();
            $new_data = $post_data['update_status'];
            $result = $this->ReceivingOrderService->saveStatusCode($new_data);
    
            if(!empty($result['data']['id'])){
                $json = [
                    'success' => '狀態已變更為：' . $result['data']['status_name'],
                    'data' => $result['data'],
                ];
            }
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }

    public function getBeforeReceivingStockPrice($product_id){
        $rs = DB::select("
        SELECT stock_price,receiving_order_id FROM ".env('DB_DATABASE').".`receiving_order_products` WHERE product_id = $product_id
        ORDER BY `id` DESC LIMIT 2
        ");
        return $rs[1];
    }

    public function searchProduct()
    {
        $json = [];

        foreach ($this->url_data as $key => $value) {
            //檢查查詢字串
            if(str_starts_with($key, 'filter_') || str_starts_with($key, 'equal_')){
                //檢查輸入字串是否包含注音符號
                if (preg_match('/[\x{3105}-\x{3129}\x{02C7}]+/u', $value)) {
                    $json['error'] = '包含注音符號不允許查詢';
                }
            }
        }

        if(!empty($json)){
            return response(json_encode($json))->header('Content-Type','application/json');
        }

        $products = $this->ReceivingOrderService->getProducts($this->url_data);

        foreach ($products ?? [] as $product) {

            $new_row = array(
                'label' => $product->id . ' ' . $product->name . ' ' . $product->specification,
                'value' => $product->id,
                'product_id' => $product->id,
                'name' => $product->name,
                'specification' => $product->specification,
                'stock_unit_code' => $product->stock_unit_code,
                'stock_unit_name' => $product->stock_unit_name,
                'usage_unit_code' => $product->usage_unit_code,
                'usage_unit_name' => $product->usage_unit_name,
                'usage_price' => $product->usage_price,
                'stock_price' => $product->stock_price ?? 0,
                'average_stock_price' => $product->average_stock_price,
                'product_edit_url' => route('lang.admin.inventory.products.form', $product->id),
            );

            if(!empty($product->productUnits)){
                $product_units = $product->productUnits->keyBy('source_unit_code')->toArray();

                data_forget($product_units, '*.source_unit');
                data_forget($product_units, '*.destination_unit');

                $new_row['product_units'] = $product_units;
            }

            if(empty($new_row['product_units'][$product->stock_unit_code])){
                $new_row['product_units'][$product->stock_unit_code] = [
                    'source_unit_name' => $product->stock_unit_name,
                    'source_unit_code' => $product->stock_unit_code,
                    'source_quantity' => 1,
                    'destination_unit_code' => $product->stock_unit_code,
                    'destination_quantity' => 1,
                    'factor' => 1,
                ];
            }
            
            $json[] = $new_row;
        }

        return response()->json($json);
    }
}