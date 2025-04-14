<?php

namespace App\Domains\Admin\Http\Controllers\Counterparty;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\TranslationLibrary;
use App\Domains\Admin\Services\Member\MemberService;
use App\Domains\Admin\Services\Counterparty\OrganizationService;
use Auth;

class OrganizationController extends Controller
{
    protected $lang;

    public function __construct(private Request $request
        , private MemberService $MemberService
        , private OrganizationService $OrganizationService)
    {

        $groups = [
            'admin/common/common',
            'admin/member/organization',
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
            'href' => route('lang.admin.member.organizations.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;
        
        $data['list'] = $this->getList();

        return view('admin.member.organization', $data);
    }

    public function list()
    {
        $data['lang'] = $this->lang;
        
        $data['form_action'] = route('lang.admin.member.organizations.list');

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

        // Prepare query_data for records
        $query_data = [];

        if(!empty($this->request->query('page'))){
            $page = $query_data['page'] = $this->request->input('page');
        }else{
            $page = $query_data['page'] = 1;
        }

        if(!empty($this->request->query('sort'))){
            $sort = $query_data['sort'] = $this->request->input('sort');
        }else{
            $sort = $query_data['sort'] = 'id';
        }

        if(!empty($this->request->query('order'))){
            $order = $query_data['order'] = $this->request->query('order');
        }else{
            $order = $query_data['order'] = 'DESC';
        }

        if(!empty($this->request->query('limit'))){
            $limit = $query_data['limit'] = $this->request->query('limit');
        }

        foreach($this->request->all() as $key => $value){
            if(strpos($key, 'filter_') !== false){
                $query_data[$key] = $value;
            }
        }

        //$data['action'] = route('lang.admin.member.members.massDelete');

        // Rows
        $organizations = $this->OrganizationService->getRows($query_data);

        $data['organizations'] = $organizations->withPath(route('lang.admin.member.organizations.list'))->appends($query_data);

        // Prepare links for list table's header
        if($order == 'ASC'){
            $order = 'DESC';
        }else{
            $order = 'ASC';
        }
        
        $data['sort'] = strtolower($sort);
        $data['order'] = strtolower($order);

        unset($query_data['sort']);
        unset($query_data['order']);

        $url = '';

        foreach($query_data as $key => $value){
            if(is_string($value)){
                $url .= "&$key=$value";
            }
        }
        
        //link of table header for sorting        
        $route = route('lang.admin.member.organizations.list');
        $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_short_name'] = $route . "?sort=sohrt_name&order=$order" .$url;
        
        return view('admin.member.organization_list', $data);
    }

    
    public function form($organization_id = null)
    {
        $data['lang'] = $this->lang;
  
        $this->lang->text_form = empty($organization_id) ? $this->lang->trans('text_add') : $this->lang->trans('text_edit');

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
            'href' => route('lang.admin.member.organizations.index'),
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

        $data['save'] = route('lang.admin.member.organizations.save');
        $data['back'] = route('lang.admin.member.organizations.index', $queries);

        // Get Record
        $result = $this->OrganizationService->findIdOrFailOrNew($organization_id);

        if(!empty($result['data'])){
            $organization = $result['data'];
        }else if(!empty($result['error'])){
            return response(json_encode(['error' => $result['error']]))->header('Content-Type','application/json');
        }
        unset($result);
        
        $data['organization']  = $organization;

        if(!empty($data['organization']) && $organization_id == $organization->id){
            $data['organization_id'] = $organization_id;
        }else{
            $data['organization_id'] = null;
        }

        return view('admin.member.organization_form', $data);
    }

    
    public function save()
    {
        $data = $this->request->all();

        $json = [];
        
        $validator = $this->OrganizationService->validator($this->request->post());

        if($validator->fails()){
            $messages = $validator->errors()->toArray();
            foreach ($messages as $key => $rows) {
                $json['error'][$key] = $rows[0];
            }
        }

        if (isset($json['error']) && !isset($json['error']['warning'])) {
            $json['error']['warning'] = $this->lang->error_warning;
        }

        if(!$json) {
            $result = $this->OrganizationService->updateOrCreate($data);

            if(empty($result['error'])){
                $json['organization_id'] = $result['data']['organization_id'];
                $json['success'] = $this->lang->text_success;
            }else{
                //Only for admin(developers)
                $user_id = Auth::user()->id;
                if($user_id == 1){
                    $json['error'] = $result['error'];
                }
                //For users
                else{
                    $json['error'] = $this->lang->text_fail;
                }
            }
        }
        
       return response(json_encode($json))->header('Content-Type','application/json');
    }
    

    public function autocomplete()
    {
        $json = [];

        $filter_data = array(
            'filter_mixed_name'   => $this->request->filter_mixed_name,
            'filter_contact'   => $this->request->filter_contact,
            'filter_contact_phone'   => $this->request->filter_contact_phone,
        );

        if (!empty($this->request->sort)) {
            if($this->request->sort =='name'){
                $filter_data['sort'] = '.name';
            } else if($this->request->sort =='short_name'){
                $filter_data['sort'] = '.short_name';
            }
        }

        $rows = $this->OrganizationService->getRows($filter_data);

        if(empty($rows)){
            return false;
        }

        foreach ($rows as $row) {
            $json[] = array(
                'organization_id' => $row->id,
                'name' => $row->name,
                //'short_name' => $row->short_name,
            );
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }

}