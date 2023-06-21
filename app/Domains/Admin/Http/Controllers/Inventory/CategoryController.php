<?php

namespace App\Domains\Admin\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\TranslationLibrary;
use App\Repositories\Eloquent\Localization\LanguageRepository;
use App\Domains\Admin\Services\Common\TaxonomyService;
use App\Domains\Admin\Services\Common\TermService;
use App\Traits\InitController;

class CategoryController extends Controller
{
    use InitController;

    private $request;
    private $lang;
    private $LanguageRepository;
    private $TaxonomyService;
    private $TermService;

    public function __construct(
        Request $request
        , LanguageRepository $LanguageRepository
        , TaxonomyService $TaxonomyService
        , TermService $TermService
    )
    {
        $this->request = $request;
        $this->LanguageRepository = $LanguageRepository;
        $this->TaxonomyService = $TaxonomyService;
        $this->TermService = $TermService;

        $groups = [
            'admin/common/common',
            'admin/inventory/category',
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

        $data['list_url'] = route('lang.admin.inventory.categories.list');
        $data['add_url'] = route('lang.admin.inventory.categories.form');
        $data['delete_url'] = route('lang.admin.inventory.categories.delete');

        return view('admin.common.term', $data);
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
        $terms = $this->TermService->getInventoryCategories($queries);

        foreach ($terms as $row) {
            $row->edit_url = route('lang.admin.inventory.categories.form', array_merge([$row->id], $queries));
            //$row->taxonomy
        }
        $data['terms'] = $terms->withPath(route('lang.admin.inventory.categories.list'))->appends($queries);

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
        unset($queries['whereEquals']);

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
        
        $data['list_url'] = route('lang.admin.inventory.categories.list');
        
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

        $data['save_url'] = route('lang.admin.inventory.categories.save');
        $data['back_url'] = route('lang.admin.inventory.categories.index', $queries);   
        $data['autocomplete_url'] = route('lang.admin.inventory.categories.autocomplete');

        // Get Record
        $term = $this->TermService->findIdOrFailOrNew($term_id);

        if($term->taxonomy && $term->taxonomy->name){
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

        $filter_data = [
            'whereIn' => ['code' => ['inventory_category', 'accounting_category']],
            'pagination' => 0,
        ];
        $data['taxonomies'] = $this->TaxonomyService->getRows($filter_data);

        $data['taxonomy_code'] = 'accounting_category,inventory_category';

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
                    'redirectUrl' => route('lang.admin.inventory.categories.form', $result['term_id']),
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

        $queries['whereIn'] = ['taxonomy_code' => ['inventory_category', 'accounting_category']];

        // Rows
        $rows = $this->TermService->getRows($queries);

        $json = [];

        foreach ($rows as $row) {
            $json[] = array(
                'taxonomy_id' => $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'short_name' => $row->short_name,
                'parent_name' => $row->parent_name ?? '',
            );
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }



}