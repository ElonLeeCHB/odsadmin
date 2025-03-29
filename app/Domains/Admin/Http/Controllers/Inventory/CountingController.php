<?php

namespace App\Domains\Admin\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Domains\Admin\Services\Inventory\CountingService;
use App\Repositories\Eloquent\Catalog\ProductRepository;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Http\Resources\Inventory\CountingProductCollection;
use App\Http\Resources\Inventory\CountingProductResource;
use App\Http\Resources\Inventory\CountingResource;
use App\Models\Inventory\Counting;
use App\Helpers\Classes\DataHelper;
use App\Helpers\Classes\UrlHelper;

class CountingController extends BackendController
{
    public function __construct(private Request $request, private CountingService $CountingService, private ProductRepository $ProductRepository, private TermRepository $TermRepository)
    {
        parent::__construct();
        
        $this->getLang(['admin/common/common','admin/inventory/product','admin/inventory/counting']);
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
            'text' => $this->lang->text_menu_inventory,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];
        
        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.inventory.countings.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;


        // 通用單據狀態
        $data['statuses'] = $this->CountingService->getCodeKeyedTermsByTaxonomyCode('common_form_status',toArray:false);

        $data['list'] = $this->getList();

        $data['list_url'] = route('lang.admin.inventory.countings.list'); //本參數在 getList() 也必須存在。
        $data['add_url'] = route('lang.admin.inventory.countings.form');
        $data['delete_url'] = route('lang.admin.inventory.countings.delete');
        $data['export_counting_product_list'] = route('lang.admin.inventory.countings.export_counting_product_list');
        
