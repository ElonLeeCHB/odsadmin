<?php

namespace App\Domains\Admin\Http\Controllers\Catalog;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Domains\Admin\Services\Catalog\PosCategoryService;
use App\Repositories\Eloquent\Localization\LanguageRepository;

class PosCategoryController extends BackendController
{
    public function __construct(private PosCategoryService $PosCategoryService, private LanguageRepository $LanguageRepository)
    {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/catalog/category']);
    }
    
    public function index()
    {
        $data['lang'] = $this->lang;

        // Breadcomb
        $breadcumbs[] = (object)[
            'text' => '首頁',
            'href' => route('lang.admin.dashboard'),
        ];
        
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_product,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];
        
        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.catalog.poscategories.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        $data['list'] = $this->getList();

        $data['list_url']   =  route('lang.admin.catalog.poscategories.list');
        $data['add_url']    = route('lang.admin.catalog.poscategories.form');
        $data['delete_url'] = route('lang.admin.catalog.poscategories.destroy');

        return view('admin.catalog.poscategory', $data);
    }


    public function list()
    {
        return $this->getList();
    }


    private function getList()
    {
        $data['lang'] = $this->lang;


        // Prepare query_data for records
        $query_data  = $this->url_data;

        // Rows
        $categories = $this->PosCategoryService->getList($this->url_data);

        if(!empty($categories)){
            //排序
            if (isset($this->url_data['sort']) && $this->url_data['sort'] === 'sort_order') {
                $order = strtoupper($this->url_data['order'] ?? 'ASC');
            
                usort($categories, function ($a, $b) use ($order) {
                    if ($order === 'DESC') {
                        return $b->sort_order <=> $a->sort_order;
                    }
                    return $a->sort_order <=> $b->sort_order;
                });
            }

            foreach ($categories as $row) {
                $row->edit_url = route('lang.admin.catalog.poscategories.form', array_merge([$row->id], $query_data));
            }
        }

        $data['categories'] = $categories;

        
        // Prepare links for list table's header
        if(isset($query_data['order']) && $query_data['order'] == 'ASC'){
            $order = 'DESC';
        }else{
            $order = 'ASC';
        }
        
        $data['sort'] = strtolower($query_data['sort'] ?? '');
        $data['order'] = strtolower($order);

        $query_data = $this->unsetUrlQueryData($query_data);
        
        
        // link of table header for sorting
        $url = '';

        foreach($query_data as $key => $value){
            if(is_string($value)){
                $url .= "&$key=$value";
            }
        }

        $route = route('lang.admin.catalog.poscategories.list');

        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
        $data['sort_code'] = $route . "?sort=code&order=$order" .$url;
        $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_sort_order'] = $route . "?sort=sort_order&order=$order" .$url;
        $data['sort_taxonomy_name'] = $route . "?sort=taxonomy_name&order=$order" .$url;
        $data['sort_is_active'] = $route . "?sort=is_active&order=$order" .$url;
        $data['sort_date_added'] = $route . "?sort=created_at&order=$order" .$url;

        $data['list_url'] = route('lang.admin.catalog.poscategories.list');

        return view('admin.catalog.poscategory_list', $data);
    }


    public function form($poscategory_id = null)
    {
        $data['lang'] = $this->lang;
  
        $this->lang->text_form = empty($poscategory_id) ? $this->lang->trans('text_add') : $this->lang->trans('text_edit');

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
        $queries  = $this->url_data;

        
        $data['save_url'] = route('lang.admin.catalog.poscategories.save', ['poscategory_id' => $poscategory_id]);
        $data['back_url'] = route('lang.admin.catalog.poscategories.index', $queries);
        $data['autocomplete_url'] = route('lang.admin.catalog.poscategories.autocomplete');

        // Get Record
        $result = $this->PosCategoryService->findIdOrFailOrNew($poscategory_id, ['equal_taxonomy_code' => 'ProductPosCategory']);

        if(!empty($result['data'])){
            $poscategory = $result['data'];
        }else if(!empty($result['error'])){
            return response(json_encode(['error' => $result['error']]))->header('Content-Type','application/json');
        }
        unset($result);

        $data['poscategory']  = $poscategory;
        
        if(!empty($data['poscategory']) && $poscategory_id == $poscategory->id){
            $data['poscategory_id'] = $poscategory_id;
        }else{
            $data['poscategory_id'] = null;
        }

        // translations
        if($poscategory->translations->isEmpty()){
            $translations = [];
        }else{
            foreach ($poscategory->translations as $translation) {
                $translations[$translation->locale] = $translation->toArray();
            }
        }
        $data['translations'] = $translations;
        
        $data['taxonomy_code'] = 'ProductPosCategory';
        
        return view('admin.catalog.poscategory_form', $data);
    }

    public function save($poscategory_id = null)
    {
        $json = [];

        if (isset($json['error']) && !isset($json['error']['warning'])) {
            $json['error']['warning'] = $this->lang->error_warning;
        }

        // 以上驗證錯誤，400
        if(!empty($json)) {
            return response()->json($json, 400);
        }

        $result = $this->PosCategoryService->save($poscategory_id, $this->post_data);

        // 執行錯誤，500
        if(!empty($result['error'])){
            return $this->getErrorResponse($result['error'], $this->lang->text_fail, 500);
        }

        // 執行成功 200
        $json = [
            'poscategory_id' => $result['term_id'],
            'success' => $this->lang->text_success,
            'redirectUrl' => route('lang.admin.catalog.poscategories.form', $result['term_id']),
        ];
        
        return response()->json($json, 200);
    }

    public function autocomplete()
    {

        // Rows
        $rows = $this->PosCategoryService->getAutocomplete($this->url_data);

        $json = [];

        foreach ($rows as $row) {
            $json[] = array(
                '_label' => $row->name,
                '_value' => $row->id,
                'term_id' => $row->id,
            );
        }
        
        return response()->json($json, 200);
    }
}