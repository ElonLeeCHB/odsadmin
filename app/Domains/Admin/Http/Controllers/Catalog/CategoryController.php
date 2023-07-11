<?php

namespace App\Domains\Admin\Http\Controllers\Catalog;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Repositories\Eloquent\Localization\LanguageRepository;
use App\Libraries\TranslationLibrary;
use App\Domains\Admin\Services\Common\TermService;
use App\Traits\InitController;

class CategoryController extends Controller
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
        $this->lang = (new TranslationLibrary())->getTranslations(['admin/common/common','admin/common/term','admin/catalog/category']);
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
            'href' => route('lang.admin.catalog.categories.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        $data['list'] = $this->getList();

        $data['list_url'] =route('lang.admin.catalog.categories.list');
        $data['add_url'] = route('lang.admin.catalog.categories.form');
        $data['delete_url'] = route('lang.admin.catalog.categories.delete');

        return view('admin.catalog.category', $data);
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

        $queries['whereIn'] = ['taxonomy_code' => ['product_category']];

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

        return view('admin.catalog.category_list', $data);
    }


    public function form($category_id = null)
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
        $category = $this->TermService->findIdOrFailOrNew($category_id,['equal_taxonomy_code' => 'product_category']);

        $data['category']  = $category;
        
        if(!empty($data['category']) && $category_id == $category->id){
            $data['category_id'] = $category_id;
        }else{
            $data['category_id'] = null;
        }

        // translations
        if($category->translations->isEmpty()){
            $translations = [];
        }else{
            foreach ($category->translations as $translation) {
                $translations[$translation->locale] = $translation->toArray();
            }
        }
        $data['translations'] = $translations;
        
        $data['taxonomy_code'] = 'product_category';
        
        return view('admin.catalog.category_form', $data);
    }


    public function save()
    {
        $data = $this->request->all();

        $json = [];

        if (isset($json['error']) && !isset($json['error']['warning'])) {
            $json['error']['warning'] = $this->lang->error_warning;
        }

        if(!$json) {

            $data['taxonomy_code'] = 'product_category';

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
        $query_data = $this->request->query();

        $queries = $this->getQueries($this->request->query());

        $queries['pagination'] = false;

        // Rows
        $rows = $this->TermService->getRows($queries);

        $json = [];

        foreach ($rows as $row) {
            if(!empty($query_data['exclude_id']) && $query_data['exclude_id'] == $row->id){
                continue;
            }

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

    public function validator(array $data)
    {
        return Validator::make($data, [
                'organization_id' =>'nullable|integer',
                'name' =>'nullable|max:10',
                'short_name' =>'nullable|max:10',
            ],[
                'organization_id.integer' => $this->lang->error_organization_id,
                'name.*' => $this->lang->error_name,
                'short_name.*' => $this->lang->error_short_name,
        ]);
    }
}