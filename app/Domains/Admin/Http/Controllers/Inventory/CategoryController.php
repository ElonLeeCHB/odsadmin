<?php

namespace App\Domains\Admin\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Repositories\Eloquent\Localization\LanguageRepository;
use App\Domains\Admin\Services\Common\TaxonomyService;
use App\Domains\Admin\Services\Inventory\CategoryService;

class CategoryController extends BackendController
{
    public function __construct(
        private Request $request
        , private LanguageRepository $LanguageRepository
        , private TaxonomyService $TaxonomyService
        , private CategoryService $CategoryService
    )
    {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/common/term','admin/inventory/category']);
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
            'text' => $this->lang->text_inventory,
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

        return view('admin.inventory.category', $data);
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

        // Prepare query_data for records
        $query_data = $this->getQueries($this->request->query());

        // Rows
        $categories = $this->CategoryService->getInventoryCategories($query_data);

        foreach ($categories as $row) {
            $row->edit_url = route('lang.admin.inventory.categories.form', array_merge([$row->id], $query_data));
        }

        $data['categories'] = $categories->withPath(route('lang.admin.inventory.categories.list'))->appends($query_data);

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
        $route = route('lang.admin.inventory.categories.list');

        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
        $data['sort_code'] = $route . "?sort=code&order=$order" .$url;
        $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_short_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_is_active'] = $route . "?sort=is_active&order=$order" .$url;
        $data['sort_taxonomy_name'] = $route . "?sort=taxonomy_name&order=$order" .$url;
        
        $data['list_url'] = route('lang.admin.inventory.categories.list');
        
        return view('admin.inventory.category_list', $data);
    }


    public function form($category_id = null)
    {
        $data['lang'] = $this->lang;

        // Languages dropdown menu
        $data['languages'] = $this->LanguageRepository->newModel()->active()->get();

        $this->lang->text_form = empty($category_id) ? $this->lang->trans('text_add') : $this->lang->trans('text_edit');

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
            'href' => route('lang.admin.common.terms.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        // Prepare link for save, back
        $queries = $this->getQueries($this->request->query());

        $data['save_url'] = route('lang.admin.inventory.categories.save');
        $data['back_url'] = route('lang.admin.inventory.categories.index', $queries);   
        $data['autocomplete_url'] = route('lang.admin.inventory.categories.autocomplete');
        $data['taxonomy_autocomplete_url'] = route('lang.admin.common.taxonomies.autocomplete');

        // Get Record
        $category = $this->CategoryService->findIdOrFailOrNew($category_id);

        if($category->taxonomy && $category->taxonomy->name){
            $category->taxonomy_name = $category->taxonomy->name;
        }else{
            $category->taxonomy_name = '';
        }

        if(!empty($category->parent_id)){
            $category->parent_name = $category->parent->name;
        }

        $data['category']  = $category;

        if(!empty($data['category']) && $category_id == $category->id){
            $data['category_id'] = $category_id;
        }else{
            $data['category_id'] = null;
        }

        // taxonomy_translations
        if($category->translations->isEmpty()){
            $term_translations = [];
        }else{
            foreach ($category->translations as $translation) {
                $term_translations[$translation->locale] = $translation->toArray();
            }
        }
        $data['translations'] = $term_translations;

        $filter_data = [
            'whereIn' => ['code' => ['product_inventory_category', 'product_accounting_category']],
            'pagination' => false,
        ];
        $data['taxonomies'] = $this->TaxonomyService->getRows($filter_data);

        $data['taxonomy_code'] = 'product_accounting_category,product_inventory_category';

        return view('admin.inventory.category_form', $data);
    }


    public function save()
    {
        $post_data = $this->request->post();

        $json = [];

        foreach ($post_data['translations'] as $locale => $translation) {
            if(empty($translation['name']) || mb_strlen($translation['name']) < 2){
                $json['error']['name-' . $locale] = '請輸入名稱 2-20 個字';
            }
        }        

        if(empty($post_data['taxonomy_code']) || mb_strlen($post_data['taxonomy_code']) < 2){
            $json['error']['taxonomy_name'] = '請輸入分類性質';
        }

        // 檢查欄位
        // do something        
        if(isset($json['error']) && !isset($json['error']['warning'])) {
            $json['error']['warning'] = $this->lang->error_warning;
        }

        if(!$json) {
            $result = $this->CategoryService->updateOrCreate($post_data);

            if(empty($result['error']) && !empty($result['category_id'])){
                $json = [
                    'category_id' => $result['category_id'],
                    'success' => $this->lang->text_success,
                    'redirectUrl' => route('lang.admin.inventory.categories.form', $result['category_id']),
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
            foreach ($selected as $category_id) {
                $result = $this->CategoryService->deleteCategory($category_id);

                if(!empty($result['error'])){
                    if(config('app.debug')){
                        $json['warning'] = $result['error'];
                    }else{
                        $json['warning'] = $this->lang->text_fail;
                    }

                    break;
                }
            }
        }

        if(empty($json['warning'] )){
            $json['success'] = $this->lang->text_success;
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }


    public function autocomplete()
    {
        $queries = $this->getQueries($this->request->query());

        $queries['whereIn'] = ['taxonomy_code' => ['product_inventory_category', 'product_accounting_category']];

        // Rows
        $rows = $this->CategoryService->getRows($queries);

        $json = [];

        foreach ($rows as $row) {
            $json[] = array(
                'label' => $row->name . '_' . $row->id,
                'value' => $row->id,
                'category_id' => $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'short_name' => $row->short_name,
                'parent_name' => $row->parent_name ?? '',
            );
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }



}