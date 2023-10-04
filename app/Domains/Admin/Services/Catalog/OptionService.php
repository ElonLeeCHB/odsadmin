<?php

namespace App\Domains\Admin\Services\Catalog;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Services\Catalog\OptionService as GlobalOptionService;
use App\Repositories\Eloquent\Catalog\OptionRepository;
use App\Repositories\Eloquent\Catalog\OptionValueRepository;
use App\Models\Catalog\OptionTranslation;
use App\Models\Catalog\OptionValueTranslation;

class OptionService extends GlobalOptionService
{
    protected $modelName = "\App\Models\Catalog\Option";


    public function __construct(protected OptionRepository $OptionRepository, protected OptionValueRepository $OptionValueRepository)
    {
        parent::__construct($OptionRepository);
    }


    public function updateOrCreate($data)
    {
        DB::beginTransaction();

        try {
            extract($data);

            // Option
            $option = $this->findIdOrFailOrNew($data['option_id']);

            $option->code = $code ?? null;
            $option->type = $type;
            $option->model = 'Product';
            $option->sort_order = $sort_order;
            $option->is_active = $is_active ?? '1';
    
            $option->save();

            // Option Translations
            if(!empty($data['option_translations'])){
                $this->saveTranslationData($option, $data['option_translations']);
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
            $this->OptionValueRepository->model->where('option_id',$option_id)->delete();

            // Add option values and translations
            if(!empty($data['option_values'])){
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
                        'code' => $option_value['code'] ?? $option_value['sort_order'] ?? 0,
                        'product_id' => $product_id,
                        'sort_order' => $option_value['sort_order'] ?? 0,
                        'is_active' => $option_value['is_active'] ?? 1,
                    ];
                    $option_value_model = $this->OptionValueRepository->model->create($arr);

                    //option value translations
                    if(!empty($option_value)){
                        $arr = [];
                        foreach($option_value['option_value_translations'] as $locale => $value){
                            $arr = [
                                'option_id' => $option->id,
                                'option_value_id' => $option_value_model->id,
                                'locale' => $locale,
                                'name' => $value['name'],
                                'short_name' => $value['short_name'] ?? $value['name'],
                            ];
                            OptionValueTranslation::create($arr);
                        }
                    }
                }
            }

            DB::commit();

            $result['data']['option_id'] = $option->id;
            
            return $result;
            
        } catch (\Exception $ex) {
            DB::rollback();
            $msg = $ex->getMessage();
            echo '<pre>', print_r($msg, 1), "</pre>"; exit;
            //return response()->json(['error' => $msg], 500);
        }
    }
    

    
}