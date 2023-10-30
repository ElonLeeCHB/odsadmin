<?php

namespace App\Domains\Admin\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Domains\Admin\Services\Inventory\ReceivingOrderService;
use App\Repositories\Eloquent\Common\UnitRepository;
use App\Models\Setting\Location;
use App\Models\Localization\Language;

class ReceivingOrderController extends BackendController
{
    public function __construct(
        private Request $request
        , private ReceivingOrderService $ReceivingOrderService
        , private UnitRepository $UnitRepository
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
            'text' => $this->lang->text_product,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];
        
        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.inventory.receiving.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        // tax_types
        $data['tax_types'] = [];

        // statuses
        $data['receiving_order_statuses'] = $this->ReceivingOrderService->getCachedActiveReceivingOrderStatuses();

        $data['list'] = $this->getList();

        $data['list_url']   =  route('lang.admin.inventory.receiving.list');
        $data['add_url']    = route('lang.admin.inventory.receiving.form');
        $data['delete_url'] = route('lang.admin.inventory.receiving.delete');

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
                $row->edit_url = route('lang.admin.inventory.receiving.form', array_merge([$row->id], $query_data));
            }
        }

        $data['receiving_orders'] = $receiving_orders->withPath(route('lang.admin.inventory.receiving.list'))->appends($query_data);
        
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
        $route = route('lang.admin.inventory.receiving.list');

        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
        $data['sort_code'] = $route . "?sort=code&order=$order" .$url;
        $data['sort_receiving_date'] = $route . "?sort=receiving_date&order=$order" .$url;
        $data['sort_receiving_date'] = $route . "?sort=receiving_date&order=$order" .$url;

        $data['list_url'] = route('lang.admin.inventory.receiving.list');

        return view('admin.inventory.receiving_order_list', $data);
    }


    public function form($receiving_order_id = null)
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
            'href' => route('lang.admin.inventory.receiving.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;


        // Prepare link for save, back
        $query_data = $this->getQueries($this->request->query());

        $data['save_url'] = route('lang.admin.inventory.receiving.save');
        $data['back_url'] = route('lang.admin.inventory.receiving.index', $query_data);
        $data['product_autocomplete_url'] = route('lang.admin.inventory.products.autocomplete');
        


        // Get Record
        $receiving_order = $this->ReceivingOrderService->findIdOrFailOrNew($receiving_order_id);

        if(empty($receiving_order->receiving_date)){
            $receiving_order->receiving_date = date('Y-m-d');
        }

        $data['receiving_order'] = $this->ReceivingOrderService->refineRow($receiving_order, ['optimize' => true,'sanitize' => true]);

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
        
        // receiving_products
        if(!empty($receiving_order)){
            $receiving_order->load('receiving_products.product_units');

            foreach ($receiving_order->receiving_products as $receiving_product) {
                foreach ($receiving_product->product_units as $product_unit) {
                    // multiplier 原應設為 $product_unit 的屬性。暫時設到 $receiving_product
                    $receiving_product->multiplier = $product_unit->destination_quantity / $product_unit->source_quantity ;
                }
            }
        }

        $data['receiving_products'] = $receiving_order->receiving_products;

        // units
        $filter_data = [
            'filter_keyword' => $this->request->filter_keyword,
            'pagination' => false,
        ];
        $data['units'] = $this->UnitRepository->getKeyedActiveUnits($filter_data);

        $data['tax_types'] = $this->ReceivingOrderService->getActiveTaxTypesIndexByCode();

        return view('admin.inventory.receiving_order_form', $data);
    }


    public function save()
    {
        $post_data = $this->request->post();

        $json = [];

        // * 檢查欄位
        if(empty($post_data['supplier_id'])){
            //$json['error']['supplier_id'] = '廠商流水號不可為空';
        }

        if(isset($json['error']) && !isset($json['error']['warning'])) {
            $json['error']['warning'] = $this->lang->error_warning;
        }
        // end

        if(!$json) {
            $result = $this->ReceivingOrderService->updateOrCreate($post_data);

            if(empty($result['error'])){
                $json = [
                    'receiving_order_id' => $result['data']['receiving_order_id'],
                    'code' => $result['data']['code'],
                    'success' => $this->lang->text_success,
                    'redirectUrl' => route('lang.admin.inventory.receiving.form', $result['data']['receiving_order_id']),
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