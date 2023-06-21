<?php

namespace App\Domains\Admin\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\TranslationLibrary;
use App\Repositories\Eloquent\Localization\LanguageRepository;
use App\Domains\Admin\Services\Common\OptionService;
use App\Domains\Admin\Services\Catalog\ProductService;
use App\Domains\Admin\Services\Catalog\CategoryService;


class ProductController extends Controller
{
    private $lang;
    private $request;
    private $OptionService;
    private $ProductService;
    private $CategoryService;

    public function __construct(
        Request $request
        , LanguageRepository $languageRepository
        , OptionService $OptionService
        , ProductService $ProductService
        , CategoryService $CategoryService
    )
    {
        $this->request = $request;
        $this->languageRepository = $languageRepository;
        $this->OptionService = $OptionService;
        $this->ProductService = $ProductService;
        $this->CategoryService = $CategoryService;

        $groups = [
            'admin/common/common',
            'admin/inventory/product',
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
            'href' => route('lang.admin.inventory.products.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;
        $data['list'] = $this->getList();

        return view('admin.inventory.product', $data);
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
        $queries = [];

        if(!empty($this->request->query('page'))){
            $page = $queries['page'] = $this->request->input('page');
        }else{
            $page = $queries['page'] = 1;
        }

        if(!empty($this->request->query('sort'))){
            $sort = $queries['sort'] = $this->request->input('sort');
        }else{
            $sort = $queries['sort'] = 'id';
        }

        if(!empty($this->request->query('order'))){
            $order = $queries['order'] = $this->request->query('order');
        }else{
            $order = $queries['order'] = 'asc';
        }

        if(!empty($this->request->query('limit'))){
            $limit = $queries['limit'] = $this->request->query('limit');
        }

        foreach($this->request->all() as $key => $value){
            if(strpos($key, 'filter_') !== false){
                $queries[$key] = $value;
            }
        }        

        // Rows
        $products = $this->ProductService->getRows($queries);

        if(!empty($products)){
            foreach ($products as $row) {
                $row->edit_url = route('lang.admin.inventory.products.form', array_merge([$row->id], $queries));
            }
        }

        $data['products'] = $products->withPath(route('lang.admin.inventory.products.list'))->appends($queries);

        // Prepare links for list table's header
        if($order == 'ASC' ){
            $order = 'DESC';
        }else{
            $order = 'ASC';
        }
        
        $data['sort'] = strtolower($sort);
        $data['order'] = strtolower($order);

        unset($queries['sort']);
        unset($queries['order']);

        $url = '';

        foreach($queries as $key => $value){
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

        return view('admin.inventory.product_list', $data);
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
        $product = $this->ProductService->findIdOrNew($product_id);

        $product_stdobj = $this->ProductService->toStdObj($product);

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
            $filter_ids = $product->categories->pluck('id')->toArray();
            if(!empty($filter_ids)){
                $cat_filters = [
                    'filter_ids' => $filter_ids,
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


        return view('admin.inventory.product_form', $data);
    }


}