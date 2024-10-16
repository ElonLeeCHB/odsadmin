<?php

namespace App\Repositories\Eloquent\Catalog;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Repositories\Eloquent\Catalog\OptionValueRepository;
use App\Models\Catalog\Option;
use App\Models\Catalog\OptionValue;
use App\Models\Catalog\ProductOption;

class OptionRepository extends Repository
{
    public $modelName = "\App\Models\Catalog\Option";


    public function __construct(protected OptionValueRepository $OptionValueRepository)
    {
        parent::__construct();
    }


    public function getOptions($data=[], $debug = 0)
    {
        return $this->getRows($data, $debug);
    }


    public function getOption($data=[], $debug = 0)
    {
        return $this->getRow($data, $debug);
    }


    public function getValues($data=[], $debug = 0)
    {
        return $this->OptionValueRepository->getRows($data, $debug);
    }


    public function destroy($option_ids)
    {
        try {
            DB::beginTransaction();

            $options = Option::whereIn('id', $option_ids)->get();

            foreach ($options as $option) {
                if ($option->option_values->isNotEmpty()) {
                    foreach ($option->option_values as $option_value) {
                        $option_value->translations()->delete();
                    }
                }
                $option->option_values()->delete();
                $option->translations()->delete();
                $option->delete();
            }
            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }


    public function deleteOptionById($option_id)
    {
        try {

            $option = $this->getRow(['equal_option_id' => $option_id]);

            if ($option) {
    
                DB::beginTransaction();

                $option->option_values->translations()->delete();
                $option->option_values()->delete();
                $option->translations()->delete();
                $option->delete();

                DB::commit();
            }


        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }



    }


    public function getProductCountByOptionId($option_id)
    {
        return ProductOption::where('option_id', $option_id)->count();
    }

}

