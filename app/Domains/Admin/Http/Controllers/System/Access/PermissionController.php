<?php

namespace App\Domains\Admin\Http\Controllers\System\Access;

use App\Domains\Admin\Http\Controllers\BackendController;
use App\Domains\Admin\Services\System\Access\PermissionService;
use Illuminate\Http\Request;

class PermissionController extends BackendController
{
    private $breadcumbs;

    public function __construct(protected Request $request, protected PermissionService $PermissionService)
    {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/setting/permission']);

        $this->setBreadcumbs();
    }

    protected function setBreadcumbs()
    {
        $this->breadcumbs = [];

        $this->breadcumbs[] = (object)[
            'text' => '首頁',
            'href' => route('lang.admin.dashboard'),
        ];

        $this->breadcumbs[] = (object)[
            'text' => '系統管理',
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];

        $this->breadcumbs[] = (object)[
            'text' => '訪問控制',
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];

        $this->breadcumbs[] = (object)[
            'text' => '權限管理',
            'href' => route('lang.admin.system.access.permissions.index'),
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['lang'] = $this->lang;

        $data['breadcumbs'] = (object)$this->breadcumbs;

        $query_data  = $this->url_data ?? [];

        $data['list'] = $this->getList();

        $data['list_url']   = route('lang.admin.system.access.permissions.list');
        $data['add_url']    = route('lang.admin.system.access.permissions.form');
        $data['delete_url'] = route('lang.admin.system.access.permissions.destroy');

        //Filters
        $data['filter_name'] = $query_data['filter_name'] ?? '';

        return view('admin.system.access.permission', $data);
    }

    public function list()
    {
        $data['lang'] = $this->lang;
        $data['form_action'] = route('lang.admin.system.access.permissions.list');

        return $this->getList();
    }

    /**
     * Show the list table.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    private function getList()
    {
        $data['lang'] = $this->lang;

        // Prepare queries for records
        $query_data = $this->url_data ?? [];

        // Rows
        $permissions = $this->PermissionService->getPermissions($query_data);

        if(!empty($permissions)){
            foreach ($permissions as $row) {
                $row->edit_url = route('lang.admin.system.access.permissions.form', array_merge([$row->id], $query_data));
            }
        }

        $data['permissions'] = $permissions->withPath(route('lang.admin.system.access.permissions.list'))->appends($query_data);

        // Prepare links for list table's header
        $sort = $query_data['sort'] ?? 'id';
        $order = $query_data['order'] ?? 'ASC';

        $data['sort'] = $sort;
        $data['order'] = strtolower($order);

        // Toggle order for next click
        $next_order = ($order == 'ASC') ? 'DESC' : 'ASC';

        // Remove sort and order from query_data for clean URLs
        $url_params = $query_data;
        unset($url_params['sort']);
        unset($url_params['order']);

        $url = '';
        foreach($url_params as $key => $value){
            if(is_string($value)){
                $url .= "&$key=$value";
            }
        }

        // Generate sort URLs
        $route = route('lang.admin.system.access.permissions.list');
        $data['sort_id'] = $route . "?sort=id&order=$next_order" . $url;
        $data['sort_name'] = $route . "?sort=name&order=$next_order" . $url;

        $data['list_url'] = route('lang.admin.system.access.permissions.list');

        return view('admin.system.access.permission_list', $data);
    }

    public function form($permission_id = null)
    {
        $data['lang'] = $this->lang;
        $data['breadcumbs'] = (object)$this->breadcumbs;

        $this->lang->text_form = empty($permission_id) ? '新增' : '編輯';

        // Prepare link for save, back
        $queries = [];

        foreach($this->request->all() as $key => $value){
            if(strpos($key, 'filter_') !== false){
                $queries[$key] = $value;
            }
        }

        $queries['page'] = $this->request->query('page', 1);
        $queries['sort'] = $this->request->query('sort', 'id');
        $queries['order'] = $this->request->query('order', 'ASC');

        if(!empty($this->request->query('limit'))){
            $queries['limit'] = $this->request->query('limit');
        }

        $data['save'] = route('lang.admin.system.access.permissions.save', $permission_id ? [$permission_id] : []);
        $data['back'] = route('lang.admin.system.access.permissions.index', $queries);

        // Get Record
        $permission = $this->PermissionService->findOrFailOrNew($permission_id);

        $data['permission'] = $permission;
        $data['permission_id'] = $permission_id;

        return view('admin.system.access.permission_form', $data);
    }

    public function save($permission_id = null)
    {
        $data = $this->request->all();
        $data['permission_id'] = $permission_id;

        $json = [];

        // Validation
        $validator = $this->PermissionService->validator($this->request->post());

        if($validator->fails()){
            $messages = $validator->errors()->toArray();

            foreach ($messages as $key => $rows) {
                $json['error'][$key] = $rows[0];
            }
        }

        if(isset($json['error']) && !isset($json['error']['warning'])) {
            $json['error']['warning'] = $this->lang->error_warning ?? '請檢查輸入的內容';
        }

        if(!$json) {
            $result = $this->PermissionService->updateOrCreate($data);

            if(empty($result['error'])){
                $json['permission_id'] = $result['data']['permission_id'];
                $json['success'] = $this->lang->text_success ?? '儲存成功';
                $json['redirectUrl'] = route('lang.admin.system.access.permissions.form', $result['data']['permission_id']);
            }else{
                if(auth()->user()->id == 1){
                    $json['error'] = $result['error'];
                }else{
                    $json['error'] = $this->lang->text_fail ?? '儲存失敗';
                }
            }
        }

       return response(json_encode($json))->header('Content-Type','application/json');
    }

    public function destroy()
    {
        $ids = $this->request->input('selected', []);

        $json = [];

        if(empty($ids)){
            $json['error'] = '請選擇要刪除的項目';
            return response(json_encode($json))->header('Content-Type','application/json');
        }

        $result = $this->PermissionService->destroy($ids);

        if(empty($result['error'])){
            $json['success'] = '刪除成功';
        }else{
            $json['error'] = $result['error'];
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }
}
