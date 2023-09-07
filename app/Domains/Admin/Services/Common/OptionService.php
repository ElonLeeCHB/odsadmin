<?php

namespace App\Domains\Admin\Services\Common;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Repositories\Eloquent\Common\OptionRepository;
use App\Repositories\Eloquent\Common\OptionValueRepository;
use App\Models\Common\OptionTranslation;
use App\Models\Common\OptionValueTranslation;

class OptionService extends Service
{
    protected $modelName = "\App\Models\Common\Option";

	public function __construct(public OptionRepository $repository
        , private OptionValueRepository $OptionValueRepository)
	{        

	}
    

    public function getOptions($data=[], $debug = 0)
    {
        $rows = $this->repository->getRows($data,$debug);
    
        return $rows;
    }
    

    public function getValues($data, $debug=0)
    {
        $option_values = $this->OptionValueRepository->getRows($data, $debug);

        return $option_values;
    }


    public function updateOrCreate($data)
    {
        DB::beginTransaction();

        try {
            extract($data);

            // Option
            $option = $this->repository->findIdOrFailOrNew($data['option_id']);

            $option->code = $code ?? null;
            $option->type = $type;
            $option->model = 'Product';
            $option->sort_order = $sort_order;
            $option->is_active = $is_active ?? '1';
    
            $option->save();

            // Option Translations
            if(!empty($data['option_translations'])){
                $this->repository->saveTranslationData($option, $data['option_translations']);
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

    public function deleteOption($option_id)
    {
        DB::beginTransaction();

        try {
            $msg = [];

            $filter_data = [
                'filter_id' => $option_id,
            ];
            $option = $this->repository->getRow($filter_data);

            if(!empty($option)){
                //下面ok。保留作範例
                // foreach ($option->option_values as $option_value) {
                //     $option_value->deleteTranslations();
                //     $option_value->delete();
                // }
                // $option->deleteTranslations();
                // $option->delete();

                OptionValueTranslation::where('option_id',$option_id)->delete();
                OptionTranslation::where('option_id',$option_id)->delete();
                $this->OptionValueRepository->newModel()->where('option_id',$option_id)->delete();
                $this->repository->newModel()->where('id',$option_id)->delete();
            }

            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            $msg['error'] = $ex->getMessage();
            return $msg;
        }     
    }

    public function delete($ids)
    {
        DB::beginTransaction();

        try {
            $msg = [];

            $filter_data = [
                'whereIn' => $ids,
                'limit' => 0,
                'pagination' => false,
            ];
            $options = $this->getRows($filter_data);

            foreach ($options as $option) {
    
                if(!$option->product_options->isEmpty()) {
                    echo '<pre>', print_r($option->product_options->toArray(), 1), "</pre>"; exit;
                    $msg['error'] = '警告: 選項代號 '.$option->id.' 已有商品使用，不可刪除。';
                    return $msg;                    
                }
            }

            if(!$msg){
                foreach ($options as $option) {
                    /*
                    OptionValueTranslation::where('option_id',$option->id)->delete();
                    $this->OptionValueRepository->model->where('option_id',$$option->id)->delete();
                    OptionTranslation::where('id',$option->id)->delete();
                    $this->OptionRepository->model->where('id',$$option->id)->delete();
                    */

                    $this->OptionValueRepository->deleteTranslations($option->id);
                }

                if(!is_array($ids)){
                    $this->deleteById($ids);
                }else{
                    foreach ($ids as $id) {
                        $this->deleteById($id);
                    }
                }
                DB::commit();
            }
            
        } catch (\Exception $ex) {
            DB::rollback();
            $msg['error'] = $ex->getMessage();
            return $msg;
        }
    }

    public function deleteById($id)
    {
        $option = $this->repository->newModel()->find($id);

        if(!$option->option_values->isEmpty()){
            foreach($option->option_values as $option_value){
                $option_value->deleteTranslations();
                $option_value->delete();
            }
        }

        $option->deleteTranslations();
        $option->delete();
    }


}