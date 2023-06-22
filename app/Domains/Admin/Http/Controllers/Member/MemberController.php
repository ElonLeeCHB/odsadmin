<?php

namespace App\Domains\Admin\Http\Controllers\Member;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Libraries\TranslationLibrary;
use App\Domains\Admin\Services\Member\MemberService;
use App\Domains\Admin\Services\Organization\OrganizationService;
use App\Domains\Admin\Services\Localization\CountryService;
use App\Domains\Admin\Services\Localization\DivisionService;
use App\Domains\Admin\Services\Localization\AddressService;
use App\Domains\Admin\Services\Common\OptionService;
use Auth;

class MemberController extends Controller
{
    private $lang;
    
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
        // Translations
        $groups = [
            'admin/common/common',
            'admin/member/member',
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

        unset($queries['sort']);
        unset($queries['order']);

        //$data['action'] = route('lang.admin.member.members.massDelete');

        // Rows
        $members = $this->MemberService->getMembers($queries);

        if(!empty($members)){
            foreach ($members as $row) {
                $row->edit_url = route('lang.admin.member.members.form', array_merge([$row->id], $queries));
            }
        }

        $data['members'] = $members->withPath(route('lang.admin.member.members.list'))->appends($queries);

        // Prepare links for list table's header
        if($order == 'ASC'){
            $order = 'DESC';
        }else{
            $order = 'ASC';
        }

        $data['sort'] = strtolower($sort);
        $data['order'] = strtolower($order);

        $url = '';

        foreach($queries as $key => $value){
            $url .= "&$key=$value";
        }

        //link of table header for sorting
        $route = route('lang.admin.member.members.list');
        $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_email'] = $route . "?sort=email&order=$order" .$url;
        $data['sort_date_added'] = $route . "?sort=created_at&order=$order" .$url;

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

        $members = $this->MemberService->getMembers($filter_data);

        foreach ($members as $row) {
            $row = $this->MemberService->parseShippingAddress($row);

            $show_text = '';
            if(!empty($this->request->show_column1) && !empty($this->request->show_column2)){
                $col = $this->request->show_column1;
                $show_text = $row->$col;

                $col = $this->request->show_column2;
                $show_text .= '_'.$row->$col;
            }else{
                $show_text = $row->personal_name . '_' . $row->mobile;
            }

            $has_order = 0;
            if(count($row->orders)){
                $has_order = 1;
            }

            $json[] = array(
                'label' => $show_text,
                'value' => $row->id,
                'customer_id' => $row->id,
                'personal_name' => $row->name,
                'salutation_id' => $row->salutation_id,
                'telephone' => $row->telephone,
                'mobile' => $row->mobile,
                'email' => $row->email,
                'payment_company' => $row->payment_company,
                'payment_department' => $row->payment_department,
                'payment_tin' => $row->payment_tin,
                'shipping_personal_name' => $row->shipping_personal_name,
                'shipping_phone' => $row->shipping_phone,
                'shipping_company' => $row->shipping_company,
                'shipping_state_id' => $row->shipping_state_id,
                'shipping_city_id' => $row->shipping_city_id,
                'shipping_road_id' => $row->shipping_road_id,
                'shipping_road' => $row->shipping_road,
                'shipping_address1' => $row->shipping_address1,
                'shipping_lane' => $row->shipping_lane,
                'shipping_alley' => $row->shipping_alley,
                'shipping_no' => $row->shipping_no,
                'shipping_floor' => $row->shipping_floor,
                'shipping_room' => $row->shipping_room,
                'has_order' => $has_order,
            );
        }

        array_unshift($json,[
            'value' => 0,
            'label' => ' -- ',
            'customer_id' => '',
            'personal_name' => '',
            'salutation_id' => '',
            'telephone' => '',
            'mobile' => '',
            'email' => '',
            'payment_company' => '',
            'payment_department' => '',
            'payment_tin' => '',
            'shipping_personal_name' => '',
            'shipping_phone' => '',
            'shipping_company' => '',
            'shipping_state_id' => '',
            'shipping_city_id' => '',
            'shipping_road_id' => '',
            'shipping_road' => '',
            'shipping_address1' => '',
            'shipping_lane' => '',
            'shipping_alley' => '',
            'shipping_no' => '',
            'shipping_floor' => '',
            'shipping_room' => '',
        ]);

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
