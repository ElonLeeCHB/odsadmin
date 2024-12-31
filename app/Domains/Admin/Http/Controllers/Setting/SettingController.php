<?php

namespace App\Domains\Admin\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use Illuminate\Http\Request;
use App\Domains\Admin\Services\Setting\SettingService;
use App\Libraries\TranslationLibrary;

class SettingController extends BackendController
{
    public function __construct(protected Request $request, protected SettingService $SettingService)
    {
        $this->request = $request;
        $this->SettingService = $SettingService;

        // Translations
        $this->lang = (new TranslationLibrary())->getTranslations(['admin/common/common','admin/setting/setting']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['lang'] = $this->lang;

        // Breadcomb
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_home,
            'href' => route('lang.admin.dashboard'),
        ];

        $breadcumbs[] = (object)[
            'text' => $this->lang->text_setting,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];

        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.setting.settings.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        $data['list'] = $this->getList();

        $data['list_url'] = route('lang.admin.setting.settings.list'); //本參數在 getList() 也必須存在。
        $data['add_url'] = route('lang.admin.setting.settings.form');
        $data['delete_url'] = route('lang.admin.setting.settings.destroy');

        return view('admin.setting.setting', $data);
    }

    public function list()
    {
        return $this->getList();
    }

    public function getList()
    {
        $data['lang'] = $this->lang;

        // Prepare query_data for records
        $queries = [];

        if(!empty($this->request->query('page'))){
            $page = $queries['page'] = $this->request->input('page');
        }else{
            $page = $queries['page'] = 1;
        }

        if(!empty($this->request->query('sort'))){
            $sort = $queries['sort'] = $this->request->input('sort');
        }else{
            $sort = $queries['sort'] = 'id';
        }

        if(!empty($this->request->query('order'))){
            $order = $queries['order'] = $this->request->query('order');
        }else{
            $order = $queries['order'] = 'DESC';
        }

        if(!empty($this->request->query('limit'))){
            $limit = $queries['limit'] = $this->request->query('limit');
        }

        foreach($this->request->all() as $key => $value){
            if(strpos($key, 'filter_') !== false){
                $queries[$key] = $value;
            }
        }

        // Rows
        $settings = $this->SettingService->getRows($queries);

        if(!empty($settings)){
            foreach ($settings as $row) {
                $row->edit_url = route('lang.admin.setting.settings.form', array_merge([$row->id], $queries));
                if(!empty($row->location) && !empty($row->location->name)){
                    $row->location_name = $row->location->name;
                }else{
                    $row->location_name = '預設';
                }
            }
        }

        $data['settings'] = $settings->withPath(route('lang.admin.setting.settings.list'))->appends($queries);

        // Prepare links for list table's header
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
        $route = route('lang.admin.setting.settings.list');
        
        $data['sort_group'] = $route . "?sort=group&order=$order" .$url;
        $data['sort_setting_key'] = $route . "?sort=setting_key&order=$order" .$url;
        $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_created_at'] = $route . "?sort=created_at&order=$order" .$url;

        $data['list_url'] = route('lang.admin.setting.settings.list');

        return view('admin.setting.setting_list', $data);
    }

    public function save()
    {
        $postData = $this->request->post();

        $json = [];

        if(isset($json['error']) && !isset($json['error']['warning'])) {
            $json['error']['warning'] = $this->lang->error_warning;
        }

        if(!$json) {
            $result = $this->SettingService->save($postData);
            
            if(empty($result['error']) && !empty($result['setting_id'])){
                $json = [
                    'setting_id' => $result['setting_id'],
                    'success' => $this->lang->text_success,
                    'redirectUrl' => route('lang.admin.setting.settings.form', $result['setting_id']),
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

    public function form($setting_id = null)
    {
        $data['lang'] = $this->lang;
    
        $this->lang->text_form = empty($setting_id) ? $this->lang->trans('text_add') : $this->lang->trans('text_edit');
    
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
            'href' => route('lang.admin.setting.settings.index'),
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
    
        $data['save_url'] = route('lang.admin.setting.settings.save');
        $data['back_url'] = route('lang.admin.setting.settings.index', $queries);        
    
        // Get Record
        $result = $this->SettingService->findIdOrFailOrNew($setting_id);

        if(empty($result['error']) && !empty($result['data'])){
            $setting = $result['data'];
        }else if(!empty($result['error'])){
            return response(json_encode(['error' => $result['error']]))->header('Content-Type','application/json');
        }
        unset($result);

        // 如果用 $setting->setting_value, 由於 model 的特性，會導致非預期的結果。所以這裡另外設定一個 data 變數
        if($setting->is_json == 1){
            $data['setting_value']  = json_encode($setting->setting_value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }else{
            $data['setting_value'] = $setting->setting_value;
        }

        // Do something to record
        $data['setting']  = $setting;
    
        if(!empty($data['setting']) && $setting_id == $setting->id){
            $data['setting_id'] = $setting_id;
        }else{
            $data['setting_id'] = null;
        }
    
        return view('admin.setting.setting_form', $data);
    }
    

    public function destroy()
    {
        $this->initController();
        
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
            $result = $this->SettingService->destroy($selected);

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
