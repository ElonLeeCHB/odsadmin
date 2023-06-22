<?php

namespace App\Domains\Admin\Http\Controllers\Common;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\TranslationLibrary;
use App\Repositories\Eloquent\Localization\LanguageRepository;
use App\Domains\Admin\Services\Common\FinancialInstitutionService;
use App\Traits\InitController;

class FinancialInstitutionController extends Controller
{
    use InitController;

    private $request;
    private $lang;
    private $LanguageRepository;
    private $FinancialInstitutionService;

    public function __construct(
        Request $request
        , LanguageRepository $LanguageRepository
        , FinancialInstitutionService $FinancialInstitutionService
    )
    {
        $this->request = $request;
        $this->lang = (new TranslationLibrary())->getTranslations(['admin/common/common','admin/common/financial_institution']);
        $this->LanguageRepository = $LanguageRepository;
        $this->FinancialInstitutionService = $FinancialInstitutionService;
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

        return view('admin.common.financial_institution', $data);
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
    public function getList()
    {
        $data['lang'] = $this->lang;

        // Prepare link for action
        $queries = $this->getQueries($this->request->query());

        // Rows
        $terms = $this->FinancialInstitutionService->getRows($queries);

        foreach ($terms as $row) {
            $row->edit_url = route('lang.admin.common.financial_institutions.form', array_merge([$row->id], $queries));
        }
        $data['terms'] = $terms->withPath(route('lang.admin.common.financial_institutions.list'))->appends($queries);

        // Prepare links for list table's header
        if($queries['order'] == 'ASC'){
            $order = 'DESC';
        }else{
            $order = 'ASC';
        }
        
        $data['sort'] = strtolower($queries['sort']);
        $data['order'] = strtolower($order);

        unset($queries['sort']);
        unset($queries['order']);
        unset($queries['with']);

        $url = '';

        foreach($queries as $key => $value){
            $url .= "&$key=$value";
        }
        
        //link of table header for sorting
        $route = route('lang.admin.common.terms.list');

        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
        $data['sort_code'] = $route . "?sort=code&order=$order" .$url;
        $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_short_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_is_active'] = $route . "?sort=is_active&order=$order" .$url;
        $data['sort_taxonomy_name'] = $route . "?sort=taxonomy_name&order=$order" .$url;
        
        $data['list_url'] = route('lang.admin.common.financial_institutions.list');
        
        return view('admin.common.financial_institution_list', $data);
    }


    public function form($institution_id = null)
    {
        $data['lang'] = $this->lang;

        // Languages dropdown menu
        $data['languages'] = $this->LanguageRepository->newModel()->active()->get();

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

        return view('admin.common.financial_institution_form', $data);
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