<?php

namespace App\Domains\Admin\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Libraries\TranslationLibrary;
use App\Repositories\Eloquent\Localization\LanguageRepository;
use App\Repositories\Eloquent\Localization\UnitRepository;
use App\Domains\Admin\Services\Common\OptionService;
use App\Services\Inventory\ProductService;
use App\Domains\Admin\Services\Catalog\CategoryService;
use App\Repositories\Eloquent\Catalog\ProductOptionValueRepository;

class ProductController extends BackendController
{
    public function __construct(
        protected Request $request
        , private LanguageRepository $languageRepository
        , private OptionService $OptionService
        , private ProductService $ProductService
        , private CategoryService $CategoryService
        , private ProductOptionValueRepository $ProductOptionValueRepository
        , private UnitRepository $UnitRepository
    )
    {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/inventory/product']);
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
            'href' => route('lang.admin.inventory.products.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;


        // categories
        $data['categories'] = $this->CategoryService->getCategories();

        
        $data['list'] = $this->getList();

        $data['list_url']   = route('lang.admin.inventory.products.list');
        $data['add_url']    = route('lang.admin.inventory.products.form');
        $data['delete_url'] = route('lang.admin.inventory.products.delete');

        return view('admin.catalog.product', $data);
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
        $products = $this->ProductService->getProducts($query_data);
        //echo '<pre>', print_r($products, 1), "</pre>"; exit;
        if(!empty($products)){
            foreach ($products as $row) {
                $row->edit_url = route('lang.admin.inventory.products.form', array_merge([$row->id], $query_data));
            }
        }

        $data['products'] = $products->withPath(route('lang.admin.inventory.products.list'))->appends($query_data);


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
        $route = route('lang.admin.inventory.products.list');

        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
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
            'href' => route('lang.admin.inventory.products.index'),
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

        $data['save'] = route('lang.admin.inventory.products.save');
        $data['back'] = route('lang.admin.inventory.products.index', $queries);

        // Get Record
        $product = $this->ProductService->findIdOrFailOrNew($product_id);
        $product->supplier_name = $product->supplier->name ?? '';
        $product->supplier_product_name = $product->supplier_product->name ?? '';

        // $product->load('supplier');
        // $clone_product = $product->toArray();
        // $clone_product['supplier_name'] = $clone_product['supplier']['name'];
        // unset($clone_product['supplier']);
        // unset($clone_product['translation']);
        // $clone_product = (object) $clone_product;
        // $data['product']  = $clone_product;
        $data['product']  = $product;

        if(!empty($data['product']) && $product_id == $product->id){
            $data['product_id'] = $product_id;
        }else{
            $data['product_id'] = null;
        }

        // product_translations
        if($product->translations->isEmpty()){
            $product_translations = [];
        }else{
            foreach ($product->translations as $translation) {
                $product_translations[$translation->locale] = $translation->toArray();
                // locale is like zh-something, the hyphen can't be as the key. 
                // This won't work: $product_translations->zh-Hant->name
            }
        }
        $data['product_translations'] = $product_translations;
        

        // product_categories
		if ($product_id) {
            $ids = $product->categories->pluck('id')->toArray();
            if(!empty($ids)){
                $cat_filters = [
                    'whereIn' => ['id', $$ids],
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

        $data['bom_products'] = [];

        $data['product_options'] = [];
        
        $data['source_codes'] = $this->ProductService->getProductSourceCodes();

        // supplier
        $data['supplier_autocomplete_url'] = route('lang.admin.counterparty.suppliers.autocomplete');

        // supplier_product
        $data['supplier_product_autocomplete_url'] = route('lang.admin.inventory.products.autocomplete');
        
        // units
        $filter_data = [
            'filter_keyword' => $this->request->filter_keyword,
            'pagination' => false,
            'limit' => 0,
        ];
        $data['units'] = $this->UnitRepository->getActiveUnits($filter_data);

        return view('admin.inventory.product_form', $data);
    }


    public function save()
    {
        $data = $this->request->all();
        
        $json = [];
        
        // Check
        foreach ($data['translations'] ?? [] as $locale => $translation) {
            if(empty($translation['name']) || mb_strlen($translation['name']) < 2){
                $json['error']['name-' . $locale] = $this->lang->error_name;
            }
        }

        if (isset($json['error']) && !isset($json['error']['warning'])) {
            $json['error']['warning'] = $this->lang->error_warning;
        }

        if(!$json) {
            $result = $this->ProductService->updateOrCreate($data);

            if(empty($result['error'])){
                $json = [
                    'success' => $this->lang->text_success,
                    'product_id' => $result['product_id'],
                    'redirectUrl' => route('lang.admin.inventory.products.form', $result['product_id']),
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
                'product_name' => $row->name,
                'model' => $row->model,
            );
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }

}