        return view('admin.inventory.counting', $data);
    }

    public function list()
    {
        return $this->getList();
    }

    private function getList()
    {
        $data['lang'] = $this->lang;

        $query_data  = $this->url_data;


        // Prepare query_data for records

        $filter_data = UrlHelper::getUrlQueriesForFilter();

        $extra_columns = $filter_data['extra_columns'] ?? [];
        $filter_data['extra_columns'] = DataHelper::addToArray('accounting_category_name', $extra_columns);
        
        $filter_data['with'] = DataHelper::addToArray('unit', $filter_data['with'] ?? []);
        
        // Rows

        $countings = $this->CountingService->getCountings($filter_data);

        foreach ($countings ?? [] as $row) {
            $row->edit_url = route('lang.admin.inventory.countings.form', array_merge([$row->id], $query_data));
            $row->unit_name = $row->unit->name ?? '';
        }

        $data['countings'] = $countings->withPath(route('lang.admin.inventory.countings.list'))->appends($query_data);


        //$data['pagination'] = $countings->links('admin.pagination.default');


        // For list table's header: sorting
        if($filter_data['order'] == 'ASC'){
            $order = 'DESC';
        }else{
            $order = 'ASC';
        }
        

        // for blade
        $data['sort'] = strtolower($filter_data['sort']);
        $data['order'] = strtolower($order);

        
        $query_data = UrlHelper::resetUrlQueries(unset_arr:['sort', 'order']);

        $url = '';

        foreach($query_data as $key => $value){
            if(is_string($value)){
                $url .= "&$key=$value";
            }
        }
        
        
        // For list table's header: link

        $route = route('lang.admin.inventory.countings.list');

        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;

        $data['sort_form_date'] = $route . "?sort=form_date&order=$order" .$url;
        $data['sort_status_code'] = $route . "?sort=status_code&order=$order" .$url;
        
        $data['list_url'] = route('lang.admin.inventory.countings.list');
        
        return view('admin.inventory.counting_list', $data);
    }

    public function form($counting_id = null)
    {
        $data['lang'] = $this->lang;

        $this->lang->text_form = empty($unit_id) ? $this->lang->trans('text_add') : $this->lang->trans('text_edit');

        // Breadcomb
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_home,
            'href' => route('lang.admin.dashboard'),
        ];

        $breadcumbs[] = (object)[
            'text' => $this->lang->text_menu_inventory,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];

        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.inventory.countings.index'),
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

        $data['save_url'] = route('lang.admin.inventory.countings.save');
        $data['back_url'] = route('lang.admin.inventory.countings.index', $queries);
        $data['status_save_url'] = route('lang.admin.inventory.countings.saveStatusCode');

        $data['import_url'] = route('lang.admin.inventory.countings.import',['counting_id' => $counting_id]);
        $data['product_autocomplete_url'] = route('lang.admin.inventory.products.autocomplete'); 

        // Get Record
        $result = $this->CountingService->findIdOrFailOrNew($counting_id);

        if(empty($result['error']) && !empty($result['data'])){
            $counting = $result['data'];
        }else if(!empty($result['error'])){
            return response(json_encode(['error' => $result['error']]))->header('Content-Type','application/json');
        }
        unset($result);

        $data['counting'] = $counting;
        

        $counting->load([
            'counting_products.product.translation',
            'counting_products.product.stock_unit.translation',
            'counting_products.unit.translation',
        ]);

        $data['counting_products']  = (new CountingResource($counting))->getCountingProductsObject();

        foreach ($data['counting_products']  as $key => $counting_product) {
            $data['counting_products'][$key]->product_edit_url = route('lang.admin.inventory.products.form', $counting_product->product_id);
        }

        $data['product_row'] = count($data['counting_products'])+1;

        if(!empty($data['counting']) && $counting_id == $counting->id){
            $data['counting_id'] = $counting_id;
        }else{
            $data['counting_id'] = null;
        }


        $data['counting_product_list'] = $this->getCountingProductList($data['counting_products']);

        
        // 通用單據狀態
        $data['statuses'] = $this->CountingService->getCodeKeyedTermsByTaxonomyCode('common_form_status',toArray:false);
        

        return view('admin.inventory.counting_form', $data);
    }

    public function getCountingProductList($counting_products)
    {

        $data['counting_products'] = $counting_products;

        return view('admin.inventory.counting_form_products', $data);
    }

    public function save()
    {
        $post_data = $this->request->post();
        $json = [];
        if(empty($this->request->name) || mb_strlen($this->request->name) < 1 ){
           // $json['error']['name'] = '請輸入名稱 1-20 個字';
        }

        // 檢查欄位

        // 狀態碼
        $params = [
            'equal_id' => $post_data['counting_id'],
            'select' => ['id', 'status_code'],
        ];
        $row = $this->CountingService->getRow($params);
        if(!empty($row->status_code ) && $row->status_code != 'P'){
            $json['error']['status_code'] = '單據未確認才可修改。現在是 ' . $row->status_name;
            $json['error']['warning'] = '單據未確認才可修改。現在是 ' . $row->status_name;
        }

        if(isset($json['error']) && !isset($json['error']['warning'])) {
            $json['error']['warning'] = $this->lang->error_warning;
        }
        if(!$json) {
            $result = $this->CountingService->saveCounting($post_data);

            if(empty($result['error'])){
                $counting_id = $result['id'];


                $json = [
                    'success' => $this->lang->text_success,

                    'counting_id' => $counting_id,
                    'code' => $result['code'],
                    'redirectUrl' => route('lang.admin.inventory.countings.form', $counting_id),
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

    public function destroy()
    {
        $post_data = $this->request->post();

		$json = [];

        // Permission
        if($this->acting_username !== 'admin'){
            $json['error'] = $this->lang->error_permission;
        }

        // Selected
		if (isset($post_data['selected'])) {
			$selected = $post_data['selected'];
		} else {
			$selected = [];
		}

		if (!$json) {

			foreach ($selected as $category_id) {
				$result = $this->CountingService->destroy($category_id);

                if(!empty($result['error'])){
                    if(config('app.debug')){
                        $json['error'] = $result['error'];
                    }else{
                        $json['error'] = $this->lang->text_fail;
                    }

                    break;
                }
			}
		}
        
        if(empty($json['error'] )){
            $json['success'] = $this->lang->text_success;
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }

    public function imports($counting_id = null)
    {
        $data = request()->all();
        $counting_id = !empty($data['counting_id']) ? $data['counting_id'] : null;

        if (request()->hasFile('file')) {
            $file = request()->file('file');
            $filename = date('Y-m-d_H-i-s') . '.' . $file->getClientOriginalExtension();


            $file->move(storage_path('app/imports/receiving_orders'), $filename);

            $fullpath = storage_path('app/imports/receiving_orders') . '/' . $filename;
            $newfile = new UploadedFile($fullpath, $filename, $file->getClientMimeType());
            
            //重新設定 $filename
            $filename = $newfile->getPathname();


            $result = $this->CountingService->readExcel($filename, $counting_id);

            if(!empty($result['error'])){
                $data['error'] = $result['error'];
            }else{                
                $data['counting_products'] = $result['counting_products'];
            }
            
            return view('admin.inventory.counting_form_products', $data);
        }


        return response()->json(['message' => 'Error !!!'], 422);
    }

    public function exportCountingProductList()
    {
        $post_data = request()->post();

        return $this->CountingService->exportCountingProductList($post_data); 
    }


    public function saveStatusCode()
    {
        $json = [];

        if(auth()->user()->username != 'admin'){
            $json['error'] = $this->lang->error_permission;
        }

        if(!$json){
            $post_data = request()->all();
            $new_data = $post_data['update_status'];
            $result = $this->CountingService->saveStatusCode($new_data);
    
            if(!empty($result['data']['id'])){
                $json = [
                    'success' => '狀態已變更為：' . $result['data']['status_name'],
                    'data' => $result['data'],
                ];
            }
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }
    

    public function productSettings()
    {
        $data['lang'] = $this->lang;

        // Breadcomb
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_home,
            'href' => route('lang.admin.dashboard'),
        ];

        $breadcumbs[] = (object)[
            'text' => $this->lang->text_menu_inventory,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];
        
        $breadcumbs[] = (object)[
            'text' => '盤點作業設定',
            'href' => route('lang.admin.inventory.countings.productSettings'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        $data['countingSetting'] = $this->CountingService->getCountingSettings();

        // 存放溫度類型 temperature_type_code
        $data['temperature_types'] = $this->CountingService->getCodeKeyedTermsByTaxonomyCode('product_storage_temperature_type',toArray:false);

        $data['save_url'] = route('lang.admin.inventory.countings.productSettings');

        return view('admin.inventory.counting_setting_products_form', $data);
        
    }

    public function saveProductSettings()
    {
        $json = [];


        if(!$json) {
            $result = $this->CountingService->saveCountingSettings($this->post_data);

            if(empty($result['error'])){
                $json['success'] = $this->lang->text_success;
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
}