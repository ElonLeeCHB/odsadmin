<?php

namespace App\Domains\Admin\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Services\Inventory\PurchasingOrderService;
use App\Repositories\Eloquent\Inventory\UnitRepository;
use App\Models\Setting\Location;
use App\Models\Localization\Language;

class PurchasingController extends BackendController
{
    public function __construct(
        private Request $request
        , private PurchasingOrderService $PurchasingOrderService
        , private UnitRepository $UnitRepository
    )
    {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/inventory/purchasing']);
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
            'text' => $this->lang->text_product,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];
        
        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.inventory.purchasing.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;


        // statuses
        $data['purchasing_order_statuses'] = $this->PurchasingOrderService->getActivePurchasingOrderStatuses();


        $data['list'] = $this->getList();

        $data['list_url']   =  route('lang.admin.inventory.purchasing.list');
        $data['add_url']    = route('lang.admin.inventory.purchasing.form');
        $data['delete_url'] = route('lang.admin.inventory.purchasing.delete');

        return view('admin.inventory.purchasing_order', $data);
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
        $purchasing_orders = $this->PurchasingOrderService->getPurchasingOrders($query_data);

        if(!empty($purchasing_orders)){
            foreach ($purchasing_orders as $row) {
                $row->edit_url = route('lang.admin.inventory.purchasing.form', array_merge([$row->id], $query_data));
            }
        }

        $data['purchasing_orders'] = $purchasing_orders->withPath(route('lang.admin.inventory.purchasing.list'))->appends($query_data);
        
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
        $route = route('lang.admin.inventory.purchasing.list');

        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
        $data['sort_code'] = $route . "?sort=code&order=$order" .$url;
        $data['sort_purchasing_date'] = $route . "?sort=purchasing_date&order=$order" .$url;
        $data['sort_receiving_date'] = $route . "?sort=receiving_date&order=$order" .$url;

        $data['list_url'] = route('lang.admin.inventory.purchasing.list');

        return view('admin.inventory.purchasing_order_list', $data);
    }


    public function form($purchasing_order_id = null)
    {
        $data['lang'] = $this->lang;

        // Languages
        $data['languages'] = Language::active()->get();


        $this->lang->text_form = empty($product_id) ? $this->lang->trans('text_add') : $this->lang->trans('text_edit');


        // Breadcomb
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_home,
            'href' => route('lang.admin.dashboard'),
        ];

        $breadcumbs[] = (object)[
            'text' => $this->lang->text_supplier,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];

        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.inventory.purchasing.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;


        // Prepare link for save, back
        $query_data = $this->getQueries($this->request->query());

        $data['save_url'] = route('lang.admin.inventory.purchasing.save');
        $data['back_url'] = route('lang.admin.inventory.purchasing.index', $query_data);
        //$data['autocomplete_url'] = route('lang.admin.inventory.purchasing.autocomplete');


        // Get Record
        $result = $this->PurchasingOrderService->findIdOrFailOrNew($purchasing_order_id);

        if(empty($result['error']) && !empty($result['data'])){
            $purchasing_order = $result['data'];
        }else if(!empty($result['error'])){
            return response(json_encode(['error' => $result['error']]))->header('Content-Type','application/json');
        }
        unset($result);

        $data['purchasing_order'] = $purchasing_order;
        
        if(!empty($purchasing_order) && $purchasing_order_id == $purchasing_order->id){
            $data['purchasing_order_id'] = $purchasing_order_id;
        }else{
            $data['purchasing_order_id'] = null;
        }

        // location
        if(empty($purchasing_order->location_id)){
            $data['location_id'] = 2;
            $data['location_name'] = '中華一餅和平門市';
        }else{
            $data['location_id'] = $purchasing_order->location_id;
            $data['location_name'] = $purchasing_order->location->name;
        }

        //$data['location_autocomplete_url'] = route('lang.admin.setting.locations.autocomplete');
        $data['locations'] = Location::active()->get();

        // supplier
        $data['supplier_autocomplete_url'] = route('lang.admin.counterparty.suppliers.autocomplete');

        // statuses
        $data['statuses'] = $this->PurchasingOrderService->getActivePurchasingOrderStatuses();
        
        // products
        if(!empty($purchasing_order)){
            $purchasing_order->load('purchasing_products');
        }

        $data['purchasing_products'] = $purchasing_order->purchasing_products;

        // units
        $filter_data = [
            'filter_keyword' => $this->request->filter_keyword,
            'pagination' => false,
        ];
        $data['units'] = $this->UnitRepository->getCodeKeyedActiveUnits($filter_data);

        return view('admin.inventory.purchasing_order_form', $data);
    }


    public function save()
    {
        $post_data = $this->request->post();

        $json = [];

        // 檢查欄位
        // do something
        if(empty($post_data['supplier_id'])){
            $json['error']['supplier_id'] = '廠商流水號不可為空';
        }

        if(isset($json['error']) && !isset($json['error']['warning'])) {
            $json['error']['warning'] = $this->lang->error_warning;
        }
        // end

        if(!$json) {
            $result = $this->PurchasingOrderService->updateOrCreate($post_data);

            if(empty($result['error'])){
                $json = [
                    'purchasing_order_id' => $result['data']['purchasing_order_id'],
                    'code' => $result['data']['code'],
                    'success' => $this->lang->text_success,
                    'redirectUrl' => route('lang.admin.inventory.purchasing.form', $result['data']['purchasing_order_id']),
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



}