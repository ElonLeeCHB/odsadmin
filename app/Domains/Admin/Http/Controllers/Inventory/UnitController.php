<?php

namespace App\Domains\Admin\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Domains\Admin\Services\Inventory\UnitService;
use App\Repositories\Eloquent\Localization\LanguageRepository;

class UnitController extends BackendController
{
    public function __construct(private Request $request, private UnitService $UnitService, private LanguageRepository $LanguageRepository)
    {
        parent::__construct();
        
        $this->getLang(['admin/common/common','admin/inventory/unit']);
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

        $data['list_url'] = route('lang.admin.inventory.units.list'); //本參數在 getList() 也必須存在。
        $data['add_url'] = route('lang.admin.inventory.units.form');
        $data['delete_url'] = route('lang.admin.inventory.units.destroy');
        
        return view('admin.inventory.unit', $data);
    }

    public function list()
    {
        return $this->getList();
    }

    private function getList()
    {
        $data['lang'] = $this->lang;


        // Prepare query_data for records
        $query_data = $this->resetUrlData($this->request->query());

        // Rows
        $units = $this->UnitService->getUnits($query_data);

        foreach ($units as $row) {
            $row->edit_url = route('lang.admin.inventory.units.form', array_merge([$row->id], $query_data));
            $row->is_active_name = ($row->is_active==1) ? $this->lang->text_enabled :$this->lang->text_disabled;
        }

        $data['units'] = $units->withPath(route('lang.admin.inventory.units.list'))->appends($query_data);

        // Prepare links for list table's header
        if($query_data['order'] == 'ASC'){
            $order = 'DESC';
        }else{
            $order = 'ASC';
        }
        
        $data['sort'] = strtolower($query_data['sort']);
        $data['order'] = strtolower($order);

        $query_data = $this->unsetUrlQueryData($query_data);
        
        
        // link of table header for sorting
        $url = '';

        foreach($query_data as $key => $value){
            $url .= "&$key=$value";
        }

        $route = route('lang.admin.inventory.units.list');

        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
        $data['sort_code'] = $route . "?sort=code&order=$order" .$url;
        $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_is_active'] = $route . "?sort=is_active&order=$order" .$url;
        
        $data['list_url'] = route('lang.admin.inventory.units.list');
        
        return view('admin.inventory.unit_list', $data);
    }

    public function form($unit_id = null)
    {
        $data['lang'] = $this->lang;

        // Languages
        $data['languages'] = $this->LanguageRepository->newModel()->active()->get();

        $this->lang->text_form = empty($unit_id) ? $this->lang->trans('text_add') : $this->lang->trans('text_edit');

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
            'href' => route('lang.admin.inventory.units.index'),
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

        $data['save_url'] = route('lang.admin.inventory.units.save');
        $data['back_url'] = route('lang.admin.inventory.units.index', $queries);        

        // Get Record
        $result = $this->UnitService->findIdOrFailOrNew($unit_id);

        if(empty($result['error']) && !empty($result['data'])){
            $unit = $result['data'];
        }else if(!empty($result['error'])){
            return response(json_encode(['error' => $result['error']]))->header('Content-Type','application/json');
        }
        unset($result);


        // translations
        if($unit->translations->isEmpty()){
            $translations = [];
        }else{
            foreach ($unit->translations as $translation) {
                $translations[$translation->locale] = $translation->toArray();
            }
        }
        $data['translations'] = $translations;
        
        $data['unit']  = $unit->toCleanObject();
        
        if(!empty($data['unit']) && $unit_id == $unit->id){
            $data['unit_id'] = $unit_id;
        }else{
            $data['unit_id'] = null;
        }

        return view('admin.inventory.unit_form', $data);
    }

    public function save()
    {
        $data = $this->request->all();

        $json = [];

        if(empty($this->request->name) || mb_strlen($this->request->name) < 1 ){
           // $json['error']['name'] = '請輸入名稱 1-20 個字';
        }

        // 檢查欄位
        // do something        
        if(isset($json['error']) && !isset($json['error']['warning'])) {
            $json['error']['warning'] = $this->lang->error_warning;
        }

        if(!$json) {
            $result = $this->UnitService->saveUnit($data);

            $unit_id = $result['id'];

            if(empty($result['error'])){
                $json = [
                    'success' => $this->lang->text_success,
                    'unit_id' => $unit_id,
                    'redirectUrl' => route('lang.admin.inventory.units.form', $unit_id),
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
            $result = $this->UnitService->destroy($selected);

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