<?php

namespace App\Domains\Admin\Http\Controllers\Counterparty;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Domains\Admin\Services\Counterparty\FinancialInstitutionService;

class FinancialInstitutionController extends BackendController
{
    public function __construct(
        private Request $request
        , private FinancialInstitutionService $FinancialInstitutionService
    )
    {
        parent::__construct();
        
        $this->getLang(['admin/common/common','admin/common/financial_institution']);
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
            'href' => route('lang.admin.common.financial_institutions.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        $data['list'] = $this->getList();

        $data['list_url'] = route('lang.admin.common.financial_institutions.list');
        $data['add_url'] = route('lang.admin.common.financial_institutions.form');
        $data['delete_url'] = route('lang.admin.common.financial_institutions.delete');

        return view('admin.counterparty.financial_institution', $data);
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
        $query_data = $this->getQueries($this->request->query());

        // Rows
        $institutions = $this->FinancialInstitutionService->getRows($query_data);

        foreach ($institutions as $row) {
            $row->edit_url = route('lang.admin.common.financial_institutions.form', array_merge([$row->id], $query_data));
        }
        $data['institutions'] = $institutions->withPath(route('lang.admin.common.financial_institutions.list'))->appends($query_data);

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
        $route = route('lang.admin.common.financial_institutions.list');

        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
        $data['sort_code'] = $route . "?sort=code&order=$order" .$url;
        $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_short_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_is_active'] = $route . "?sort=is_active&order=$order" .$url;
        $data['sort_taxonomy_name'] = $route . "?sort=taxonomy_name&order=$order" .$url;
        
        $data['list_url'] = route('lang.admin.common.financial_institutions.list');
        
        return view('admin.counterparty.financial_institution_list', $data);
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
        $queries = $this->getQueries($this->request->query());

        $data['save_url'] = route('lang.admin.common.financial_institutions.save');
        $data['back_url'] = route('lang.admin.common.financial_institutions.index', $queries);   
        $data['autocomplete_url'] = route('lang.admin.common.financial_institutions.autocomplete');

        // Get Record
        $institution = $this->FinancialInstitutionService->findIdOrFailOrNew($institution_id);

        $data['institution']  = $institution;

        if(!empty($data['institution']) && $institution_id == $institution->id){
            $data['institution_id'] = $institution_id;
        }else{
            $data['institution_id'] = null;
        }

        return view('admin.counterparty.financial_institution_form', $data);
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
            $result = $this->FinancialInstitutionService->updateOrCreate($data);

            if(empty($result['error'])){
                if(isset($result['row_id'])){
                    $json = [
                        'institution_id' => $result['row_id'],
                        'success' => $this->lang->text_success,
                        'redirectUrl' => route('lang.admin.common.financial_institutions.form', $result['row_id']),
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

    public function delete()
    {
        $this->initController();

        $post_data = $this->request->post();

		$json = [];

        // Permission
        if($this->acting_username !== 'admin'){
            $json['error'] = $this->lang->error_permission;
        }

        // Selected
		if (isset($post_data['selected'])) {
			$selected = $post_data['selected'];
		} else {
			$selected = [];
		}

		if (!$json) {

			foreach ($selected as $category_id) {
				$result = $this->FinancialInstitutionService->deleteFinancialInstitution($category_id);

                if(!empty($result['error'])){
                    if(config('app.debug')){
                        $json['error'] = $result['error'];
                    }else{
                        $json['error'] = $this->lang->text_fail;
                    }

                    break;
                }
			}
		}
        
        if(empty($json['error'] )){
            $json['success'] = $this->lang->text_success;
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }

    public function autocomplete()
    {
        $queries = $this->getQueries($this->request->query());

        // Rows
        $rows = $this->FinancialInstitutionService->getRows($queries);

        $json = [];

        foreach ($rows as $row) {
            $json[] = array(
                'institution_id' => $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'short_name' => $row->short_name,
            );
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }



}