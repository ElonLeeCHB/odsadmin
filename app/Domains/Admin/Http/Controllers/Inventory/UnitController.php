<?php

namespace App\Domains\Admin\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Services\Inventory\UnitService;
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
        $data['delete_url'] = route('lang.admin.inventory.units.delete');
        
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
        $query_data = $this->getQueries($this->request->query());

        // Rows
        $units = $this->UnitService->getUnits($query_data);

        foreach ($units as $row) {
            $row->edit_url = route('lang.admin.inventory.units.form', array_merge([$row->id], $query_data));
            $row->is_active = ($row->is_active==1) ? $this->lang->text_enabled :$this->lang->text_disabled;
        }
        $data['units'] = $units;

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
        $unit = $this->UnitService->findIdOrFailOrNew($unit_id);

        if(!empty($unit->id)){
            $unit = $this->UnitService->sanitizeRow($unit);
        }
        $data['unit']  = $unit;

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
            $result = $this->UnitService->updateOrCreateUnit($data);

            if(empty($result['error']) && !empty($result['unit_id'])){
                $json = [
                    'unit_id' => $result['unit_id'],
                    'success' => $this->lang->text_success,
                    'redirectUrl' => route('lang.admin.inventory.units.form', $result['unit_id']),
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
        $this->initController();

        $post_data = $this->request->post();

		$json = [];

        // Permission
        if($this->acting_username !== 'admin'){
            $json['error'] = $this->lang->error_permission;
        }

        // Selected
		if (isset($post_data['selected'])) {
			$selected = $post_data['selected'];
		} else {
			$selected = [];
		}

		if (!$json) {

			foreach ($selected as $category_id) {
				$result = $this->UnitService->deleteUnitById($category_id);

                if(!empty($result['error'])){
                    if(config('app.debug')){
                        $json['error'] = $result['error'];
                    }else{
                        $json['error'] = $this->lang->text_fail;
                    }

                    break;
                }
			}
		}
        
        if(empty($json['error'] )){
            $json['success'] = $this->lang->text_success;
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }

}