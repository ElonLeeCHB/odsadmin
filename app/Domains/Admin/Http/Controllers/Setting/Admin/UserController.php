<?php

namespace App\Domains\Admin\Http\Controllers\Setting\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Libraries\TranslationLibrary;
use App\Domains\Admin\Services\Setting\Admin\UserService;
use Auth;

class UserController extends Controller
{
    private $request;
    private $lang;
    private $UserService;
    
    public function __construct(Request $request, UserService $UserService)
    {
        $this->request = $request;
        $this->UserService = $UserService;
        $this->lang = (new TranslationLibrary())->getTranslations(['admin/common/common','admin/admin/user',]);
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
            'text' => $this->lang->text_user,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];

        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.setting.admin.users.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        $data['list'] = $this->getList();
        
        $data['newUrl'] = route('lang.admin.setting.admin.users.form');
        $data['listUrl'] = route('lang.admin.setting.admin.users.list');     

        return view('admin.setting.admin', $data);
    }

    public function list()
    {
        $data['lang'] = $this->lang;

        $data['form_action'] = route('lang.admin.setting.admin.users.list');

        return $this->getList();
    }

    /**
     * Show the list table.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function getList()
    {
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

        //$data['action'] = route('lang.admin.setting.admin.users.massDelete');
        //$queries['user_meta']['is_admin'] = 1;

        // Rows
        $users = $this->UserService->getAdminUsers($queries);

        if(!empty($users)){
            foreach ($users as $row) {
                $row->edit_url = route('lang.admin.setting.admin.users.form', array_merge([$row->id], $queries));
            }
        }

        $data['users'] = $users->withPath(route('lang.admin.setting.admin.users.list'))->appends($queries);

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
        $route = route('lang.admin.setting.admin.users.list');
        $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_email'] = $route . "?sort=email&order=$order" .$url;
        $data['sort_date_added'] = $route . "?sort=created_at&order=$order" .$url;
        
        $data['listUrl'] = route('lang.admin.setting.admin.users.list');

        return view('admin.setting.admin_list', $data);
    }


    public function form($user_id = null)
    {
        $data['lang'] = $this->lang;

        $this->lang->text_form = empty($user_id) ? $this->lang->trans('text_add') : $this->lang->trans('text_edit');

        // Breadcomb
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_home,
            'href' => route('lang.admin.dashboard'),
        ];

        $breadcumbs[] = (object)[
            'text' => $this->lang->text_user,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];

        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.setting.admin.users.index'),
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

        $data['save'] = route('lang.admin.setting.admin.users.save');
        $data['back'] = route('lang.admin.setting.admin.users.index', $queries);

        // Get Record
        $user = $this->UserService->findIdOrFailOrNew($user_id);

        $data['user']  = $user;

        if(!empty($data['user']) && $user_id == $user->id){
            $data['user_id'] = $user_id;
        }else{
            $data['user_id'] = null;
        }

        return view('admin.setting.admin_form', $data);
    }

    public function save()
    {
        $data = $this->request->all();

        $json = [];

        // Check user
        $validator = $this->UserService->validator($this->request->post());

        if($validator->fails()){
            $messages = $validator->errors()->toArray();

            foreach ($messages as $key => $rows) {
                $json['error'][$key] = $rows[0];
            }
        }

        if(isset($json['error']) && !isset($json['error']['warning'])) {
            $json['error']['warning'] = $this->lang->error_warning;
        }

        if(!$json) {
            $result = $this->UserService->updateOrCreate($data);

            if(empty($result['error'])){
                $json['user_id'] = $result['data']['user_id'];
                $json['success'] = $this->lang->text_success;
            }else{
                $user_id = Auth::user()->id;
                if($user_id == 1){
                    $json['error'] = $result['error'];
                }else{
                    $json['error'] = $this->lang->text_fail;
                }
            }
        }

       return response(json_encode($json))->header('Content-Type','application/json');
    }


    public function autocomplete()
    {
        $json = [];

        //$filter_data['filter_id'] = '>1';

        if(isset($this->request->filter_personal_name) && mb_strlen($this->request->filter_personal_name, 'utf-8') > 0)
        {
            $filter_data['filter_name'] = $this->request->filter_personal_name;
        }

        if(isset($this->request->filter_mobile) && strlen($this->request->filter_mobile) > 2)
        {
            $filter_data['filter_mobile'] = $this->request->filter_mobile;
        }

        if(isset($this->request->filter_telephone) && strlen($this->request->filter_telephone) > 2)
        {
            $filter_data['filter_telephone'] = $this->request->filter_telephone;
        }

        if(isset($this->request->filter_email) && strlen($this->request->filter_email) > 2)
        {
            $filter_data['filter_email'] = $this->request->filter_email;
        }

        if (empty($this->request->sort)) {
            $filter_data['sort'] = 'name';
            $filter_data['order'] = 'ASC';
        }else{
            $filter_data['sort'] = $this->request->sort;
            $filter_data['order'] = $this->request->order;
        }

        if(!empty($this->request->with) )
        {
            $filter_data['with'] = $this->request->with;
        }

        $filter_data['pagination'] = false;

        $users = $this->UserService->getRows($filter_data);

        foreach ($users as $row) {

            $show_text = '';
            if(!empty($this->request->show_column1) && !empty($this->request->show_column2)){
                $col = $this->request->show_column1;
                $show_text = $row->$col;

                $col = $this->request->show_column2;
                $show_text .= '_'.$row->$col;
            }else{
                $show_text = $row->personal_name . '_' . $row->mobile;
            }

            $json[] = array(
                'label' => $show_text,
                'value' => $row->id,
                'user_id' => $row->id,
                'personal_name' => $row->name,
                'salutation_id' => $row->salutation_id,
                'telephone' => $row->telephone,
                'mobile' => $row->mobile,
                'email' => $row->email,
            );
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }
}
