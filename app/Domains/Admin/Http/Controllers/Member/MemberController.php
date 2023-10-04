<?php

namespace App\Domains\Admin\Http\Controllers\Member;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Libraries\TranslationLibrary;
use App\Domains\Admin\Services\Member\MemberService;
use App\Domains\Admin\Services\Counterparty\OrganizationService;
use App\Domains\Admin\Services\Localization\CountryService;
use App\Domains\Admin\Services\Localization\DivisionService;
use App\Domains\Admin\Services\Localization\AddressService;
use App\Domains\Admin\Services\Catalog\OptionService;

class MemberController extends BackendController
{
    public function __construct(
        private Request $request
        , private MemberService $MemberService
        , private OrganizationService $OrganizationService
        , private CountryService $CountryService
        , private DivisionService $DivisionService
        , private AddressService $AddressService
        , private OptionService $OptionService
        )
    {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/member/member']);
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
            'text' => $this->lang->text_member,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];

        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.member.members.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        $data['list'] = $this->getList();

        $data['list_url'] =route('lang.admin.member.members.list');
        $data['add_url'] = route('lang.admin.member.members.form');
        $data['delete_url'] = route('lang.admin.member.members.delete');

        return view('admin.member.member', $data);
    }

    public function list()
    {
        $data['lang'] = $this->lang;

        $data['form_action'] = route('lang.admin.member.members.list');

        return $this->getList();
    }

    /**
     * Show the list table.
     */
    private function getList()
    {
        $data['lang'] = $this->lang;

        // Prepare query_data for records
        $query_data = $this->getQueries($this->request->query());

        
        // Rows
        $members = $this->MemberService->getMembers($query_data);

        if(!empty($members)){
            foreach ($members as $row) {
                $row->edit_url = route('lang.admin.member.members.form', array_merge([$row->id], $query_data));
            }
        }

        $data['members'] = $members->withPath(route('lang.admin.member.members.list'))->appends($query_data);

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
        $route = route('lang.admin.member.members.list');

        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
        $data['sort_username'] = $route . "?sort=username&order=$order" .$url;
        $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_payment_company'] = $route . "?sort=payment_company&order=$order" .$url;
        $data['sort_date_added'] = $route . "?sort=created_at&order=$order" .$url;

        $data['list_url']   =  route('lang.admin.member.members.list');

        return view('admin.member.member_list', $data);
    }


    public function form($member_id = null)
    {
        $data['lang'] = $this->lang;

        $this->lang->text_form = empty($member_id) ? $this->lang->trans('text_add') : $this->lang->trans('text_edit');

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

        $data['save'] = route('lang.admin.member.members.save');
        $data['back'] = route('lang.admin.member.members.index', $queries);

        // Get Record
        $member = $this->MemberService->findIdOrFailOrNew($member_id);
        
        $data['member']  = $member;

        if(!empty($data['member']) && $member_id == $member->id){
            $data['member_id'] = $member_id;
        }else{
            $data['member_id'] = null;
        }

        // Salutation
        $filter_data = [
            'filter_code' => 'salutation',
            'with' => 'option_values.translation'
        ];
        $salutation = $this->OptionService->getRow($filter_data);
        $data['salutation'] = [
            'option_id' => $salutation->id,
            'name' => $salutation->name,
        ];

        foreach ($salutation->option_values as $option_value) {
            $data['salutation']['option_values'][] = [
                'option_value_id' => $option_value->id,
                'name' => $option_value->name,
            ];
        }

        $data['countries'] = $this->CountryService->getRows(['pagination' => false]);

        $data['states'] = $this->DivisionService->getStates();

        if(!empty($member->shipping_state_id)){
            $data['shipping_cities'] = $this->DivisionService->getCities(['filter_parent_id' => $member->shipping_state_id]);
        }else{
            $data['shipping_cities'] = [];
        }

        return view('admin.member.member_form', $data);
    }

    public function save()
    {
        $data = $this->request->all();

        $json = [];

        // Check member
        //新增時檢查
        if(empty($this->request->member_id)){
            if(!empty($this->request->mobile)){
                $filter_data = [
                    'filter_mobile' => str_replace('-','',$this->request->mobile),
                    'regexp' => false,
                ];
                $member = $this->MemberService->getRow($filter_data);
    
                if(!empty($member)){
                    $json['error']['mobile'] = '這個手機號碼已存在，不可新增。';
                }
            }

            if(!empty($this->request->email)){
                $filter_data = [
                    'filter_email' => trim($this->request->email),
                    'regexp' => false,
                ];
                $member = $this->MemberService->getRow($filter_data);
    
                if(!empty($member)){
                    $json['error']['email'] = '這個 email 已存在，不可新增。';
                }
            }
        }

        $validator = $this->validator($this->request->post());

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
            $result = $this->MemberService->updateOrCreate($data);

            if(empty($result['error'])){
                $json = [
                    'member_id' => $result['member_id'],
                    'success' => $this->lang->text_success,
                    'redirectUrl' => route('lang.admin.member.members.form', $result['member_id']),
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
            foreach ($selected as $member_id) {
                $result = $this->MemberService->deleteMember($member_id);

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


    public function autocomplete()
    {
        $query_data = $this->request->query();

        $json = $this->MemberService->getJsonMembers($query_data);

        return response(json_encode($json))->header('Content-Type','application/json');
    }


    public function validator(array $data)
    {
        return Validator::make($data, [
                //code' =>'nullable|unique:users,code,'.$data['member_id'],
                //'username' =>'nullable|unique:users,username,'.$data['member_id'],
                //'email' =>'nullable|required|unique:users,email,'.$data['member_id'],
                //'mobile' =>'nullable|min:9|max:15|unique:users,mobile,'.$data['member_id'],
                'name' =>'nullable|min:2|max:20',
                // 'first_name' =>'min:2|max:10',
                // 'short_name' =>'nullable|max:10',
                // 'job_title' =>'nullable|max:20',
                // 'password' =>'nullable|min:6|max:20',
            ],[
                //'code.unique' => $this->lang->error_code_exists,
                // 'username.required' => $this->lang->error_username,
                // 'username.unique' => $this->lang->error_username_exists,
                //'email.required' => $this->lang->error_email,
                //'email.unique' => $this->lang->error_email_exists,
                'name.*' => $this->lang->error_name,
                // 'mobile.required' => $this->lang->error_mobile,
                // 'mobile.min' => $this->lang->error_mobile,
                // 'mobile.max' => $this->lang->error_mobile,
                 //'mobile.unique' => $this->lang->error_mobile_exists,
                // 'first_name.*' => $this->lang->error_first_name,
                // 'short_name.*' => $this->lang->error_short_name,
                // 'job_title.*' => $this->lang->error_job_title,
                // 'password.*' => $this->lang->error_password,
        ]);
    }
}
