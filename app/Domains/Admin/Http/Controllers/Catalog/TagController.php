<?php

namespace App\Domains\Admin\Http\Controllers\Catalog;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\Eloquent\Localization\LanguageRepository;
use App\Libraries\TranslationLibrary;
use App\Domains\Admin\Services\Common\TermService;
use App\Traits\InitController;

class TagController extends Controller
{
    use InitController;

    private $request;
    private $lang;
    private $LanguageRepository;
    private $TermService;

    public function __construct(Request $request, LanguageRepository $LanguageRepository, TermService $TermService)
    {
        $this->request = $request;
        $this->LanguageRepository = $LanguageRepository;
        $this->TermService = $TermService;
        $this->lang = (new TranslationLibrary())->getTranslations(['admin/common/common','admin/common/term','admin/catalog/tag']);
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
            'href' => route('lang.admin.catalog.tags.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        $data['list'] = $this->getList();

        $data['list_url'] =route('lang.admin.catalog.tags.list'); //本參數在 getList() 也必須存在。
        $data['add_url'] = route('lang.admin.catalog.tags.form');
        $data['delete_url'] = route('lang.admin.catalog.tags.delete');

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

        $queries['whereIn'] = ['taxonomy_code' => ['product_tags']];

        // rows
        $terms = $this->TermService->getRows($queries);
        if(!empty($terms)){
            foreach ($terms as $row) {
                $row->edit_url = route('lang.admin.catalog.categories.form', array_merge([$row->id], $queries));
            }
        }

        $data['terms'] = $terms->withPath(route('lang.admin.catalog.categories.list'))->appends($queries);

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
        unset($queries['whereIn']);

        $url = '';

        foreach($queries as $key => $value){
            $url .= "&$key=$value";
        }
        
        // link of table header for sorting        
        $route = route('lang.admin.catalog.products.list');

        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
        $data['sort_code'] = $route . "?sort=code&order=$order" .$url;
        $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_short_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_taxonomy_name'] = $route . "?sort=taxonomy_name&order=$order" .$url;
        $data['sort_is_active'] = $route . "?sort=is_active&order=$order" .$url;
        $data['sort_date_added'] = $route . "?sort=created_at&order=$order" .$url;

        $data['list_url'] =route('lang.admin.catalog.categories.list');

        return view('admin.common.term_list', $data);
    }


    public function form($term_id = null)
    {
        $data['lang'] = $this->lang;
  
        $this->lang->text_form = empty($product_id) ? $this->lang->trans('text_add') : $this->lang->trans('text_edit');

        // Languages
        $data['languages'] = $this->LanguageRepository->newModel()->active()->get();

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
            'href' => route('lang.admin.catalog.products.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        // Prepare link for save, back
        $queries = $this->getQueries($this->request->query());

        $data['save_url'] = route('lang.admin.catalog.categories.save');
        $data['back_url'] = route('lang.admin.catalog.categories.index', $queries);   
        $data['autocomplete_url'] = route('lang.admin.catalog.categories.autocomplete');

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

        // product_translations
        if($term->translations->isEmpty()){
            $translations = [];
        }else{
            foreach ($term->translations as $translation) {
                $translations[$translation->locale] = $translation->toArray();
            }
        }
        $data['translations'] = $translations;

        $data['taxonomy_code'] = 'product_tags';
        
        return view('admin.common.term_form', $data);
    }


    public function save()
    {
        $data = $this->request->all();

        $json = [];

        if (isset($json['error']) && !isset($json['error']['warning'])) {
            $json['error']['warning'] = $this->lang->error_warning;
        }

        if(!$json) {
            $result = $this->TermService->updateOrCreate($data);
            if(empty($result['error'])){
                $json = [
                    'term_id' => $result['term_id'],
                    'success' => $this->lang->text_success,
                    'redirectUrl' => route('lang.admin.catalog.categories.form', $result['term_id']),
                ];
            }else if(auth()->user()->username == 'admin'){
                $json['warning'] = $result['error'];
            }else{
                $json['warning'] = $this->lang->text_fail;
            }
        }
        
        return response(json_encode($json))->header('Content-Type','application/json');

    }

    public function autocomplete()
    {
        $queries = $this->getQueries($this->request->query());

        $queries['pagination'] = false;
        $queries['whereIn'] = ['taxonomy_code' => ['product_tags']];

        // Rows
        $rows = $this->TermService->getRows($queries);

        $json = [];

        foreach ($rows as $row) {
            $json[] = array(
                'term_id' => $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'short_name' => $row->short_name,
                'parent_name' => $row->parent_name ?? '',
            );
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }

    public function delete()
    {

    }
}