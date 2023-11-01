<?php

namespace App\Domains\Admin\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Domains\Admin\Services\Inventory\ProductService;
use App\Repositories\Eloquent\Localization\LanguageRepository;
use App\Repositories\Eloquent\Common\UnitRepository;
//use App\Repositories\Eloquent\Inventory\ProductUnitRepository;
use App\Domains\Admin\Services\Catalog\CategoryService;
use App\Repositories\Eloquent\Catalog\ProductOptionValueRepository;
use App\Repositories\Eloquent\Common\TermRepository;
//use App\Models\Common\Term;
use App\Helpers\Classes\DataHelper;
use App\Helpers\Classes\UrlHelper;

class ProductController extends BackendController
{
    public function __construct(
        protected Request $request
        , private LanguageRepository $languageRepository
        , private ProductService $ProductService
        , private CategoryService $CategoryService
        , private ProductOptionValueRepository $ProductOptionValueRepository
        , private UnitRepository $UnitRepository
        , private TermRepository $TermRepository
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
        //$data['categories'] = $this->CategoryService->getCategories();

        // 來源碼
        $data['source_codes'] = $this->ProductService->getKeyedSourceCodes();

        // 會計分類
        $data['accounting_categories'] = $this->ProductService->getKeyedAccountingCategory();

        $data['list'] = $this->getList();

        $data['list_url']   = route('lang.admin.inventory.products.list');
        $data['add_url']    = route('lang.admin.inventory.products.form');
        $data['delete_url'] = route('lang.admin.inventory.products.delete');
        $data['export_inventory_product_list'] = route('lang.admin.inventory.products.export_inventory_product_list');

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

        $url_queries = $this->request->query();


        // Prepare query_data for records
        $filter_data = UrlHelper::getUrlQueriesForFilter();

        $extra_columns = $filter_data['extra_columns'] ?? [];
        $filter_data['extra_columns'] = DataHelper::addToArray($extra_columns, 'accounting_category_name');
        
        $with = $filter_data['with'] ?? [];
       $filter_data['with'] = DataHelper::addToArray($with, 'source_type');
        
        // Rows
        $products = $this->ProductService->getProducts($filter_data);

        if(!empty($products)){
            foreach ($products as $row) {
                $row->edit_url = route('lang.admin.inventory.products.form', array_merge([$row->id], $url_queries));
                $row->supplier_short_name = $row->supplier->short_name ?? '';
            }
        }

        $products = $products->withPath(route('lang.admin.inventory.products.list'))->appends($url_queries);
        
        $data['products'] = $products;
        $data['pagination'] = $products->links('admin.pagination.default');

        // Prepare links for list table's header for sorting
        if($filter_data['order'] == 'ASC'){
            $order = 'DESC';
        }else{
            $order = 'ASC';
        }
        
        // for blade
        $data['sort'] = strtolower($filter_data['sort']);
        $data['order'] = strtolower($order);

        $url_queries = UrlHelper::resetUrlQueries(unset_arr:['sort', 'order']);

        $url = '';

        foreach($url_queries as $key => $value){
            $url .= "&$key=$value";
        }


        // link of table header for sorting        
        $route = route('lang.admin.inventory.products.list');

        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
        $data['sort_specification'] = $route . "?sort=specification&order=$order" .$url;
        $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_model'] = $route . "?sort=model&order=$order" .$url;
        $data['sort_accounting_category_code'] = $route . "?sort=accounting_category_code&order=$order" .$url;
        $data['sort_supplier_short_name'] = $route . "?sort=supplier_name&order=$order" .$url;
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
        $queries = $this->getQueries($this->request->query());

        $data['save_url'] = route('lang.admin.inventory.products.save');
        $data['back_url'] = route('lang.admin.inventory.products.index', $queries);
        $data['product_autocomplete_url'] = route('lang.admin.inventory.products.autocomplete');

        // Get record
        $product = $this->ProductService->findIdOrFailOrNew($product_id);

        $extra_columns = $queries['extra_columns'] ?? [];
        $extra_columns[] = ['supplier_name'];
        $extra_columns[] = ['supplier_product_name'];
        $extra_columns[] = ['avaible_unit_codes'];
        $product = $this->ProductService->setRowExtraColumns($product, $extra_columns);
        $product = $this->ProductService->setMetaRows($product);



        // Default column value
        if(empty($product->id)){
            $product->is_active = 1;
        }

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
            }
        }
        $data['product_translations'] = $product_translations;
        

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

        // product_accounting_category
        $filter_data = [
            'equal_taxonomy_code' => 'product_accounting_category',
            'pagination' => false,
            'limit' => 30,
            'sort' => 'code',
            'order' => 'ASC'
        ];
        $accounting_categories = $this->TermRepository->getRows($filter_data);
        $data['accounting_categories'] = $this->TermRepository->refineRows($accounting_categories, ['optimize' => true,'sanitize' => true]);


        $data['bom_products'] = [];

        $data['product_options'] = [];
        
        $data['source_type_codes'] = $this->ProductService->getProductSourceCodes();

        // supplier
        $data['supplier_autocomplete_url'] = route('lang.admin.counterparty.suppliers.autocomplete');

        // supplier_product
        $data['supplier_product_autocomplete_url'] = route('lang.admin.inventory.products.autocomplete');
        

        // product_units & destination_units dropdown menu
        $product_units = $product->product_units;
        
        $codes = [];
        
        // 還沒設定任何單位轉換，選單只能出現庫存單位
        if(count($product_units) == 0 && !empty($product->stock_unit_code)){
            $codes[$product->stock_unit_code] = $product->stock_unit->name ?? '';
        }
        // 已有單位轉換，抓出所有不重複的單位
        else if(count($product_units) > 0){
            foreach ($product_units as $product_unit) {
                $source_unit_code = $product_unit->source_unit_code;
                $destination_unit_code = $product_unit->destination_unit_code;

                if(empty($codes[$source_unit_code])){
                    $codes[$source_unit_code] = $product_unit->source_unit->name;
                }

                if(empty($codes[$destination_unit_code])){
                    $codes[$destination_unit_code] = $product_unit->destination_unit->name;
                }

            }
        }

        foreach ($codes as $code => $value) {
            $data['destination_units'][] = (object) [
                'code' => $code,
                'name' => $value,
            ];
        }


        for ($i = 0; $i < 5; $i++) {
            if(!empty($product_units[$i])){
                $product_unit = $product_units[$i];
                $new_product_units[$i] = (object) [
                    'id' => $product_unit->id,
                    'source_unit_code' => $product_unit->source_unit_code,
                    'source_quantity' => $product_unit->source_quantity,
                    'destination_unit_code' => $product_unit->destination_unit_code,
                    'destination_quantity' => $product_unit->destination_quantity,
                    'level' => $product_unit->level,
                ];
            }else{
                $new_product_units[$i] = (object) [
                    'source_unit_code' => '',
                    'source_quantity' => 0,
                    'destination_unit_code' => '',
                    'destination_quantity' => 0,
                    'level' => 0,
                ];
            }
        }
        $data['product_units'] = $new_product_units;

        // units
        $filter_data = [
            'filter_keyword' => $this->request->filter_keyword,
            'pagination' => false,
            'limit' => 0,
        ];
        $data['units'] = $this->UnitRepository->getKeyedActiveUnits($filter_data);

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

        // Check units
        $arr_source_unit_code = [];
        foreach ($data['units'] ?? [] as $key => $unit) {
            $source_unit_code = $unit['source_unit_code'];
            if(!empty($source_unit_code) && in_array($source_unit_code, $arr_source_unit_code)){
                $json['error']['warning'] = '換算單位重複！';
                break;
            }
            $arr_source_unit_code[] = $unit['source_unit_code'];
        }

        if (isset($json['error']) && !isset($json['error']['warning'])) {
            $json['error']['warning'] = $this->lang->error_warning;
        }

        if(!$json) {
            $result = $this->ProductService->saveProduct($data);

            if(empty($result['error'])){
                $json = [
                    'success' => $this->lang->text_success,
                    'product_id' => $result['id'],
                    'redirectUrl' => route('lang.admin.inventory.products.form', $result['id']),
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
        $url_queries = $this->request->query();
        
        $json = [];

        // * 檢查錯誤

        foreach ($url_queries as $key => $value) {
            //檢查查詢字串
            if(str_starts_with($key, 'filter_') || str_starts_with($key, 'equal_')){
                //檢查輸入字串是否包含注音符號
                if (preg_match('/[\x{3105}-\x{3129}\x{02C7}]+/u', $value)) {
                    $json['error'] = '包含注音符號不允許查詢';
                } 
            }
        }

        if(!empty($json)){
            return response(json_encode($json))->header('Content-Type','application/json');
        }



        // Prepare query_data for records
        $filter_data = UrlHelper::getUrlQueriesForFilter();

        // with
        $with = [];
        if(!empty($filter_data['with'])){
            $with = $filter_data['with']; // will be used later
        }

        // exra_columns
        $extra_columns = [];
        if(!empty($filter_data['extra_columns'])){
            $extra_columns = $filter_data['extra_columns']; ; // will be used later
        }

       // $extra_columns[] = 'source_unit_name';

        $products = $this->ProductService->getProducts($filter_data);

        foreach ($products as $product) {

            $new_row = array(
                'label' => $product->name . '-' . $product->specification . '-' . $product->id,
                'value' => $product->id,
                'product_id' => $product->id,
                'name' => $product->name,
                'specification' => $product->specification,
            );

            if(!empty($extra_columns)){
                foreach($extra_columns as $extra_column){
                    $new_row[$extra_column] = $product->$extra_column;
                }
            }

            // product_units
            if(in_array('product_units', $with)){
                $product_units = [];

                if(!empty($product->product_units)){
                    $product_units = $product->product_units->keyBy('source_unit_code')->toArray();

                    data_forget($product_units, '*.source_unit');
                    data_forget($product_units, '*.destination_unit');
                    
                    foreach ($product_units as $product_unit_key => $product_unit) {
                        $product_units[$product_unit_key] = $product_unit;
                    }

                    $new_row['product_units'] = $product_units;
                }

                if(empty($new_row['product_units'][$product->stock_unit_code])){
                    $new_row['product_units'][$product->stock_unit_code] = [
                        'source_unit_name' => $product->stock_unit_name,
                        'source_unit_code' => $product->stock_unit_code,
                        'source_quantity' => 1,
                        'destination_unit_code' => $product->stock_unit_code,
                        'destination_quantity' => 1,

                    ];

                }
            }

            $json[] = $new_row;
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }


    public function delete()
    {
        $json['error'] = '目前不提供刪除！';

        return response(json_encode($json))->header('Content-Type','application/json');
    }


    public function exportInventoryProductList()
    {
        $post_data = request()->post();
        return $this->ProductService->exportInventoryProductList($post_data); 
    }
}