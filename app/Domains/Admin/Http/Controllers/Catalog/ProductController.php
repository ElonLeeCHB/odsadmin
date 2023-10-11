<?php

namespace App\Domains\Admin\Http\Controllers\Catalog;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Repositories\Eloquent\Localization\LanguageRepository;
use App\Domains\Admin\Services\Catalog\ProductService;
use App\Domains\Admin\Services\Catalog\OptionService;
use App\Domains\Admin\Services\Catalog\CategoryService;
use App\Services\Sale\OrderProductOptionService;
use App\Repositories\Eloquent\Catalog\ProductOptionValueRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductController extends BackendController
{
    public function __construct(
        private Request $request
        , private LanguageRepository $languageRepository
        , private ProductService $ProductService
        , private CategoryService $CategoryService
        , private OptionService $OptionService
        , private OrderProductOptionService $OrderProductOptionService
        , private ProductOptionValueRepository $ProductOptionValueRepository
    )
    {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/catalog/product']);
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
            'href' => route('lang.admin.catalog.products.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;


        // categories
        $data['categories'] = $this->CategoryService->getCategories();


        $data['list'] = $this->getList();

        $data['list_url']   = route('lang.admin.catalog.products.list');
        $data['add_url']    = route('lang.admin.catalog.products.form');
        $data['delete_url'] = route('lang.admin.catalog.products.delete');

        return view('admin.catalog.product', $data);
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

        // Rows, LengthAwarePaginator
        $products = $this->ProductService->getSalableProducts($query_data);

        if(!empty($products)){
            $products->load('main_category');

            foreach ($products as $row) {
                $row->main_category_name = $row->main_category->name ?? '';
                $row->edit_url = route('lang.admin.catalog.products.form', array_merge([$row->id], $query_data));
            }
        }

        $data['products'] = $products->withPath(route('lang.admin.catalog.products.list'))->appends($query_data);


        // Prepare links for list table's header
        if($query_data['order'] == 'ASC'){
            $order = 'DESC';
        }else{
            $order = 'ASC';
        }
        
        $data['sort'] = strtolower($query_data['sort']);
        $data['order'] = strtolower($order);

        $query_data = $this->unsetUrlQueryData($query_data);

        $url = '';

        foreach($query_data as $key => $value){
            $url .= "&$key=$value";
        }
        
        // link of table header for sorting        
        $route = route('lang.admin.catalog.products.list');
        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
        $data['sort_main_category_id'] = $route . "?sort=main_category_id&order=$order" .$url;
        $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_model'] = $route . "?sort=model&order=$order" .$url;
        $data['sort_price'] = $route . "?sort=price&order=$order" .$url;
        $data['sort_quantity'] = $route . "?sort=quantity&order=$order" .$url;
        $data['sort_date_added'] = $route . "?sort=created_at&order=$order" .$url;
        
        return view('admin.catalog.product_list', $data);
    }


    public function form($product_id = null)
    {
        $data['lang'] = $this->lang;

        // Languages
        $data['languages'] = $this->languageRepository->newModel()->active()->get();
  
        $this->lang->text_form = empty($product_id) ? $this->lang->trans('text_add') : $this->lang->trans('text_edit');

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

        $data['save'] = route('lang.admin.catalog.products.save');
        $data['back'] = route('lang.admin.catalog.products.index', $queries);

        // Get Record
        $product = $this->ProductService->findIdOrFailOrNew($product_id);

        $data['product']  = $product;

        $data['bom_products'] = $product->bom_products;

        if(!empty($data['product']) && $product_id == $product->id){
            $data['product_id'] = $product_id;
        }else{
            $data['product_id'] = null;
        }

        // translations
        if($product->translations->isEmpty()){
            $translations = [];
        }else{
            foreach ($product->translations as $translation) {
                $translations[$translation->locale] = $translation->toArray();
                // locale is like zh-something, the hyphen can't be as the key. 
                // This won't work: $translations->zh-Hant->name
            }
        }
        $data['translations'] = $translations;

        // product_categories
        if ($product_id) {
            $ids = $product->categories->pluck('id')->toArray();
            if(!empty($ids)){
                $cat_filters = [
                    'whereIn' => ['id' => $ids],
                    'pagination' => false
                ];
                $product_categories = $this->CategoryService->getRows($cat_filters);
            
                foreach ($product_categories as $category) {
                    $data['product_categories'][] = (object)[
                        'category_id' => $category->id,
                        'name'        => $category->name,
                    ];
                }
            }
        }
        
        if(empty($data['product_categories'])) {
            $data['product_categories'] = [];
        }

        // product_options
        $product->load('product_options.translation');
        $product->product_options->load('product_option_values.translation');
        
        //為避免 lazy load，所以先 eager load
        $product->product_options->load('option.translation'); 
        $product->product_options->load('option.option_values.translation');

        if($product->product_options->isEmpty()){
            $data['product_options'] = [];
        }
        else{
            foreach ($product->product_options as $product_option) {
                $product_option_value_data = [];    
                if (!empty($product_option->product_option_values)) {
                    $sorted = $product_option->product_option_values->sortBy('sort_order');
                    foreach ($sorted as $product_option_value) {
                        $product_option_value_data[] = (object)[
                            'product_option_value_id' => $product_option_value->id,
                            'option_value_id'         => $product_option_value->option_value_id,
                            'name'                    => $product_option_value->translation->name ?? '',
                            'quantity'                => $product_option_value->quantity,
                            'is_default'              => $product_option_value->is_default,
                            'is_active'               => $product_option_value->is_active,
                            'subtract'                => $product_option_value->subtract,
                            'price'                   => round($product_option_value->price),
                            'price_prefix'            => $product_option_value->price_prefix,
                            'points'                  => round($product_option_value->points),
                            'points_prefix'           => $product_option_value->points_prefix,
                            'weight'                  => round($product_option_value->weight),
                            'weight_prefix'           => $product_option_value->weight_prefix,
                            'sort_order'              => $product_option_value->sort_order,
                        ];
                    }
                }
                $data['product_options'][] = (object)[
                    'product_option_id'    => $product_option->id,
                    'product_option_values' => $product_option_value_data,
                    'option_id'            => $product_option->option_id,
                    'name'                 => $product_option->translation->name,
                    'type'                 => $product_option->option->type,
                    'value'                => isset($product_option->value) ? $product_option->value : '',
                    'required'             => $product_option->required,
                    'sort_order'           => $product_option->sort_order,
                    'is_active'             => $product_option->is_active,
                    'is_fixed'             => $product_option->is_fixed,
                    'is_hidden'             => $product_option->is_hidden,
                ];
            }
        }

        // For modal window
        $data['option_values'] = [];

        foreach ($product->product_options as $product_option) {
            $option = $product_option->option;
            if ($option->type == 'options_with_qty' || $option->type == 'select' || $option->type == 'radio' || $option->type == 'checkbox' || $product_option->type == 'image') {
                if (!isset($data['option_values'][$option->id])) { //避免重複。但是實際上是否有可能一個商品拉兩個顏色組？
                    $data['option_values'][$option->id] = $option->option_values->where('is_active',1);
                }
            }
        }

        return view('admin.catalog.product_form', $data);
    }
    

    public function save()
    {
        $data = $this->request->all();
        
        $json = [];
        
        // Check
        foreach ($data['translations'] as $locale => $translation) {
            if(empty($translation['name']) || mb_strlen($translation['name']) < 2){
                $json['error']['name-' . $locale] = $this->lang->error_name;
            }
        }   

        // 暫不使用
        // if(empty($data['model']) || mb_strlen($data['model']) < 2){
        //     $json['error']['model'] = $this->lang->error_model;
        // }
        
        // $validator = $this->ProductService->validator($this->request->post());

        // if($validator->fails()){
        //     $messages = $validator->errors()->toArray();
        //     foreach ($messages as $key => $rows) {
        //         $json['error'][$key] = $rows[0];
        //     }
        // }

        // Check product_options
        if (isset($data['product_options'])) {

            //product_options in form
            $product_option_value_ids_in_form = [];
            foreach($data['product_options'] as $product_option){
                if(!empty($product_option['product_option_values'])){
                    foreach ($product_option['product_option_values'] as $product_option_value) {
                        $product_option_value_ids_in_form[] = $product_option_value['product_option_value_id'];
                    }
                }
            }

            if(!empty($product_option_value_ids_in_form)){
                $product_option_value_ids_in_form = array_unique($product_option_value_ids_in_form);
                sort($product_option_value_ids_in_form);

                //product_options in database
                $query_data = [
                    'equal_product_id' => $data['product_id'],
                    'pluck' => 'id',
                    'limit' => 0,
                    'pagination' => false,
                    'sort' => 'id',
                    'order' => 'ASC',
                ];
                $existed_product_option_values = $this->ProductOptionValueRepository->getRows($query_data)->toArray();
                $existed_product_option_values = array_unique($existed_product_option_values);

                // Delete check
                $delete_product_option_value_ids = array_diff($existed_product_option_values, $product_option_value_ids_in_form);

                foreach ($delete_product_option_value_ids as $product_option_value_id) {
                    $filter_data = [
                        'equal_product_option_value_id' => $product_option_value_id,
                        'pagination' => false,
                        'select' => ['id','order_id'],
                    ];
                    $order_product_options = $this->OrderProductOptionService->getRow($filter_data);

                    if(!empty($order_product_options)){
                        $json['error']['warning'] = $this->lang->error_product_option_value . ' order_id: ' . $order_product_options->order_id;
                    }
                }
            }
        }

        if (isset($json['error']) && !isset($json['error']['warning'])) {
            $json['error']['warning'] = $this->lang->error_warning;
        }

        if(!$json) {
            $result = $this->ProductService->updateOrCreateProduct($data);

            if(empty($result['error'])){
                $json = [
                    'success' => $this->lang->text_success,
                    'product_id' => $result['product_id'],
                    'redirectUrl' => route('lang.admin.catalog.products.form', $result['product_id']),
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
        $post_data = $this->request->post();

        $json = [];

        if (isset($post_data['selected'])) {
            $selected = $post_data['selected'];
        } else {
            $selected = [];
        }

        // if (!$this->user->hasPermission('modify', 'catalog/category')) {
        //     $json['error'] = $this->language->get('error_permission');
        // }

        if (!$json) {
            foreach ($selected as $product_id) {
                $result = $this->ProductService->deleteProduct($product_id);

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
        $json = [];

        if(isset($this->request->filter_name)){
            $filter_name = $this->request->filter_name;
        }else{
            $filter_name = '';
        }

        if(isset($this->request->filter_model)){
            $filter_model = $this->request->filter_model;
        }else{
            $filter_model = '';
        }

        $filter_data = array(
            'filter_model' => $filter_model,
            'filter_name' => $filter_name,
            'filter_is_salable' => $this->request->filter_is_salable,
            'limit'   => 10,
            'pagination'   => false,
        );

        $rows = $this->ProductService->getProducts($filter_data);

        foreach ($rows as $row) {
            $json[] = array(
                'label' => $row->name . '-' . $row->id,
                'value' => $row->id,
                'product_id' => $row->id,
                'name' => $row->name,
                'specification' => $row->specification,
                'model' => $row->model,
                'stock_unit_code' => $row->stock_unit_code,
                'stock_unit_name' => $row->stock_unit_name,
            );
        }

        return response(json_encode($json))->header('Content-Type','application/json');
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