<?php

namespace App\Domains\Admin\Http\Controllers\Counterparty;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Domains\Admin\Services\Counterparty\SupplierService;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Domains\Admin\Services\Localization\DivisionService;
use App\Helpers\Classes\DataHelper;
use App\Http\Resources\Inventory\SupplierResource;
use App\Http\Resources\Inventory\SupplierCollection;
use App\Helpers\Classes\OrmHelper;
use App\Models\Counterparty\Supplier;

class SupplierController extends BackendController
{
    public function __construct(protected Request $request, protected SupplierService $SupplierService, protected TermRepository $TermRepository, protected DivisionService $DivisionService)
    {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/counterparty/organization','admin/counterparty/supplier']);
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
            'text' => $this->lang->text_supplier,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];

        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.counterparty.suppliers.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        $data['list'] = $this->getList();

        $data['list_url'] = route('lang.admin.counterparty.suppliers.list');
        $data['add_url'] = route('lang.admin.counterparty.suppliers.form');
        $data['delete_url'] = route('lang.admin.counterparty.suppliers.destroy');

        return view('admin.counterparty.supplier', $data);
    }

    public function list()
    {
        return $this->getList();
    }

    public function getList()
    {
        $data['lang'] = $this->lang;

        // Prepare query_data for records
        $query_data  = $this->url_data ?? [];

        // Records
        $suppliers = $this->SupplierService->getSuppliers($query_data);

        if(!empty($suppliers)){
            foreach ($suppliers as $row) {
                $row->edit_url = route('lang.admin.counterparty.suppliers.form', array_merge([$row->id], $data));
            }
        }

        foreach ($suppliers as $row) {
            $row->edit_url = route('lang.admin.counterparty.suppliers.form', array_merge([$row->id], $query_data));
            $row->payment_term_name = $row->payment_term->name ?? '';
        }

        $data['suppliers'] = $suppliers->withPath(route('lang.admin.counterparty.suppliers.list'))->appends($query_data);

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

        $url = '';

        foreach($query_data as $key => $value){
            if(is_string($value)){
                $url .= "&$key=$value";
            }
        }


        //link of table header for sorting
        $route = route('lang.admin.counterparty.suppliers.list');

        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
        $data['sort_code'] = $route . "?sort=code&order=$order" .$url;
        $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_short_name'] = $route . "?sort=short_name&order=$order" .$url;
        $data['sort_tax_type_code'] = $route . "?sort=tax_type_code&order=$order" .$url;
        $data['sort_telephone'] = $route . "?sort=telephone&order=$order" .$url;

        $data['list_url'] = route('lang.admin.counterparty.suppliers.list');

        return view('admin.counterparty.supplier_list', $data);
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
            'text' => $this->lang->text_supplier,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];

        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.counterparty.suppliers.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        // Prepare link for save, back
        $queries = [];

        foreach($this->request->all() as $key => $value){
            if(strpos($key, 'filter_') !== false){
                $queries[$key] = $value;
            }
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

        if(!empty($this->request->query('page'))){
            $queries['page'] = $this->request->query('page');
        }else{
            $queries['page'] = 1;
        }

        $data['save_url'] = route('lang.admin.counterparty.suppliers.save');
        $data['back_url'] = route('lang.admin.counterparty.suppliers.index', $queries);
        $data['banks_url'] = route('lang.admin.counterparty.banks.autocomplete');


        // Get Record
        $result = $this->SupplierService->findIdOrFailOrNew($supplier_id);

        if(empty($result['error']) && !empty($result['data'])){
            $supplier = $result['data'];
        }else if(!empty($result['error'])){
            return response(json_encode(['error' => $result['error']]))->header('Content-Type','application/json');
        }
        unset($result);

        $supplier = $this->SupplierService->setMetasToRow($supplier);

        $supplier->parent_name = $supplier->parent->name ?? '';

        $supplier->payment_term_name = $supplier->payment_term->name ?? '';
        $supplier = $this->SupplierService->unsetRelation($supplier, ['payment_term']);

        // Default column value
        if(empty($supplier->id)){
            $supplier->is_active = 1;
        }

        $data['supplier']  = $supplier->toCleanObject();

        if(!empty($data['supplier']) && $supplier_id == $supplier->id){
            $data['supplier_id'] = $supplier_id;
        }else{
            $data['supplier_id'] = null;
        }

        $data['payment_term_autocomplete_url'] = route('lang.admin.common.payment_terms.autocomplete');

        $data['tax_types'] = $this->SupplierService->getCodeKeyedTermsByTaxonomyCode('tax_type',toArray:false);

        $data['states'] = $this->DivisionService->getStates();

        if(!empty($supplier->shipping_state_id)){
            $data['shipping_cities'] = $this->DivisionService->getCities(['equal_parent_id' => $supplier->shipping_state_id]);
        }else{
            $data['shipping_cities'] = [];
        }

        return view('admin.counterparty.supplier_form', $data);
    }

    public function save()
    {
        $postData = $this->request->post();

        $json = [];

        // 檢查欄位
        $validator = $this->validator($postData);

        if($validator->fails()){
            $messages = $validator->errors()->toArray();

            foreach ($messages as $key => $rows) {
                // 處理多語欄位：translations.zh_Hant.name -> name-zh_Hant
                if (preg_match('/^translations\.([^.]+)\.(.+)$/', $key, $matches)) {
                    $locale = $matches[1];      // zh_Hant, en, 等
                    $field = $matches[2];       // name, description, 等
                    $json['errors'][$field . '-' . $locale] = $rows[0];
                }
                // 一般欄位
                else {
                    $json['errors'][$key] = $rows[0];
                }
            }

            $json['error'] = $this->lang->error_warning;
            return response()->json($json, 422);
        }

        $result = $this->SupplierService->saveSupplier($postData, $postData['supplier_id'] ?? null);

        $json = [
            'supplier_id' => $result['id'],
            'success' => $this->lang->text_success,
            'redirectUrl' => route('lang.admin.counterparty.suppliers.form', $result['id']),
        ];

        return response()->json($json);
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
            $result = $this->SupplierService->destroy($selected);

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

    public function validator(array $data)
    {
        // 取得當前語言（假設是必填語言）
        $current_locale = app()->getLocale();


        return Validator::make($data, [
                'supplier_id' =>'nullable|integer',
                'code' =>'nullable|unique:organizations,code,'.$data['supplier_id'],
                'name' =>'required|min:2|max:50|unique:organizations,name,'.$data['supplier_id'],
                'short_name' =>'required|min:2|max:50|unique:organizations,short_name,'.$data['supplier_id'],
                'mobile' =>'nullable|min:9|max:20',
                'telephone' =>'nullable|min:7|max:20',
                'tax_type_code' =>'required',

            ],[
                'supplier_id.*' => $this->lang->error_supplier_id,
                'code.*' => $this->lang->error_code,
                'name.*' => '請輸入 2 - 50 個中文字',
                'short_name.*' => '請輸入 2 - 50 個中文字',
                'mobile.*' => $this->lang->error_mobile,
                'telephone.*' => $this->lang->error_telephone,
                'tax_type_code.*' => '請選擇課稅別',
        ]);
    }

    public function rowsWithMetaData($rows)
    {
        foreach ($rows as $key => $row) {
            $metas = $row->metas;
            foreach ($metas as $meta_row) {
                $row->{$meta_row->meta_key} = $meta_row->meta_value;
            }
        }
        return $rows;
    }

    public function autocomplete()
    {
        $json = [];

        $query_data  = $this->url_data;

        $filter_data = $query_data;
        $filter_data['with'] = ['payment_term', 'metas'];

        $hasFilterOrEqual = false;

        foreach ($filter_data as $key => $value) {
            if ((str_starts_with($key, 'filter_') == true || str_starts_with($key, 'equal_') == true) && !empty($value)) {
                $hasFilterOrEqual = true;
                break;
            }
        }

        // 不存在任何查詢
        if($hasFilterOrEqual !== true){
            $cache_name = 'cache/counterparty/suppliers/often_used.json';
            $suppliers = DataHelper::getJsonFromStoragNew($cache_name);
        }
        else{
            $suppliers = $this->SupplierService->getSuppliers($filter_data);

            if(empty($suppliers)){
                return false;
            }

            $suppliers = $this->rowsWithMetaData($suppliers);
        }

        // 稅別
        $data['tax_types'] = $this->SupplierService->getCodeKeyedTermsByTaxonomyCode('tax_type',toArray:false);

        foreach ($suppliers ?? [] as $row) {
            $json[] = array(
                'label' => $row->name . ', ' . $row->tax_id_num,
                'value' => $row->id,
                'supplier_id' => $row->id,
                'supplier_name' => $row->name,
                'short_name' => $row->short_name,
                'tax_id_num' => $row->tax_id_num,
                'tax_type_code' => $row->tax_type_code,
            );
        }

        array_unshift($json,[
            'value' => 0,
            'label' => ' -- ',
            'supplier_id' => '',
            'supplier_name' => '',
            'short_name' => '',
            'tax_id_num' => '',
            'tax_type_code' => '',
        ]);

        return response(json_encode($json))->header('Content-Type','application/json');
    }
}
