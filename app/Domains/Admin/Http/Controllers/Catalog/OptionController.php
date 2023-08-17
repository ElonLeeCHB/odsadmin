<?php

namespace App\Domains\Admin\Http\Controllers\Catalog;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Libraries\TranslationLibrary;
use App\Repositories\Eloquent\Localization\LanguageRepository;
use App\Domains\Admin\Services\Common\OptionService;
use App\Domains\Admin\Services\Catalog\ProductService;

class OptionController extends BackendController
{
    public function __construct(private Request $request
        , private LanguageRepository $LanguageRepository
        , private OptionService $OptionService
        , private ProductService $ProductService)
    {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/common/option']);
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
            'text' => $this->lang->text_option,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];
        
        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.catalog.options.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;
        $data['list'] = $this->getList();
        
        $data['delete'] = route('lang.admin.catalog.options.delete');
        $data['add'] = route('lang.admin.catalog.options.form');

        return view('admin.catalog.option', $data);
    }

    public function list()
    {
        $data['lang'] = $this->lang;

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
        $queries = [];

        if(!empty($this->request->query('page'))){
            $page = $queries['page'] = $this->request->input('page');
        }else{
            $page = $queries['page'] = 1;
        }

        if(!empty($this->request->query('sort'))){
            $sort = $queries['sort'] = $this->request->input('sort');
        }else{
            $sort = $queries['sort'] = 'sort_order';
        }

        if(!empty($this->request->query('order'))){
            $order = $queries['order'] = $this->request->query('order');
        }else{
            $order = $queries['order'] = 'ASC';
        }

        if(!empty($this->request->query('limit'))){
            $limit = $queries['limit'] = $this->request->query('limit');
        }

        foreach($this->request->all() as $key => $value){
            if(strpos($key, 'filter_') !== false){
                $queries[$key] = $value;
            }
        }

        $queries['filter_model'] = 'Product';

        $data['action'] = route('lang.admin.catalog.options.list');

        // Rows
        $options = $this->OptionService->getRows($queries);
        
        if(!empty($options)){
            foreach ($options as $row) {
                $row->edit_url = route('lang.admin.catalog.options.form', array_merge([$row->id], $queries));
            }
        }
        $data['options'] = $options->withPath(route('lang.admin.catalog.options.list'))->appends($queries);

        // Prepare links for list table's header
        if($order == 'ASC'){
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
        $route = route('lang.admin.catalog.options.list');
        $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_model'] = $route . "?sort=model&order=$order" .$url;
        $data['sort_price'] = $route . "?sort=price&order=$order" .$url;
        $data['sort_quantity'] = $route . "?sort=quantity&order=$order" .$url;
        $data['sort_date_added'] = $route . "?sort=created_at&order=$order" .$url;

        return view('admin.catalog.option_list', $data);
    }


    public function form($option_id = null)
    {
        $data['lang'] = $this->lang;
  
        $this->lang->text_form = empty($option_id) ? $this->lang->trans('text_add') : $this->lang->trans('text_edit');

        // Breadcomb
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_home,
            'href' => route('lang.admin.dashboard'),
        ];
        
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_option,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];
        
        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.catalog.options.index'),
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

        $data['save'] = route('lang.admin.catalog.options.save');
        $data['back'] = route('lang.admin.catalog.options.index', $queries);

        // Languages
        $languages = $this->LanguageRepository->model->active()->get();
        $data['languages'] = $languages;

        // Get Record
        $option = $this->OptionService->findIdOrFailOrNew($option_id);

        $data['option']  = $option;

        // option_translations
        if($option->translations->isEmpty()){
            $option_translations = [];
        }else{
            foreach ($option->translations as $translation) {
                $locale = str_replace('-', '_', $translation->locale);
                $option_translations[$locale] = $translation->toArray();
            }
        }

        $data['option_translations'] = $option_translations;
        
        // Option Values
        if(!empty($option->id)){
            $filter_data = [];
            $filter_data = [
                'filter_option_id' => $option->id,
                'regexp' => false,
                'limit' => 0,
                'pagination' => false,
                'sort' => 'sort_order',
                'order' => 'ASC',
            ];

            $filter_data['with'][] = 'translation';
            if($option->model == 'Product' ){
                $filter_data['with'][] = 'product.translation';
            }

            $option_values = $this->OptionService->getValues($filter_data);

            $data['option_values'] = $option_values;
        }else{
            $data['option_values'] = [];
        }
        //echo '<pre>', print_r($option_values->toArray(), 1), "</pre>"; exit;

        return view('admin.catalog.option_form', $data);
    }
    
    public function save()
    {
        $data = $this->request->all();

        $json = [];
        
        // Check
        $validator = $this->validator($this->request->post());

        if($validator->fails()){
            $messages = $validator->errors()->toArray();
            foreach ($messages as $key => $rows) {
                $json['error'][$key] = $rows[0];
            }

        }

        // Check option_value_id in product_option_values
        if (isset($data['option_values']) && isset($data['option_id'])) {
            $option_value_data = [];
    
            //option_values in form
            foreach ($data['option_values'] as $option_value) {
                if ($option_value['option_value_id']) {
                    $option_value_data[] = $option_value['option_value_id'];
                }
            }

            //option_values in database 
            $option = $this->OptionService->getRow(['filter_id' => $data['option_id']],0);
            $option_values = [];
            if(!empty($option->product_option_values)){
                $option_values = $option->product_option_values->pluck('option_value_id')->toArray() ?? [];
            }
            $option_value_ids = array_unique($option_values);

            foreach ($option_value_ids as $option_value_id) {
                if(!in_array($option_value_id, $option_value_data)){
                    $json['error']['warning'] = $this->lang->error_value;
                }
            }
        }

        if (isset($data['option_values'])) {
			foreach ($data['option_values'] as $option_value_row => $option_value) {
                $option_value_id = $option_value['option_value_id'];
				foreach ($option_value['option_value_translations'] as $locale => $option_value_translation) {
					if ((mb_strlen(trim($option_value_translation['name'])) < 1) || (mb_strlen($option_value_translation['name']) > 128)) {
						$json['error']['option-value-' . $option_value_row . '-' . $locale] = $this->lang->error_option_value;
					}
				}
			}
		}

        if (isset($json['error']) && !isset($json['error']['warning'])) {
            $json['error']['warning'] = $this->lang->error_warning;
        }

        if(!$json) {
            $result = $this->OptionService->updateOrCreate($data);
            if(empty($result['error'])){
                $json['option_id'] = $result['data']['option_id'];
                $json['success'] = $this->lang->text_success;
            }else{
                $json['warning'] = $this->lang->text_fail;
            }
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }


    public function delete()
    {
        $data = $this->request->all();

        $json = [];
        
		if (isset($data['selected'])) {
			$selected = $data['selected'];
		} else {
			$selected = [];
		}

        // permission
		// if (!$this->user->hasPermission('modify', 'catalog/option')) {
		// 	$json['error'] = $this->language->get('error_permission');
		// }
        
		foreach ($selected as $option_id) {
			$product_total = $this->ProductService->getTotalProductsByOptionId($option_id);
            
			if ($product_total) {
				$json['error'] = $option_id .' - '.$this->lang->error_product;
			}
		}
        
		if (!$json) {
			foreach ($selected as $option_id) {
				$this->OptionService->deleteOption($option_id);
			}

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

        if(isset($this->request->filter_is_active)){
            $filter_is_active = $this->request->filter_is_active;
        }else{
            $filter_is_active = true;
        }

        $filter_data = array(
            'filter_model' => $filter_model,
            'filter_name' => $filter_name,
            'filter_is_active' => $filter_is_active,
            'pagination' => false,
            'regexp' => false,
            'with' => 'option_values.product',
        );
        $options = $this->OptionService->getRows($filter_data);

        foreach ($options as $option) {
            $filter_data['filter_option_id'] = $option->id;

            $option_values = $option->option_values;

            $json[] = array(
                'option_id' => $option->id,
                'name' => $option->name,
                'type' => $option->type,
                'option_value' => $option_values,
            );
        }
        
        return response(json_encode($json))->header('Content-Type','application/json');
    }


    public function export()
    {
        $json = [];

        if(isset($this->request->filter_model)){
            $filter_data['filter_model'] = $this->request->filter_model;
        }

        if(isset($this->request->filter_product_id)){
            $filter_data['filter_product_id'] = $this->request->filter_product_id;
        }

        if(isset($this->request->regx)){
            $filter_data['regexp'] = $this->request->regx;
        }else{
            $filter_data['regexp'] = false;
        }

        if(isset($this->request->regx)){
            $filter_data['limit'] = $this->request->limit;
        }else{
            $filter_data['limit'] = 1000;
        }

        $filter_data['pagination'] = false;

        $options = $this->OptionService->export($filter_data);

        if(!$options->isEmpty()){
            foreach ($options as $option) {
                $json[] = [
                    'option_id'    => $option->id,
                    'name'    => $option->name,
                ];
            }

        }


        if(empty($this->request->dataType) || $this->request->dataType == 'json'){
            return response(json_encode($json))->header('Content-Type','application/json');
        }

    }


    public function validator(array $data)
    {
        foreach ($data['option_translations'] as $lang_code => $value) {
            $key = 'name-'.$lang_code;
            $arr[$key] = $value['name'];
            $arr1[$key] = 'required|max:200';
            $arr2[$key] = $this->lang->error_name;
        }

        return Validator::make($arr, $arr1,$arr2);
    }
}