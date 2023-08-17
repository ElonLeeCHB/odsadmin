<?php

namespace App\Domains\Admin\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use Illuminate\Http\Request;
use App\Domains\Admin\Services\Setting\LocationService;

class LocationController extends BackendController
{
    public function __construct(private Request $request, private LocationService $LocationService)
    {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/setting/location']);
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
            'text' => $this->lang->text_system,
            'href' => 'javaScript:void(0)',
        ];
        
        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.setting.locations.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;
        
        $data['list'] = $this->getList();

        $data['add_url'] = route('lang.admin.setting.locations.form');
        $data['delete_url'] = route('lang.admin.setting.locations.delete');
        $data['list_url'] = route('lang.admin.setting.locations.list'); //本參數在 getList() 也必須存在。
        
        return view('admin.setting.location', $data);
    }

    public function list()
    {
        // Language
        $data['lang'] = $this->lang;

        return $this->getList();
    }

    public function getList()
    {
        $data['lang'] = $this->lang;


        // Prepare query_data for records
        $query_data = $this->getQueries($this->request->query());

        $locations = $this->LocationService->getRows($query_data);

        foreach ($locations as $row) {
            $row->edit_url = route('lang.admin.setting.locations.form', array_merge([$row->id], $query_data));
        }

        $data['locations'] = $locations;


        // Prepare links for list table's header
        if($query_data['order'] == 'ASC'){
            $order = 'DESC';
        }else{
            $order = 'ASC';
        }
        
        $data['sort'] = strtolower($query_data['sort']);
        $data['order'] = strtolower($order);

        unset($query_data['sort']);
        unset($query_data['order']);
        unset($query_data['with']);
        unset($query_data['whereIn']);

        $url = '';

        foreach($query_data as $key => $value){
            $url .= "&$key=$value";
        }
        

        //link of table header for sorting
        $route = route('lang.admin.setting.locations.list');
        
        $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_short_name'] = $route . "?sort=short_name&order=$order" .$url;
        
        $data['list_url'] = route('lang.admin.setting.locations.list');
        
        return view('admin.setting.location_list', $data);
    }

    public function form($location_id = null)
    {
        $data['lang'] = $this->lang;

        $this->lang->text_form = empty($location_id) ? $this->lang->trans('text_add') : $this->lang->trans('text_edit');

        // Breadcomb
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_home,
            'href' => route('lang.admin.dashboard'),
        ];

        $breadcumbs[] = (object)[
            'text' => $this->lang->text_member,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];

        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.member.members.index'),
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

        $data['save_url'] = route('lang.admin.setting.locations.save');
        $data['back_url'] = route('lang.admin.setting.locations.index', $queries);        

        // Get Record
        $location = $this->LocationService->findIdOrFailOrNew($location_id);

        $data['location']  = $location;

        if(!empty($data['location']) && $location_id == $location->id){
            $data['location_id'] = $location_id;
        }else{
            $data['location_id'] = null;
        }

        return view('admin.setting.location_form', $data);
    }


    public function save()
    {
        $data = $this->request->all();

        $json = [];

        if(isset($json['error']) && !isset($json['error']['warning'])) {
            $json['error']['warning'] = $this->lang->error_warning;
        }

        if(!$json) {
            $result = $this->LocationService->updateOrCreate($data);

            if(empty($result['error']) && !empty($result['location_id'])){
                $json = [
                    'location_id' => $result['location_id'],
                    'success' => $this->lang->text_success,
                    'redirectUrl' => route('lang.admin.setting.locations.form', $result['location_id']),
                ];
            }else{
                // $user_id = Auth::user()->id;
                // if($user_id == 1){
                //     $json['error'] = $result['error'];
                // }else{
                //     $json['error'] = $this->lang->text_fail;
                // }
                $json['error'] = $result['error'];
            }
        }

       return response(json_encode($json))->header('Content-Type','application/json');


    }


    public function delete()
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
            foreach ($selected as $category_id) {
                $result = $this->LocationService->deleteLocation($category_id);

                if(!empty($result['error'])){
                    if(config('app.debug')){
                        $json['warning'] = $result['error'];
                    }else{
                        $json['warning'] = $this->lang->text_fail;
                    }

                    break;
                }
            }
        }

        if(empty($json['warning'] )){
            $json['success'] = $this->lang->text_success;
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }

}
