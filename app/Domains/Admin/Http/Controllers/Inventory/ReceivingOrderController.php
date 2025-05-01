<?php

namespace App\Domains\Admin\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Domains\Admin\Services\Inventory\ReceivingOrderService;
use App\Repositories\Eloquent\Inventory\UnitRepository;
use App\Repositories\Eloquent\Inventory\ReceivingOrderProductRepository;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Models\Setting\Location;
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
            'text' => $this->lang->text_menu_inventory,
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
            'text' => $this->lang->text_menu_inventory,
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
        $data['product_autocomplete_url'] = route('lang.admin.inventory.products.autocomplete');
        $data['status_save_url'] = route('lang.admin.inventory.receivings.saveStatusCode');
        

        // Get Record
        $receiving_order = $this->ReceivingOrderService->findIdOrFailOrNew($receiving_order_id);

        if(empty($receiving_order->receiving_date)){
            $receiving_order->receiving_date = date('Y-m-d');
        }

        if(empty($receiving_order->status_code)){
            $receiving_order->status_code = 'P';
        }

        $data['receiving_order'] = $receiving_order->toCleanObject();

        if(!empty($receiving_order) && $receiving_order_id == $receiving_order->id){
            $data['receiving_order_id'] = $receiving_order_id;
        }else{
            $data['receiving_order_id'] = null;
        }

        // location
        if(empty($receiving_order->location_id)){
            $data['location_id'] = 2;
            $data['location_name'] = '中華一餅和平門市';
        }else{
            $data['location_id'] = $receiving_order->location_id;
            $data['location_name'] = $receiving_order->location->name;
        }
        $data['locations'] = Location::active()->get();

        // supplier
        $data['supplier_autocomplete_url'] = route('lang.admin.counterparty.suppliers.autocomplete');

        // statuses
        $data['statuses'] = $this->ReceivingOrderService->getCodeKeyedTermsByTaxonomyCode('common_form_status',toArray:false);

        // receiving_products
        if(!empty($receiving_order)){
            $receiving_order->load('receiving_products.product_units');
        }

        $data['receiving_products'] = $receiving_order->receiving_products;

        foreach ($data['receiving_products']  as $key => $receiving_product) {
            $data['receiving_products'][$key]->product_edit_url = route('lang.admin.inventory.products.form', $receiving_product->product_id);
        }

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


    public function save()
    {
        try {
            $old_receiving_order = null;
    
            if (!$this->post_data['receiving_order_id']) {
                $old_receiving_order = $this->ReceivingOrderService->findOrFail($this->post_data['receiving_order_id']);
            }
    
            $json = [];
    
            // 檢查表單
                if(! (empty($old_receiving_order->status_code) || $old_receiving_order->status_code == 'P') ){
                    $json['errors']['status_code'] = '單據未確認才可修改。現在是 "' . $old_receiving_order->status_code. '-' . $old_receiving_order->status_name . '"';
                }
    
                if(empty($this->post_data['supplier_id'])){
                    $json['errors']['supplier_id'] = '請選擇廠商';
                }
    
                if(empty($this->post_data['form_type_code'])){
                    $json['errors']['form_type_code'] = '請選擇單別';
                }
                
                if(empty($this->post_data['tax_type_code'])){
                    $json['errors']['tax_type_code'] = '請選擇課稅別';
                }
    
                if(isset($json['errors']) && !isset($json['errors']['warning'])) {
                    $json['errors']['warning'] = '請再檢查資料！';
                }
            //
    
            if (empty($json)){
                if (!$this->post_data['receiving_order_id']) {
                    $receiving_order = $this->ReceivingOrderService->addReceivingOrder($this->post_data);
                } else {
                    $receiving_order = $this->ReceivingOrderService->editReceivingOrder($this->post_data['receiving_order_id'], $this->post_data);
                }

                $json = [
                    'receiving_order_id' => $receiving_order->id,
                    'success' => $this->lang->text_success,
                    'redirectUrl' => $this->lang->text_success,
                ];

                event(new \App\Events\InventoryReceivingOrderSavedEvent(saved_order:$receiving_order, old_order:$old_receiving_order));
            }

            return response(json_encode($json))->header('Content-Type','application/json');

        } catch (\Throwable $th) {
            if(config('app.debug')){
                $json['error'] = $th->getMessage();
            }else{
                $json['error'] = $this->lang->text_fail;
            }
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
            $result = $this->CountingService->saveStatusCode($new_data);
    
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
}