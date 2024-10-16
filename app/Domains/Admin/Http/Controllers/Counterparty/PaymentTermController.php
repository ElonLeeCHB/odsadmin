<?php

namespace App\Domains\Admin\Http\Controllers\Counterparty;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Libraries\TranslationLibrary;
use App\Repositories\Eloquent\Localization\LanguageRepository;
use App\Domains\Admin\Services\Counterparty\PaymentTermService;

class PaymentTermController extends BackendController
{
    public function __construct(private Request $request, private PaymentTermService $PaymentTermService)
    {
        parent::__construct();
        
        $this->getLang(['admin/common/common','admin/common/payment_term']);
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
            'href' => route('lang.admin.common.payment_terms.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        $data['list'] = $this->getList();

        $data['list_url'] = route('lang.admin.common.payment_terms.list'); //本參數在 getList() 也必須存在。
        $data['add_url'] = route('lang.admin.common.payment_terms.form');
        $data['delete_url'] = route('lang.admin.common.payment_terms.destroy');
        
        return view('admin.common.payment_term', $data);
    }

    public function list()
    {
        return $this->getList();
    }

    private function getList()
    {
        $data['lang'] = $this->lang;

        // Prepare query_data for records
        $query_data = $this->getQueries($this->request->query());

        // Rows
        $payment_terms = $this->PaymentTermService->getPaymentTerms($query_data);

        $trans_type_array = [
            1 => '1:銷售',
            2 => '2:採購',
        ];

        $trans_due_date_basis_array = [
            1 => '1:來源單據日',
            2 => '2:出貨日(到貨日)',
            3 => '3:次月初',
        ];

        foreach ($payment_terms as $row) {
            $row->edit_url = route('lang.admin.common.payment_terms.form', array_merge([$row->id], $query_data));
            $row->due_date_basis_name = $trans_due_date_basis_array[$row->due_date_basis];
            $row->type_name = $trans_type_array[$row->type];
            $row->is_active_text = $row->is_active ? $this->lang->text_enabled : $this->lang->text_disabled;
        }
        
        $data['payment_terms'] = $payment_terms->withPath(route('lang.admin.common.payment_terms.list'))->appends($query_data);

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

        $url = '';

        foreach($query_data as $key => $value){
            $url .= "&$key=$value";
        }
        
        //link of table header for sorting
        $route = route('lang.admin.common.payment_terms.list');

        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
        $data['sort_type'] = $route . "?sort=type&order=$order" .$url;
        $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_due_date_basis'] = $route . "?sort=due_date_basis&order=$order" .$url;
        $data['sort_sort_order'] = $route . "?sort=sort_order&order=$order" .$url;
        $data['sort_is_active'] = $route . "?sort=is_active&order=$order" .$url;
        
        $data['list_url'] = route('lang.admin.common.payment_terms.list');
        
        return view('admin.common.payment_term_list', $data);
    }

    public function form($payment_term_id = null)
    {
        $data['lang'] = $this->lang;

        $this->lang->text_form = empty($payment_term_id) ? $this->lang->trans('text_add') : $this->lang->trans('text_edit');

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
            'href' => route('lang.admin.common.payment_terms.index'),
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

        $data['save_url'] = route('lang.admin.common.payment_terms.save');
        $data['back_url'] = route('lang.admin.common.payment_terms.index', $queries);        

        // Get Record
        $result = $this->PaymentTermService->findIdOrFailOrNew($payment_term_id);

        if(empty($result['error']) && !empty($result['data'])){
            $payment_term = $result['data'];
        }else if(!empty($result['error'])){
            return response(json_encode(['error' => $result['error']]))->header('Content-Type','application/json');
        }
        unset($result);

        $data['payment_term']  = $payment_term;

        if(!empty($data['payment_term']) && $payment_term_id == $payment_term->id){
            $data['payment_term_id'] = $payment_term_id;
        }else{
            $data['payment_term_id'] = null;
        }


        return view('admin.common.payment_term_form', $data);
    }

    public function save()
    {
        $data = $this->request->all();

        $json = [];

        if(empty($data['name']) || mb_strlen($data['name']) < 2 || mb_strlen($data['name']) > 50){
            $json['error']['name'] = $this->lang->error_name;
        }

        if(empty($data['type'])){
            $json['error']['type'] = $this->lang->error_type;
        }

        if(empty($data['due_date_basis'])){
            $json['error']['due_date_basis'] = $this->lang->error_due_date_basis;
        }

        // 檢查欄位
        // do something        
        if(isset($json['error']) && !isset($json['error']['warning'])) {
            $json['error']['warning'] = $this->lang->error_warning;
        }

        if(!$json) {
            $result = $this->PaymentTermService->updateOrCreate($data);

            if(empty($result['error']) && !empty($result['payment_term_id'])){
                $json = [
                    'payment_term_id' => $result['payment_term_id'],
                    'success' => $this->lang->text_success,
                    'redirectUrl' => route('lang.admin.common.payment_terms.form', $result['payment_term_id']),
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
            $result = $this->PaymentTermService->destroy($selected);

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
        $query_data = $this->request->query();
        
        $filter_data['equal_type'] = 2;

        if(isset($query_data['filter_name'])){
            $filter_data['filter_name'] = $query_data['filter_name'];
        }

        if(isset($query_data['is_active'])){
            $filter_data['equal_is_active'] = $query_data['is_active'];
        }else{
            $filter_data['equal_is_active'] = 1;
        }

        $filter_data['limit'] = 0;
        $filter_data['pagination'] = false;

        $rows = $this->PaymentTermService->getPaymentTerms($filter_data);
        
        $json = [];

        $json[] = [
            'label' => '---請選擇---',
            'value' => 0,
            'name' => '',
        ];

        foreach ($rows as $row) {
            $json[] = array(
                'label' => '2:採購-' . $row->name,
                'value' => $row->id,
                'name' => $row->name,
            );
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }
}