<?php

namespace App\Domains\Admin\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\TranslationLibrary;
use App\Domains\Admin\Services\Inventory\WarehouseService;

class WarehouseController extends Controller
{
    private $lang;
    private $request;
    private $service;

    public function __construct(Request $request, WarehouseService $service)
    {
        $this->request = $request;
        $this->service = $service;

        // Translations
        $this->lang = (new TranslationLibrary())->getTranslations(['admin/common/common','admin/inventory/warehouse',]);
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
        $data['delete_url'] = route('lang.admin.inventory.warehouses.delete');
        
        return view('admin.inventory.warehouse', $data);
    }

    public function list()
    {
        return $this->getList();
    }

    public function getList()
    {
        $data['lang'] = $this->lang;

        // Prepare link for action
        $queries = [];

        if(!empty($this->request->query('sort'))){
            $sort = $queries['sort'] = $this->request->input('sort');
        }else{
            $sort = $queries['sort'] = 'sort_order';
        }

        if(!empty($this->request->query('order'))){
            $order = $queries['order'] = $this->request->query('order');
        }else{
            $order = $queries['order'] = 'ASC';
        }

        if(!empty($this->request->query('page'))){
            $queries['page'] = $this->request->input('page');
        }else{
            $queries['page'] = 1;
        }

        if(!empty($this->request->query('limit'))){
            $queries['limit'] = $this->request->query('limit');
        }

        foreach($this->request->all() as $key => $value){
            if(strpos($key, 'filter_') !== false){
                $queries[$key] = $value;
            }
        }

        // Rows
        $warehouses = $this->service->getRows($queries);

        foreach ($warehouses as $row) {
            $row->edit_url = route('lang.admin.inventory.warehouses.form', array_merge([$row->id], $queries));
            $row->is_active = ($row->is_active==1) ? $this->lang->text_enabled :$this->lang->text_disabled;
        }

        $data['warehouses'] = $warehouses;

        // Prepare links for sort on list table's header
        if($order == 'ASC'){
            $order = 'DESC';
        }else{
            $order = 'ASC';
        }
        
        $data['sort'] = strtolower($sort);
        $data['order'] = strtolower($order);

        unset($queries['sort']);
        unset($queries['order']);

        $url = '';

        foreach($queries as $key => $value){
            $url .= "&$key=$value";
        }
        
        //link of table header for sorting
        $route = route('lang.admin.inventory.warehouses.list');

        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
        $data['sort_code'] = $route . "?sort=code&order=$order" .$url;
        $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_is_active'] = $route . "?sort=is_active&order=$order" .$url;
        
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
        $warehouse = $this->service->findIdOrFailOrNew($warehouse_id);

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
            $result = $this->service->updateOrCreate($data);

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

    public function delete()
    {

    }

}