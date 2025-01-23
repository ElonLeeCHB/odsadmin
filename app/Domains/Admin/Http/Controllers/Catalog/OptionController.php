<?php

namespace App\Domains\Admin\Http\Controllers\Catalog;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Libraries\TranslationLibrary;
use App\Repositories\Eloquent\Localization\LanguageRepository;
use App\Domains\Admin\Services\Catalog\OptionService;
use App\Helpers\Classes\DataHelper;

class OptionController extends BackendController
{
    private $breadcumbs;

    public function __construct(private Request $request
        , private LanguageRepository $LanguageRepository
        , private OptionService $OptionService)
    {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/catalog/option']);
        $this->setBreadcumbs();
    }

    private function setBreadcumbs()
    {
        $this->breadcumbs = [];

        $this->breadcumbs[] = (object)[
            'text' => $this->lang->text_home,
            'href' => route('lang.admin.dashboard'),
        ];

        $this->breadcumbs[] = (object)[
            'text' => $this->lang->text_option,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];

        $this->breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.catalog.options.index'),
        ];
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
        $data['breadcumbs'] = (object)$this->breadcumbs;

        $data['list'] = $this->getList();

        $data['list_url']   = route('lang.admin.catalog.options.list');
        $data['add_url'] = route('lang.admin.catalog.options.form');
        $data['delete_url'] = route('lang.admin.catalog.options.destroy');

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
        $query_data = $this->resetUrlData(request()->query());

        // Rows, LengthAwarePaginator
            $options = $this->OptionService->getOptions($query_data);

            if(!empty($options)){

                foreach ($options as $row) {
                    $row->edit_url = route('lang.admin.catalog.options.form', array_merge([$row->id], $query_data));
                }
                
                $data['options'] = $options;
                $data['pagination'] = $options->withPath(route('lang.admin.catalog.options.list'))->appends($query_data)->links('admin.pagination.default');
            }else{
                $data['options'] = [];
                $data['pagination'] = '';
            }
        //

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
        //

        // link of table header for sorting
            $route = route('lang.admin.catalog.options.list');

            $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
            $data['sort_main_category_id'] = $route . "?sort=main_category_id&order=$order" .$url;
            $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
            $data['sort_model'] = $route . "?sort=model&order=$order" .$url;
            $data['sort_price'] = $route . "?sort=price&order=$order" .$url;
            $data['sort_quantity'] = $route . "?sort=quantity&order=$order" .$url;
            $data['sort_date_added'] = $route . "?sort=created_at&order=$order" .$url;
        //

        // link of table header for sorting
            $route = route('lang.admin.catalog.options.list');
            $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        //

        $data['action'] = route('lang.admin.catalog.options.list');

        return view('admin.catalog.option_list', $data);
    }


    public function form($option_id = null)
    {
        $data['lang'] = $this->lang;

        $this->lang->text_form = empty($option_id) ? $this->lang->trans('text_add') : $this->lang->trans('text_edit');

        // Breadcomb
        $data['breadcumbs'] = (object)$this->breadcumbs;

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

        $data['save_url'] = route('lang.admin.catalog.options.save');
        $data['back_url'] = route('lang.admin.catalog.options.index', $queries);

        // Languages
        $languages = $this->LanguageRepository->model->active()->get();
        $data['languages'] = $languages;

        // Get Record
        $filter_data = $this->url_data;
        $filter_data['equal_id'] = $option_id;
        $filter_data['with'] = ['translations', 'optionValues.translations', 'optionValues.product'];
        $option = $this->OptionService->getOption($filter_data);
        $data['option']  = DataHelper::toCleanObject($option);

        // option translations
        if($option->translations->isEmpty()){
            $data['option_translations'] = [];
        }else{
            foreach ($option->translations as $translation) {
                $data['option_translations'][$translation->locale] = $translation;
            }
        }

        $sortedOptionValues = $option->optionValues->isEmpty() ? collect([]) : $option->optionValues->sortBy('sort_order');
        
        // option values
        if($sortedOptionValues->isEmpty()){
            $data['option_values'] = [];
        }else{
            foreach ($sortedOptionValues as $option_value) {
                $newOptionValue = DataHelper::toCleanObject($option_value);

                if(!$option_value->translations->isEmpty()){
                    $newOptionValue->translations = DataHelper::toCleanCollection($option_value->translations->keyBy('locale'));
                    $newOptionValue->product = DataHelper::toCleanObject($option_value->product);
                }

                $data['option_values'][] = $newOptionValue;
            }
        }
        
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
            $option_value_form_data = [];

            //option_values in form
            foreach ($data['option_values'] as $option_value) {
                if ($option_value['option_value_id']) {
                    $option_value_form_data[] = $option_value['option_value_id'];
                }
            }

            //option_values in database
            $filter_data = [
                'equal_id' => $data['option_id'],
                'with' => ['product_option_values'],
            ];
            $option = $this->OptionService->getOption($filter_data);

            if(!empty($option->product_option_values)){
                $in_use_option_value_ids = $option->product_option_values->pluck('option_value_id')->toArray() ?? [];
            }
            $in_use_option_value_ids = array_unique($in_use_option_value_ids);

            foreach ($in_use_option_value_ids as $in_use_option_value_id) {
                //表單資料沒有使用中的 option_value_id 代表刪除動作。但這是使用中，所以不准刪。
                if(!in_array($in_use_option_value_id, $option_value_form_data)){
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

        // 以上驗證錯誤，400
        if(!empty($json)) {
            return response()->json($json, 400);
        }

        $result = $this->OptionService->save($data);

        // 執行錯誤，500
        if(!empty($result['error'])){
            return $this->getErrorResponse($result['error'], $this->lang->text_fail, 500);
        }

        // 執行成功 200
        $json = [
            'success' => $this->lang->text_success,
            'option_id' => $result['option_id'],
            'redirectUrl' => route('lang.admin.catalog.options.form', $result['option_id']),
        ];

        return response()->json($json, 200);
    }


    public function destroy()
    {
        $data = $this->request->all();

        $json = [];

		if (isset($data['selected'])) {
			$selected = $data['selected'];
		} else {
			$selected = [];
		}

        // Permission
        if($this->acting_username !== 'admin'){
            $json['error'] = $this->lang->error_permission;
        }

        foreach ($selected as $option_id) {
            // 若有商品使用則不可刪
			$product_count = $this->OptionService->getProductCountByOptionId($option_id);

			if ($product_count) {
				$json['error'] = $option_id .' - '.$this->lang->error_product;
			}
		}

		if (!$json) {
            $result = $this->OptionService->destroy($selected);

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
        $options = $this->OptionService->getOptions($filter_data);

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
