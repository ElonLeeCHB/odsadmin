<?php

namespace App\Repositories\Eloquent\Catalog;

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


    public function deleteOptionById($option_id)
    {
        $option = $this->getRow(['equal_option_id' => $option_id]);

        if ($option) {
            // option_values and translations
            $option->option_values->each(function ($option_value) {
                $option_value->translations->each(function ($translation) {
                    $translation->delete();
                });

                $option_value->delete();
            });

            // translations
            $option->translations->each(function ($translation) {
                $translation->delete();
            });
        
            $option->delete();
        }

    }


    public function getProductCountsByOptionId($option_id)
    {
        return ProductOption::where('option_id', $option_id)->getCount();
    }





}

