<?php

namespace App\Domains\Admin\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use Illuminate\Http\Request;
use App\Libraries\TranslationLibrary;
use App\Repositories\Eloquent\Localization\LanguageRepository;
use App\Domains\Admin\Services\Common\TermService;
use App\Domains\Admin\Traits\InitControllerTrait;

class TermController extends BackendController
{
    public function __construct(private Request $request, private TermService $TermService, private LanguageRepository $LanguageRepository)
    {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/common/term']);
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
            'href' => route('lang.admin.common.terms.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        $data['list'] = $this->getList();

        $data['list_url'] = route('lang.admin.common.terms.list'); //本參數在 getList() 也必須存在。
        $data['add_url'] = route('lang.admin.common.terms.form');
        $data['delete_url'] = route('lang.admin.common.terms.delete');
        
        return view('admin.common.term', $data);
    }

    public function list()
    {
        return $this->getList();
    }

    public function getList()
    {
        $data['lang'] = $this->lang;

        // Prepare link for action
        $queries = $this->getQueries($this->request->query());

        // Rows
        $terms = $this->TermService->getRows($queries);

        foreach ($terms as $row) {
            $row->edit_url = route('lang.admin.common.terms.form', array_merge([$row->id], $queries));
        }
        $data['terms'] = $terms->withPath(route('lang.admin.common.terms.list'))->appends($queries);

        // Prepare links for sort on list table's header
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
        $data['sort_short_name'] = $route . "?sort=short_name&order=$order" .$url;
        $data['sort_taxonomy_name'] = $route . "?sort=taxonomy_name&order=$order" .$url;
        
        $data['list_url'] = route('lang.admin.common.terms.list');
        
        return view('admin.common.term_list', $data);
    }

    public function form($term_id = null)
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

        $data['save_url'] = route('lang.admin.common.terms.save');
        $data['back_url'] = route('lang.admin.common.terms.index', $queries);   
        $data['autocomplete_url'] = route('lang.admin.common.terms.autocomplete');     

        // Get Record
        $term = $this->TermService->findIdOrFailOrNew($term_id);

        if(!empty($term) && !empty($term->taxonomy->name)){
            $term->taxonomy_name = $term->taxonomy->name;
        }else{
            $term->taxonomy_name = '';
        }


        $data['term']  = $term;

        if(!empty($data['term']) && $term_id == $term->id){
            $data['term_id'] = $term_id;
        }else{
            $data['term_id'] = null;
        }

        // taxonomy_translations
        if($term->translations->isEmpty()){
            $term_translations = [];
        }else{
            foreach ($term->translations as $translation) {
                $term_translations[$translation->locale] = $translation->toArray();
            }
        }
        $data['translations'] = $term_translations;

        return view('admin.common.term_form', $data);
    }

    public function save()
    {
        $data = $this->request->all();

        $json = [];

        foreach ($data['translations'] as $locale => $translation) {
            if(empty($translation['name']) || mb_strlen($translation['name']) < 2){
                $json['error']['name-' . $locale] = '請輸入名稱 2-20 個字';
            }
        }        

        if(empty($data['taxonomy_code']) || mb_strlen($data['taxonomy_code']) < 2){
            $json['error']['taxonomy_name'] = '請輸入分類性質';
        }

        // 檢查欄位
        // do something        
        if(isset($json['error']) && !isset($json['error']['warning'])) {
            $json['error']['warning'] = $this->lang->error_warning;
        }

        if(!$json) {
            $result = $this->TermService->updateOrCreate($data);

            if(empty($result['error']) && !empty($result['term_id'])){
                $json = [
                    'term_id' => $result['term_id'],
                    'success' => $this->lang->text_success,
                    'redirectUrl' => route('lang.admin.common.terms.form', $result['term_id']),
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
        $queries = $this->getQueries($this->request->query());

        $rows = $this->TermService->getRows($queries);

        $json = [];

        foreach ($rows as $row) {
            $json[] = array(
                'id' => $row->id,
                'code' => $row->code,
                'name' => $row->name,
            );
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }
}