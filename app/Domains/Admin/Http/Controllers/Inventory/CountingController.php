<?php

namespace App\Domains\Admin\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Domains\Admin\Services\Inventory\CountingService;

class CountingController extends BackendController
{
    public function __construct(private Request $request, private CountingService $CountingService)
    {
        parent::__construct();
        
        $this->getLang(['admin/common/common','admin/inventory/counting']);
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
            'href' => route('lang.admin.inventory.categories.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        $data['list'] = $this->getList();

        $data['list_url'] = route('lang.admin.inventory.countings.list'); //本參數在 getList() 也必須存在。
        $data['add_url'] = route('lang.admin.inventory.countings.form');
        $data['delete_url'] = route('lang.admin.inventory.countings.delete');
        $data['export_counting_product_list'] = route('lang.admin.inventory.countings.export_counting_product_list');
        
        return view('admin.inventory.counting', $data);
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
        $countings = $this->CountingService->getCountingTasks($query_data);

        foreach ($countings as $row) {
            $row->edit_url = route('lang.admin.inventory.countings.form', array_merge([$row->id], $query_data));
            $row->is_active_name = ($row->is_active==1) ? $this->lang->text_enabled :$this->lang->text_disabled;
        }

        if(count($countings) > 0){
            $data['countings'] = $countings->withPath(route('lang.admin.inventory.countings.list'))->appends($query_data);
        }else{
            $data['countings'] = [];
        }


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
        $data['sort_task_date'] = $route . "?sort=task_date&order=$order" .$url;
        $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_is_active'] = $route . "?sort=is_active&order=$order" .$url;
        
        $data['list_url'] = route('lang.admin.inventory.countings.list');
        
        return view('admin.inventory.counting_list', $data);
    }

    public function form($counting_id = null)
    {
        $data['lang'] = $this->lang;

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

        $data['save_url'] = route('lang.admin.inventory.countings.save');
        $data['back_url'] = route('lang.admin.inventory.countings.index', $queries);
        $data['import_url'] = route('lang.admin.inventory.countings.import',['counting_id' => $counting_id]);     

        // Get Record
        $counting = $this->CountingService->findIdOrFailOrNew($counting_id);
        
        $data['counting']  = $counting;

        if(!empty($data['counting']) && $counting_id == $counting->id){
            $data['counting_id'] = $counting_id;
        }else{
            $data['counting_id'] = null;
        }

        $data['counting_products'] = [];

        return view('admin.inventory.counting_form', $data);
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
            $result = $this->CountingService->saveUnit($data);

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
				$result = $this->CountingService->deleteUnitById($category_id);

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


    public function import($counting_id = null)
    {
        $data = request()->all();
        //$post_data = request()->post();

        $counting_id = !empty($data['counting_id']) ? $data['counting_id'] : null;

        $query_data = [];

        if (request()->hasFile('file')) {
            $file = request()->file('file');
            $filename = date('Y-m-d_H-i-s') . '.' . $file->getClientOriginalExtension();


            $file->move(storage_path('app/imports/receiving_orders'), $filename);

            $fullpath = storage_path('app/imports/receiving_orders') . '/' . $filename;
            $newfile = new UploadedFile($fullpath, $filename, $file->getClientMimeType());
            
            //重新設定 $filename
            $filename = $newfile->getPathname();

            $result = $this->CountingService->import($filename, $counting_id);

            

            if(!empty($result['id'])){
                $counting_id = $result['id'];

                $json = [
                    'success' => '檔案上傳成功',
                    'counting_id' => $result['id'],
                    'code' => $result['code'],
                    'redirectUrl' => route('lang.admin.inventory.countings.form', array_merge(['id' => $counting_id], $query_data)),
                ];
                return response()->json($json);

            }else if(!empty($result['error'])){
                return response()->json(['error' => "檔案上傳成功但解析失敗。<BR>\r\n錯誤：" . $result['error']]);
            }
    
        }
    
        return response()->json(['message' => '请选择一个有效的文件xxx'], 422);
    }


    public function exportCountingProductList()
    {
        $post_data = request()->post();
        
        return $this->CountingService->exportCountingProductList($post_data); 
    }

}