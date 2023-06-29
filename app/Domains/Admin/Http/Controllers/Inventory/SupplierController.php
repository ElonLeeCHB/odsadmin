<?php

namespace App\Domains\Admin\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Libraries\TranslationLibrary;
use App\Domains\Admin\Services\Organization\OrganizationService;

class SupplierController extends Controller
{
    private $lang;
    private $request;
    private $OrganizationService;

    public function __construct(Request $request, OrganizationService $OrganizationService)
    {
        $this->request = $request;
        $this->OrganizationService = $OrganizationService;

        // Translations
        $this->lang = (new TranslationLibrary())->getTranslations(['admin/common/common','admin/organization/organization','admin/inventory/supplier']);
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
            'text' => $this->lang->text_product,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];
        
        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.inventory.categories.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        $data['list'] = $this->getList();

        $data['list_url'] = route('lang.admin.inventory.suppliers.list'); //本參數在 getList() 也必須存在。
        $data['add_url'] = route('lang.admin.inventory.suppliers.form');
        $data['delete_url'] = route('lang.admin.inventory.suppliers.delete');

        return view('admin.inventory.supplier', $data);
    }

    public function list()
    {
        return $this->getList();
    }

    public function getList()
    {
        $data['lang'] = $this->lang;

        // Prepare link for action
        $queries = [];

        if(!empty($this->request->query('sort'))){
            $sort = $queries['sort'] = $this->request->input('sort');
        }else{
            $sort = $queries['sort'] = 'id';
        }

        if(!empty($this->request->query('order'))){
            $order = $queries['order'] = $this->request->query('order');
        }else{
            $order = $queries['order'] = 'asc';
        }

        if(!empty($this->request->query('page'))){
            $queries['page'] = $this->request->input('page');
        }else{
            $queries['page'] = 1;
        }

        if(!empty($this->request->query('limit'))){
            $queries['limit'] = $this->request->query('limit');
        }

        foreach($this->request->all() as $key => $value){
            if(strpos($key, 'filter_') !== false){
                $queries[$key] = $value;
            }
        }

        $queries['filter_is_supplier'] = 1;

        // Rows
        $suppliers = $this->OrganizationService->getOrganizations($queries,1);

        foreach ($suppliers as $row) {
            $row->edit_url = route('lang.admin.inventory.suppliers.form', array_merge([$row->id], $queries));
        }

        $data['suppliers'] = $suppliers;

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
        $route = route('lang.admin.inventory.suppliers.list');

        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
        $data['sort_code'] = $route . "?sort=code&order=$order" .$url;
        $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_short_name'] = $route . "?sort=short_name&order=$order" .$url;
        
        $data['list_url'] = route('lang.admin.inventory.suppliers.list');
        
        return view('admin.inventory.supplier_list', $data);
    }

    public function form($supplier_id = null)
    {
        $data['lang'] = $this->lang;

        $this->lang->text_form = empty($supplier_id) ? $this->lang->trans('text_add') : $this->lang->trans('text_edit');

        // Breadcomb
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_home,
            'href' => route('lang.admin.dashboard'),
        ];

        $breadcumbs[] = (object)[
            'text' => $this->lang->text_inventory,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];

        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.inventory.suppliers.index'),
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

        $data['save_url'] = route('lang.admin.inventory.suppliers.save');
        $data['back_url'] = route('lang.admin.inventory.suppliers.index', $queries);        

        // Get Record
        $supplier = $this->OrganizationService->findIdOrFailOrNew($supplier_id);

        $data['meta_data'] = $this->OrganizationService->setMetaDataset($supplier);

        $supplier->parent_name = $supplier->parent->name ?? '';

        $data['supplier']  = $supplier;

        if(!empty($data['supplier']) && $supplier_id == $supplier->id){
            $data['supplier_id'] = $supplier_id;
        }else{
            $data['supplier_id'] = null;
        }

        return view('admin.inventory.supplier_form', $data);
    }

    public function save()
    {
        $postData = $this->request->post();

        $json = [];
        
        $postData['is_supplier'] = 1;
        $postData['organization_id'] = $postData['supplier_id'];

        $validator = $this->validator($postData);

        if($validator->fails()){
            $messages = $validator->errors()->toArray();
            foreach ($messages as $key => $rows) {
                $json['error'][$key] = $rows[0];
            }
        }

        // 檢查欄位
        // do something        
        if(isset($json['error']) && !isset($json['error']['warning'])) {
            $json['error']['warning'] = $this->lang->error_warning;
        }

        if(!$json) {
            $result = $this->OrganizationService->updateOrCreate($postData);

            if(empty($result['error']) && !empty($result['organization_id'])){
                $json = [
                    'supplier_id' => $result['organization_id'],
                    'success' => $this->lang->text_success,
                    'redirectUrl' => route('lang.admin.inventory.suppliers.form', $result['organization_id']),
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

    }

    public function validator(array $data)
    {
        return Validator::make($data, [
                'organization_id' =>'nullable|integer',
                'code' =>'nullable|unique:organizations,code,'.$data['organization_id'],
                'name' =>'min:2|max:50',
                'short_name' =>'min:2|max:10',
                'mobile' =>'nullable|min:9|max:20',
                'telephone' =>'nullable|min:7|max:20',
            ],[
                'organization_id.*' => $this->lang->error_organization_id,
                'code.*' => $this->lang->error_code,
                'name.*' => $this->lang->error_name,
                'short_name.*' => $this->lang->error_short_name,
                'mobile.*' => $this->lang->error_mobile,
                'telephone.*' => $this->lang->error_telephone,
        ]);
    }
}