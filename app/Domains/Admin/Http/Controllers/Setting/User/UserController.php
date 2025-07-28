<?php

namespace App\Domains\Admin\Http\Controllers\Setting\User;

use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use Illuminate\Http\Request;
use App\Domains\Admin\Services\Setting\User\UserService;

class UserController extends BackendController
{
    public function __construct(protected Request $request, protected UserService $UserService)
    {
        parent::__construct();
        
        $this->getLang(['admin/common/common','admin/admin/user']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['lang'] = $this->lang;
        
        $query_data  = $this->url_data;

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
            'href' => route('lang.admin.setting.user.users.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        $data['list'] = $this->getList();
        
        $data['list_url']   =  route('lang.admin.setting.user.users.list');
        $data['add_url']    = route('lang.admin.setting.user.users.form');
        $data['delete_url'] = route('lang.admin.setting.user.users.destroy');
        
        //Filters
        $data['filter_keyname'] = $query_data['filter_keyname'] ?? '';
        $data['filter_phone'] = $query_data['filter_phone'] ?? '';
        $data['equal_is_admin'] = $query_data['equal_is_admin'] ?? 1;
        $data['equal_is_active'] = $query_data['equal_is_active'] ?? 1;

        return view('admin.setting.user', $data);
    }

    public function list()
    {
        $data['lang'] = $this->lang;

        $data['form_action'] = route('lang.admin.setting.user.users.list');

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
        $query_data  = $this->url_data;

        // if(isset($query_data['equal_is_admin']) && $query_data['equal_is_admin'] == 0){
        //     $query_data['whereDoesntHave']['metas'] = ['is_admin' => 1];
        //     unset($query_data['equal_is_admin']);
        // }


        // Rows
        $users = $this->UserService->getUsers($query_data);

        if(!empty($users)){
            foreach ($users as $row) {
                $row->edit_url = route('lang.admin.setting.user.users.form', array_merge([$row->id], $query_data));
            }
        }

        $data['users'] = $users->withPath(route('lang.admin.setting.user.users.list'))->appends($query_data);


        // Prepare links for list table's header
        if(isset($query_data['order']) && $query_data['order'] == 'ASC'){
            $order = 'DESC';
        }else{
            $order = 'ASC';
        }
        
        $data['sort'] = strtolower($query_data['sort'] ?? '');
        $data['order'] = strtolower($order);

        unset($query_data['sort']);
        unset($query_data['order']);
        unset($query_data['with']);
        unset($query_data['whereDoesntHave']);

        $url = '';

        foreach($query_data as $key => $value){
            if(is_string($value)){
                $url .= "&$key=$value";
            }
        }

        //link of table header for sorting
        $route = route('lang.admin.setting.user.users.list');

        $data['sort_username'] = $route . "?sort=username&order=$order" .$url;
        $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_email'] = $route . "?sort=email&order=$order" .$url;
        $data['sort_created_at'] = $route . "?sort=created_at&order=$order" .$url;
        
        $data['list_url'] = route('lang.admin.setting.user.users.list');

        return view('admin.setting.user_list', $data);
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
            'href' => route('lang.admin.setting.user.users.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        // Prepare link for save, back
        $queries  = $this->url_data;

        $data['save_url'] = route('lang.admin.setting.user.users.save');
        $data['back_url'] = route('lang.admin.setting.user.users.index', $queries);

        // Get Record
        $result = $this->UserService->findIdOrFailOrNew($user_id);

        if(empty($result['error']) && !empty($result['data'])){
            $user = $result['data'];
        }else if(!empty($result['error'])){
            return response(json_encode(['error' => $result['error']]))->header('Content-Type','application/json');
        }
        unset($result);

        $user = $this->UserService->setMetasToRow($user);

        $data['user']  = $user;

        if(!empty($data['user']) && $user_id == $user->id){
            $data['user_id'] = $user_id;
        }else{
            $data['user_id'] = null;
        }

        return view('admin.setting.user_form', $data);
    }


    public function save()
    {
        try {
            $data = $this->request->all();

            $json = [];

            // Check user
            $validator = $this->UserService->validator($this->request->post());

            if ($validator->fails()) {
                $messages = $validator->errors()->toArray();

                foreach ($messages as $key => $rows) {
                    $json['error'][$key] = $rows[0];
                }
            }

            if (isset($json['error']) && !isset($json['error']['warning'])) {
                $json['error']['warning'] = $this->lang->error_warning;
            }

            if(!empty($json)){
                return response(json_encode($json), 400);
            }

            $result = $this->UserService->updateOrCreate($data);

            $json = [
                'success' => $this->lang->text_success,
                'user_id' => $result['data']['user_id'],
            ];

            return response(json_encode($json));
        } catch (\Throwable $th) {
            return response(['error' => $th->getMessage()], 500);
        }
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
            $result = $this->UserService->destroy($selected);

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
