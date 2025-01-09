<?php

namespace App\Domains\Admin\Services\Catalog;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Services\Catalog\OptionService as GlobalOptionService;
use App\Repositories\Eloquent\Catalog\OptionRepository;
use App\Repositories\Eloquent\Catalog\OptionValueRepository;
use App\Models\Catalog\OptionTranslation;
use App\Models\Catalog\OptionValueTranslation;

class OptionService extends Service
{
    protected $modelName = "\App\Models\Catalog\Option";


    // 呈現表單內容，以及儲存的時候，都會用到。呈現表單的時候需要with('option_values')，儲存則不需要。以此原則，with 不適合寫在這裡。
    public function getOption($params)
    {
        $result = $this->findIdOrFailOrNew($params['equal_id'], $params);

        if(!empty($result['data'])){
            return $result['data'];
        }else if(!empty($result['error'])){
            return response(json_encode(['error' => $result['error']]))->header('Content-Type','application/json');
        }
    }


    public function save($data)
    {
        DB::beginTransaction();

        try {
            extract($data);

            // Option
            $result = $this->findIdOrFailOrNew($data['option_id']);

            if(empty($result['error']) && !empty($result['data'])){
                $option = $result['data'];
            }else{
                return response(json_encode($result))->header('Content-Type','application/json');
            }
            $option->code = $code ?? null;
            $option->type = $type;
            $option->model = 'Product';
            $option->is_active = $is_active ?? '1';
            $option->note = $note;

            $option->save();

            // Option Translations
            if(!empty($data['option_translations'])){
                $this->saveRowTranslationData($option, $data['option_translations']);
            }

            //Option Values
            // $filter_data = [];
            // $filter_data['filter_option_id'] = $option->option_id;
            // if($option->model == 'Product' ){
            //     $filter_data['with'] = 'product.translations';
            // }
            // $option_values = $this->getValues($filter_data);


            // Delete all option values and translations. It should be checked in controller.
            OptionValueTranslation::where('option_id',$option_id)->delete();
            (new OptionValueRepository)->model->where('option_id',$option_id)->delete();

            // Add option values and translations
            if(!empty($data['option_values'])){
                $upsert_option_value_data = [];
                foreach ($data['option_values'] as $key => $option_value) {

                    if(empty($option_value['product_name'])){
                        $product_id = null;
                    }else{
                        $product_id = $option_value['product_id'];
                    }

                    //option value
                    $arr = [
                        'id' => $option_value['option_value_id'],
                        'option_id' => $option->id,
                        'code' => $option_value['code'] ?? null,
                        'product_id' => $product_id,
                        'sort_order' => $option_value['sort_order'] ?? 0,
                        'is_on_www' => $option_value['is_on_www'] ?? 1,
                        'is_active' => $option_value['is_active'] ?? 1,
                    ];
                    $option_value_model = (new OptionValueRepository)->model->create($arr);

                    //option value translations
                    if(!empty($option_value)){
                        foreach($option_value['option_value_translations'] as $locale => $value){
                            $upsert_option_value_data[] = [
                                'option_id' => $option->id,
                                'option_value_id' => $option_value_model->id,
                                'locale' => $locale,
                                'name' => $value['name'],
                                'short_name' => $value['short_name'] ?? $value['name'],
                                'web_name' => $value['web_name'] ?? $value['name'],
                            ];
                        }
                    }
                }
                if(!empty($upsert_option_value_data)){
                    OptionValueTranslation::upsert($upsert_option_value_data, ['user_id','meta_key']);
                }
            }

            DB::commit();

            $result['option_id'] = $option->id;

            return $result;

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }


    public function getOptions($params = [], $debug = 0)
    {
        $params['equal_model'] = 'Product';

        return $this->getRows($params, $debug);
    }



}
