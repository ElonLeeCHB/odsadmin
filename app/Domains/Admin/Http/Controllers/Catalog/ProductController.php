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
use App\Helpers\Classes\OrmHelper;
use App\Services\Sale\OrderProductOptionService;
use App\Repositories\Eloquent\Catalog\ProductOptionValueRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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
        $data['delete_url'] = route('lang.admin.catalog.products.destroy');

        // $data['order_printing_product_tags'] = $this->ProductService->getProductTags();
        $data['pringting_categories'] = $this->ProductService->getTermsByTaxonomyCode(taxonomy_code:'OrderPrintingProductCategory');

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
        $query_data = $this->url_data;

        // Rows, LengthAwarePaginator
        $query_data['select'] = ['id','code','main_category_id','sort_order','price','is_active','is_salable'];
        $query_data['equal_is_salable'] = 1;

        $products = $this->ProductService->getProductList($query_data);

        if(!empty($products)){
            $products->load('main_category');

            foreach ($products as $row) {
                $row->main_category_name = $row->main_category->name ?? '';
                $row->edit_url = route('lang.admin.catalog.products.form', array_merge([$row->id], $this->url_data));
            }
            
            $data['products'] = $products;
            $data['pagination'] = $products->withPath(route('lang.admin.catalog.products.list'))->appends($query_data)->links('admin.pagination.default');
        }else{
            $data['products'] = [];
            $data['pagination'] = '';
        }

        $query_data  = $this->url_data;

        // Prepare links for list table's header
        if(isset($query_data['order']) && $query_data['order'] == 'ASC'){
            $order = 'DESC';
        }else{
            $order = 'ASC';
        }

        $data['order'] = strtolower($order);

        if(isset($query_data['sort'])){
            $data['sort'] = strtolower($query_data['sort'] ?? '');
        }else{
            $data['sort'] = '';
        }

        $query_data = $this->unsetUrlQueryData(request()->query());

        $url = '';

        foreach($query_data as $key => $value){
            if(is_string($value)){
                $url .= "&$key=$value";
            }
        }

        // link of table header for sorting
        $route = route('lang.admin.catalog.products.list');

        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
        $data['sort_main_category_id'] = $route . "?sort=main_category_id&order=$order" .$url;
        $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_web_name'] = $route . "?sort=web_name&order=$order" .$url;
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
        $product = $this->ProductService->getProductById($product_id);

        $data['product']  = $product;



        $params = [
            'pagination' => false,
            'limit' => 0,
        ];
        $data['pringting_categories'] = $this->ProductService->getTermsByTaxonomyCode(taxonomy_code:'OrderPrintingProductCategory');

        // $data['exist_product_tag_ids'] = optional($product->productTags)->pluck('term_id')->toArray();
        $data['exist_order_printing_product_category_ids'] = optional($product->productTags)->pluck('term_id')->toArray();

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

        if($product->productOptions->isEmpty()){
            $data['product_options'] = [];
        }
        else{
            foreach ($product->productOptions as $productOption) {
                if(empty($productOption->translation->name)){
                    continue;
                }
                $product_option_value_data = [];
                if (!empty($productOption->productOptionValues)) {
                    $sorted = $productOption->productOptionValues->sortBy('sort_order');
                    foreach ($sorted as $product_option_value) {
                        $product_option_value_data[] = (object)[
                            'product_option_value_id' => $product_option_value->id,
                            'option_value_id'         => $product_option_value->option_value_id,
                            'name'                    => $product_option_value->translation->name ?? '',
                            'default_quantity'        => $product_option_value->default_quantity ?? 0,
                            'quantity'                => $product_option_value->quantity,
                            'is_default'              => $product_option_value->is_default,
                            'is_on_www'               => $product_option_value->is_on_www,
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
                    'id'                   => $productOption->id,
                    'product_option_id'    => $productOption->id,
                    'product_option_values' => $product_option_value_data,
                    'option_id'            => $productOption->option_id,
                    'name'                 => $productOption->translation->name,
                    'type'                 => $productOption->option->type,
                    'value'                => isset($productOption->value) ? $productOption->value : '',
                    'required'             => $productOption->required,
                    'sort_order'           => $productOption->sort_order,
                    'is_active'             => $productOption->is_active,
                    'is_fixed'             => $productOption->is_fixed,
                    'is_hidden'             => $productOption->is_hidden,
                ];
            }
        }

        // For modal window
        $data['option_values'] = [];

        foreach ($product->productOptions as $productOption) {
            if(empty($productOption->translation->name)){
                continue;
            }
            $option = $productOption->option;
            if ($option->type == 'options_with_qty' || $option->type == 'select' || $option->type == 'radio' || $option->type == 'checkbox' || $productOption->type == 'image') {
                if (!isset($data['option_values'][$option->id])) { //避免重複。
                    $data['option_values'][$option->id] = $option->optionValues->where('is_active',1)->sortBy('sort_order');
                }
            }
        }

        // ProductWwwCategory
        $data['ProductWwwCategories'] = $this->ProductService->getWwwCategories($product_id);
        $data['AllProductWwwCategories'] = $this->ProductService->getAllWwwCategories($product_id);

        // ProductPosCategory
        $data['ProductPosCategories'] = $this->ProductService->getPosCategories($product_id);

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
                        $json['error']['warning'] = $this->lang->error_product_option_value . 'product_option_value_id:'.$product_option_value_id.', order_id: ' . $order_product_options->order_id;
                    }
                }
            }
        }

        if (isset($json['error']) && !isset($json['error']['warning'])) {
            $json['error']['warning'] = $this->lang->error_warning;
        }

        // 以上驗證錯誤，400
        // opencart 的後台 common.js 必須一律收到 200 才能處理。這個暫時不改
        if(!empty($json)) {
            return response()->json($json, 200);
        }

        $result = $this->ProductService->save($data);

        // 執行錯誤，500
        if(!empty($result['error'])){
            return $this->getErrorResponse($result['error'], $this->lang->text_fail, 200);
        }

        // 如果是招牌潤餅便當，順便更新選項(例如配菜)
            if ($data['product_id'] == 1001){ // 招牌潤餅便當
                //POS分類包括 潤餅便當 1453，刈包便當 1454，不含素食 1464, 不含客製 1459
                $product_ids = DB::table('product_terms')
                    ->select('product_id')
                    ->where('taxonomy_id', 32)
                    ->whereIn('term_id', [1453, 1454, 1464, 1459])
                    ->groupBy('product_id')
                    ->havingRaw('
                        (SUM(CASE WHEN term_id = 1453 THEN 1 ELSE 0 END) > 0 OR
                        SUM(CASE WHEN term_id = 1454 THEN 1 ELSE 0 END) > 0)
                        AND SUM(CASE WHEN term_id = 1464 THEN 1 ELSE 0 END) = 0
                        AND SUM(CASE WHEN term_id = 1459 THEN 1 ELSE 0 END) = 0
                    ')
                    ->pluck('product_id')->toArray();

                $this->copyProductOption(1001, 1005, $product_ids);
            }
        //



        // 執行成功 200
        $json = [
            'success' => $this->lang->text_success,
            'product_id' => $result['product_id'],
            'redirectUrl' => route('lang.admin.catalog.products.form', $result['product_id']),
        ];

        return response()->json($json, 200);
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
            $result = $this->ProductService->destroy($selected);

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
                'label' => $row->name . '-' . $row->id, //待廢棄
                'value' => $row->id, //待廢棄
                '_label' => $row->name . '-' . $row->id,
                '_value' => $row->id,
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

    // 如果是招便潤餅便當 1001，順便更新其它便當的配菜
    public function copyProductOption($product_id, $option_id, $product_ids = [])
    {
        if (empty($product_ids)){
            $product_ids = $this->post_data['product_ids'] ?? [];
        }
        if (empty($product_ids)){
            return false;
        }

        $result = $this->ProductService->copyProductOption($product_id, $option_id, $product_ids);

        return response()->json(['success' => true, 'message' => "更新成功"], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
