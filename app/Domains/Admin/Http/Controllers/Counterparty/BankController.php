<?php

namespace App\Domains\Admin\Http\Controllers\Counterparty;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Domains\Admin\Services\Counterparty\BankService;

class BankController extends BackendController
{
    public function __construct(
        private Request $request
        , private BankService $BankService
    )
    {
        parent::__construct();
        
        $this->getLang(['admin/common/common','admin/counterparty/bank']);
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
            'text' => $this->lang->text_common,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];
        
        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.counterparty.banks.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        $data['list'] = $this->getList();

        $data['list_url'] = route('lang.admin.counterparty.banks.list');
        $data['add_url'] = route('lang.admin.counterparty.banks.form');
        $data['delete_url'] = route('lang.admin.counterparty.banks.destroy');

        return view('admin.counterparty.bank', $data);
    }

    public function list()
    {
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
        $query_data = $this->resetUrlData($this->request->query());

        // Rows
        $institutions = $this->BankService->getRows($query_data);

        foreach ($institutions as $row) {
            $row->edit_url = route('lang.admin.counterparty.banks.form', array_merge([$row->id], $query_data));
        }
        $data['institutions'] = $institutions->withPath(route('lang.admin.counterparty.banks.list'))->appends($query_data);

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
        $route = route('lang.admin.counterparty.banks.list');

        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
        $data['sort_code'] = $route . "?sort=code&order=$order" .$url;
        $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_short_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_is_active'] = $route . "?sort=is_active&order=$order" .$url;
        $data['sort_taxonomy_name'] = $route . "?sort=taxonomy_name&order=$order" .$url;
        
        $data['list_url'] = route('lang.admin.counterparty.banks.list');
        
        return view('admin.counterparty.bank_list', $data);
    }


    public function form($institution_id = null)
    {
        $data['lang'] = $this->lang;

        $this->lang->text_form = empty($term_id) ? $this->lang->trans('text_add') : $this->lang->trans('text_edit');

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
            'href' => route('lang.admin.common.terms.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        // Prepare link for save, back
        $queries = $this->resetUrlData($this->request->query());

        $data['save_url'] = route('lang.admin.counterparty.banks.save');
        $data['back_url'] = route('lang.admin.counterparty.banks.index', $queries);   
        $data['autocomplete_url'] = route('lang.admin.counterparty.banks.autocomplete');

        // Get Record
        $result = $this->BankService->findIdOrFailOrNew($institution_id);

        if(!empty($result['data'])){
            $institution = $result['data'];
        }else if(!empty($result['error'])){
            return response(json_encode(['error' => $result['error']]))->header('Content-Type','application/json');
        }
        unset($result);

        $data['institution']  = $institution;

        if(!empty($data['institution']) && $institution_id == $institution->id){
            $data['institution_id'] = $institution_id;
        }else{
            $data['institution_id'] = null;
        }

        return view('admin.counterparty.bank_form', $data);
    }


    public function save()
    {
        $data = $this->request->all();

        $json = [];

        // 檢查欄位
        // do something        
        if(isset($json['error']) && !isset($json['error']['warning'])) {
            $json['error']['warning'] = $this->lang->error_warning;
        }

        if(!$json) {
            $result = $this->BankService->updateOrCreate($data);

            if(empty($result['error'])){
                if(isset($result['row_id'])){
                    $json = [
                        'institution_id' => $result['row_id'],
                        'success' => $this->lang->text_success,
                        'redirectUrl' => route('lang.admin.counterparty.banks.form', $result['row_id']),
                    ];
                }else{
                    $json['error'] = $this->lang->text_fail;
                }
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
            $result = $this->BankService->destroy($selected);

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
        $queries = $this->resetUrlData($this->request->query());
        //echo '<pre>', print_r($queries, 1), "</pre>"; exit;

        // Rows
        $rows = $this->BankService->getRows($queries);

        $json = [];

        foreach ($rows as $row) {
            $json[] = array(
                'id' => $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'short_name' => $row->short_name,
            );
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }



}