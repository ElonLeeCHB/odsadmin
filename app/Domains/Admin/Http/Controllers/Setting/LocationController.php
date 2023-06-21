<?php

namespace App\Domains\Admin\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Domains\Admin\Services\Setting\LocationService;
use App\Libraries\TranslationLibrary;

class LocationController extends Controller
{
    private $lang;
    private $request;
    private $service;

    public function __construct(Request $request, LocationService $service)
    {
        $this->request = $request;
        $this->service = $service;

        // Translations
        $groups = [
            'admin/common/common',
            'admin/setting/location',
        ];
        $this->lang = (new TranslationLibrary())->getTranslations($groups);
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
        // Language
        $data['lang'] = $this->lang;

        // Prepare link for action
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

        $locations = $this->service->getRows($queries);

        foreach ($locations as $row) {
            $row->edit_url = route('lang.admin.setting.locations.form', array_merge([$row->id], $queries));
        }

        $data['locations'] = $locations;

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
        $location = $this->service->findIdOrFailOrNew($location_id);

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
            $result = $this->service->updateOrCreate($data);

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

    }

}
