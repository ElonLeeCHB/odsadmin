<?php

namespace App\Domains\Admin\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Domains\Admin\Services\Inventory\ReceivingOrderService;
use App\Repositories\Eloquent\Inventory\UnitRepository;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Models\Setting\Location;
use App\Models\Localization\Language;

class ReceivingOrderController extends BackendController
{
    public function __construct(
        private Request $request
        , private ReceivingOrderService $ReceivingOrderService
        , private UnitRepository $UnitRepository
        , private TermRepository $TermRepository
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
        $data['receiving_order_statuses'] = $this->ReceivingOrderService->getCachedActiveReceivingOrderStatuses();

        // 單別
        $data['form_types'] = $this->ReceivingOrderService->getCodeKeyedTermsByTaxonomyCode('receiving_order_form_type',toArray:false);

        $data['list'] = $this->getList();

        $data['list_url']   =  route('lang.admin.inventory.receivings.list');
        $data['add_url']    = route('lang.admin.inventory.receivings.form');
        $data['delete_url'] = route('lang.admin.inventory.receivings.delete');
        $data['export01_url'] = route('lang.admin.inventory.receivings.export01');

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
        $query_data = $this->getQueries($this->request->query());

        // Rows
        $receiving_orders = $this->ReceivingOrderService->getReceivingOrders($query_data);

        if(!empty($receiving_orders)){
            foreach ($receiving_orders as $row) {
                $row->edit_url = route('lang.admin.inventory.receivings.form', array_merge([$row->id], $query_data));
            }
        }

        $data['receiving_orders'] = $receiving_orders->withPath(route('lang.admin.inventory.receivings.list'))->appends($query_data);

        // Prepare links for list table's header
        if($query_data['order'] == 'ASC'){
            $order = 'DESC';
        }else{
            $order = 'ASC';
        }

        $data['sort'] = strtolower($query_data['sort']);
        $data['order'] = strtolower($order);

        $query_data = $this->unsetUrlQueryData($query_data);

        $url = '';

        foreach($query_data as $key => $value){
            $url .= "&$key=$value";
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
        $query_data = $this->getQueries($this->request->query());

        $data['save_url'] = route('lang.admin.inventory.receivings.save', $receiving_order_id);
        $data['back_url'] = route('lang.admin.inventory.receivings.index', $query_data);
        $data['product_autocomplete_url'] = route('lang.admin.inventory.products.autocomplete');
        $data['status_save_url'] = route('lang.admin.inventory.receivings.saveStatusCode');


        // Get Record
        $result = $this->ReceivingOrderService->findIdOrFailOrNew($receiving_order_id);

        if(!empty($result['data'])){
            $receiving_order = $result['data'];
        }else if(!empty($result['error'])){
            return response(json_encode(['error' => $result['error']]))->header('Content-Type','application/json');
        }
        unset($result);

        if(empty($receiving_order->receiving_date)){
            $receiving_order->receiving_date = date('Y-m-d');
        }

        if(empty($receiving_order->status_code)){
            $receiving_order->status_code = 'P';
        }

        $data['receiving_order'] = $receiving_order;

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
        $data['statuses'] = $this->ReceivingOrderService->getCachedActiveReceivingOrderStatuses();

        $standard_units = $this->UnitRepository->getCodeKeyedStandardActiveUnits();
        $standard_units_array_keys = array_keys($standard_units);

        // receiving_products
        if(!empty($receiving_order)){
            $receiving_order->load('receiving_products.product_units');

            foreach ($receiving_order->receiving_products as $receiving_product) {

                foreach ($receiving_product->product_units as $key => $product_unit) {
                    $arr = $product_unit->toArray();
                    $arr['factor'] = $product_unit['destination_quantity'] / $product_unit['source_quantity'];

                    unset($arr['source_unit']);
                    unset($arr['destination_unit']);

                    $receiving_product->product_units[$key] = (object) $arr;
                }

                // 都是標準單位，product_units 不會有，要查 units 表
                if(   in_array($receiving_product->receiving_unit_code, $standard_units_array_keys)
                   && in_array($receiving_product->stock_unit_code, $standard_units_array_keys)){

                    $params = [
                        'from_quantity' => 1,
                        'from_unit_code' => $receiving_product->receiving_unit_code,
                        'to_unit_code' => $receiving_product->stock_unit_code,
                    ];
                    $factor = $this->UnitRepository->calculateQty($params);

                    //$stock_unit_code = $standard_units[$receiving_product->receiving_unit_code]
                    $receiving_product->product_units[] = (object) [
                        'product_id' => $receiving_product->product_id,
                        'source_unit_code' => $receiving_product->receiving_unit_code ?? '',
                        'source_unit_name' => $standard_units[$receiving_product->receiving_unit_code]->name ?? '',
                        'source_quantity' => 1,
                        'destination_unit_code' => $receiving_product->product->stock_unit_code ?? '',
                        'destination_unit_name' => $standard_units[$receiving_product->stock_unit_code]->name ?? '',
                        'destination_quantity' => $factor,
                        'factor' => $factor,
                    ];

                    $receiving_product->setRelation('product', null);

                }

                // 來源單位不是標準單位，則來源單位應有轉換，但新增一筆來源跟目的都相同的庫存單位供選擇
                if(   !in_array($receiving_product->receiving_unit_code, $standard_units_array_keys)
                   && in_array($receiving_product->stock_unit_code, $standard_units_array_keys)){

                    //$stock_unit_code = $standard_units[$receiving_product->receiving_unit_code]
                    $receiving_product->product_units[] = (object) [
                        'product_id' => $receiving_product->product_id,
                        'source_unit_code' => $receiving_product->stock_unit_code,
                        'source_unit_name' => $receiving_product->stock_unit_name,
                        'source_quantity' => 1,
                        'destination_unit_code' => $receiving_product->stock_unit_code,
                        'destination_unit_name' => $receiving_product->stock_unit_name,
                        'destination_quantity' => 1,
                        'factor' => 1,
                    ];

                    $receiving_product->setRelation('product', null);
                }
            }
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

        return view('admin.inventory.receiving_order_form', $data);
    }


    public function save($id = null)
    {
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
            $json['error']['warning'] = $this->lang->error_warning;
        }
        // end

        if(!$json) {
            $result = $this->ReceivingOrderService->saveReceivingOrder($post_data);

            if(empty($result['error'])){
                $json = [
                    'receiving_order_id' => $result['data']['receiving_order_id'],
                    'code' => $result['data']['code'],
                    'success' => $this->lang->text_success,
                    'redirectUrl' => route('lang.admin.inventory.receivings.form', $result['data']['receiving_order_id']),
                ];
            }else{
                if(config('app.debug')){
                    $json['error'] = $result['error'];
                }else{
                    $json['error'] = $this->lang->text_fail;
                }
            }
        }

       return response(json_encode($json))->header('Content-Type','application/json');
    }


    public function export01()
    {
        $params = request()->all();
        return $this->ReceivingOrderService->export01($params);
    }


    public function saveStatusCode()
    {
        $post_data = request()->all();
        $new_data = $post_data['update_status'];
        $result = $this->ReceivingOrderService->saveStatusCode($new_data);

        if(!empty($result['data']['id'])){
            $msg = [
                'success' => '狀態已變更為：' . $result['data']['status_name'],
                'data' => $result['data'],
            ];
        }

        return $msg;
    }
}
