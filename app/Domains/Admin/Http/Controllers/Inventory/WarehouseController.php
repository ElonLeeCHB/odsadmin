<?php

namespace App\Domains\Admin\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Domains\Admin\Services\Inventory\WarehouseService;

class WarehouseController extends BackendController
{
    public function __construct(private Request $request, private WarehouseService $WarehouseService)
    {
        parent::__construct();
        
        $this->getLang(['admin/common/common','admin/inventory/warehouse']);
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
            'href' => route('lang.admin.inventory.categories.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        $data['list'] = $this->getList();

        $data['list_url'] = route('lang.admin.inventory.warehouses.list'); //本參數在 getList() 也必須存在。
        $data['add_url'] = route('lang.admin.inventory.warehouses.form');
        $data['delete_url'] = route('lang.admin.inventory.warehouses.destroy');
        
        return view('admin.inventory.warehouse', $data);
    }

    public function list()
    {
        return $this->getList();
    }

    private function getList()
    {
        $data['lang'] = $this->lang;

        $url_query_data = $this->request->query();
        
        // Prepare queries for records
        $query_data = [];

        if(isset($url_query_data['sort'])){
            $query_data['sort'] = $url_query_data['sort'];
        }else{
            $query_data['sort'] = 'id';
        }

        if(isset($url_query_data['order'])){
            $query_data['order'] = $url_query_data['order'];
        }else{
            $query_data['order'] = 'asc';
        }

        if(isset($url_query_data['page'])){
            $query_data['page'] = $url_query_data['page'];
        }else{
            $query_data['page'] = 1;
        }

        foreach($url_query_data as $key => $value){
            if(strpos($key, 'filter_') !== false){
                $query_data[$key] = $value;
            }

            if(strpos($key, 'equal_') !== false){
                $query_data[$key] = $value;
            }
        }

        // Default is_active to 1
        if(!isset($url_query_data['equal_is_active'])){
            $query_data['equal_is_active'] = 1;
        }else{
            $query_data['equal_is_active'] = $url_query_data['equal_is_active'];
        }
        

        // Rows
        $warehouses = $this->WarehouseService->getRows($query_data);

        foreach ($warehouses as $row) {
            $row->edit_url = route('lang.admin.inventory.warehouses.form', array_merge([$row->id], $query_data));
            $row->is_active = ($row->is_active==1) ? $this->lang->text_enabled :$this->lang->text_disabled;
        }

        $data['warehouses'] = $warehouses;

        // Prepare links for sort on list table's header
        if(isset($query_data['order']) && $query_data['order'] == 'ASC'){
            $query_data['order'] = 'DESC';
        }else{
            $query_data['order'] = 'ASC';
        }
        
        $data['sort'] = strtolower($query_data['sort'] ?? '');
        $data['order'] = strtolower($query_data['order']);

        unset($query_data['sort']);
        unset($query_data['order']);

        $url = '';

        foreach($query_data as $key => $value){
            if(is_string($value)){
                $url .= "&$key=$value";
            }
        }
        
        //link of table header for sorting
        $route = route('lang.admin.inventory.warehouses.list');

        $order = $url_query_data['order'] ?? 'ASC';

        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
        $data['sort_code'] = $route . "?sort=code&order=$order" .$url;
        $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_is_active'] = $route . "?sort=is_active&order=$order" .$url;
        $data['sort_sort_order'] = $route . "?sort=sort_order&order=$order" .$url;
        
        $data['list_url'] = route('lang.admin.inventory.warehouses.list');
        
        return view('admin.inventory.warehouse_list', $data);
    }

    public function form($warehouse_id = null)
    {
        $data['lang'] = $this->lang;

        $this->lang->text_form = empty($warehouse_id) ? $this->lang->trans('text_add') : $this->lang->trans('text_edit');

        // Breadcomb
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_home,
            'href' => route('lang.admin.dashboard'),
        ];

        $breadcumbs[] = (object)[
            'text' => $this->lang->text_warehouse,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];

        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.inventory.warehouses.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        // Prepare link for save, back
        $queries = [];

        foreach($this->request->all() as $key => $value){
            if(strpos($key, 'filter_') !== false){
                $queries[$key] = $value;
            }
        }

        if(!empty($this->request->query('page'))){
            $queries['page'] = $this->request->query('page');
        }else{
            $queries['page'] = 1;
        }

        if(!empty($this->request->query('sort'))){
            $queries['sort'] = $this->request->query('sort');
        }else{
            $queries['sort'] = 'id';
        }

        if(!empty($this->request->query('order'))){
            $queries['order'] = $this->request->query('order');
        }else{
            $queries['order'] = 'DESC';
        }

        if(!empty($this->request->query('limit'))){
            $queries['limit'] = $this->request->query('limit');
        }

        $data['save_url'] = route('lang.admin.inventory.warehouses.save');
        $data['back_url'] = route('lang.admin.inventory.warehouses.index', $queries);        

        // Get Record
        $result = $this->WarehouseService->findIdOrFailOrNew($warehouse_id);

        if(!empty($result['data'])){
            $warehouse = $result['data'];
        }else if(!empty($result['error'])){
            return response(json_encode(['error' => $result['error']]))->header('Content-Type','application/json');
        }
        unset($result);

        $data['warehouse']  = $warehouse;

        if(!empty($data['warehouse']) && $warehouse_id == $warehouse->id){
            $data['warehouse_id'] = $warehouse_id;
        }else{
            $data['warehouse_id'] = null;
        }

        return view('admin.inventory.warehouse_form', $data);
    }

    public function save()
    {
        $data = $this->request->all();

        $json = [];

        if(empty($this->request->name) || mb_strlen($this->request->name) < 2 ){
            $json['error']['name'] = '請輸入名稱 2-20 個字';
        }

        // 檢查欄位
        // do something        
        if(isset($json['error']) && !isset($json['error']['warning'])) {
            $json['error']['warning'] = $this->lang->error_warning;
        }

        if(!$json) {
            $result = $this->WarehouseService->updateOrCreate($data);

            if(empty($result['error']) && !empty($result['warehouse_id'])){
                $json = [
                    'warehouse_id' => $result['warehouse_id'],
                    'success' => $this->lang->text_success,
                    'redirectUrl' => route('lang.admin.inventory.warehouses.form', $result['warehouse_id']),
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

    public function destroy()
    {
        $post_data = $this->request->post();

        $json = [];

        if (isset($post_data['selected'])) {
            $selected = $post_data['selected'];
        } else {
            $selected = [];
        }

        // Permission
        if($this->acting_username !== 'admin'){
            $json['error'] = $this->lang->error_permission;
        }
        
		if (!$json) {
            $result = $this->WarehouseService->destroy($selected);

            if(empty($result['error'])){
                $json['success'] = $this->lang->text_success;
            }else{
                if(config('app.debug') || auth()->user()->username == 'admin'){
                    $json['error'] = $result['error'];
                }else{
                    $json['error'] = $this->lang->text_fail;
                }
            }
		}

        return response(json_encode($json))->header('Content-Type','application/json');
    }

}