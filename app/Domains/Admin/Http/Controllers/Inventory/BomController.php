<?php

namespace App\Domains\Admin\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Domains\Admin\Services\Inventory\BomService;

class BomController extends BackendController
{
    public function __construct(
        protected Request $request
        , private BomService $BomService
    )
    {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/inventory/bom']);
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
            'href' => route('lang.admin.inventory.boms.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        $data['list'] = $this->getList();

        $data['list_url']   = route('lang.admin.inventory.boms.list');
        $data['add_url']    = route('lang.admin.inventory.boms.form');
        $data['delete_url'] = route('lang.admin.inventory.boms.delete');
        $data['product_autocomplete_url'] = route('lang.admin.inventory.products.autocomplete');
        return view('admin.inventory.bom', $data);
    }

    public function list()
    {
        return $this->getList();
    }

    public function getList()
    {
        $data['lang'] = $this->lang;

        $query_data  = $this->url_data;

        // Prepare query_data for records
        $filter_data = $query_data;

        $filter_data['select_relation_columns'] = ['product_name'];

        $filter_data['extra_columns'][] = 'product_name';
        // Rows
        $boms = $this->BomService->getBoms($filter_data);

        if(!empty($boms)){
            foreach ($boms as $row) {
                $row->edit_url = route('lang.admin.inventory.boms.form', array_merge([$row->id], $query_data));
            }
        }

        $boms->withPath(route('lang.admin.inventory.boms.list'))->appends($query_data);

        $data['boms'] = DataHelper::unsetRelations($boms, ['product']);

        // Prepare links for list table's header
        if(!empty($query_data['order']) && $query_data['order'] == 'ASC'){
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
        $route = route('lang.admin.inventory.boms.list');

        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
        $data['product_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_effective_date'] = $route . "?sort=model&order=$order" .$url;
        $data['sort_expiry_date'] = $route . "?sort=supplier_name&order=$order" .$url;
        $data['list_url']   = route('lang.admin.inventory.boms.list');
        return view('admin.inventory.bom_list', $data);
    }


    public function form($bom_id = null)
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
            'href' => route('lang.admin.inventory.boms.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;


        // Prepare link for save, back
        $queries  = $this->url_data;

        $data['save_url'] = route('lang.admin.inventory.boms.save');
        $data['back_url'] = route('lang.admin.inventory.boms.index', $this->request->getQueryString());
        $data['product_autocomplete_url'] = route('lang.admin.inventory.products.autocomplete');


        // Get record
        $filter_data = [
            'with' => 'bom_products.sub_product.translation',
        ];
        $result = $this->BomService->findIdOrFailOrNew($bom_id, $filter_data);

        if(empty($result['error']) && !empty($result['data'])){
            $bom = $result['data'];
        }else if(!empty($result['error'])){
            return response(json_encode(['error' => $result['error']]))->header('Content-Type','application/json');
        }
        unset($result);

        $bom = $this->BomService->getExtraColumns($bom, ['product_name']);


        // Default column value
        if(empty($bom_id)){
            $bom->is_active = 1;
        }

        $bom->product_edit_url = route('lang.admin.inventory.products.form', $bom->product_id);

        $data['bom']  = $bom->toCleanObject();

        if(!empty($data['bom']) && $bom_id == $bom->id){
            $data['bom_id'] = $bom_id;
        }else{
            $data['bom_id'] = null;
        }


        // sub_products
        //$sub_products = $bom->sub_products;
        $data['bom_products'] = $this->BomService->getBomSubProducts($bom);

        foreach ($data['bom_products']  as $key => $counting_product) {
            $data['bom_products'][$key]->product_edit_url = route('lang.admin.inventory.products.form', $counting_product->sub_product_id);
        }

        return view('admin.inventory.bom_form', $data);
    }


    public function save()
    {
        $post_data = $this->request->post();

        // 檢查
        $json = [];

        $bom_id = $post_data['bom_id'] ?? '';


        if (isset($json['error']) && !isset($json['error']['warning'])) {
            $json['error']['warning'] = $this->lang->error_warning;
        }


        // 檢查通過
        if(!$json) {
            $result = $this->BomService->saveBom($post_data);

            //$result = $this->BomService->saveBomProducts($post_data);
            if(empty($result['error'])){
                $json = [
                    'bom_id' => $result['data']['id'],
                    'success' => $this->lang->text_success,
                    'redirectUrl' => route('lang.admin.inventory.boms.form', $result['data']['id']),
                ];
            }else if(auth()->user()->username == 'admin'){
                $json['error'] = $result['error'];
            }else{
                $json['error'] = $this->lang->text_fail;
            }
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }










}
