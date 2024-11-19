<?php

namespace App\Domains\ApiV2\Services\User;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Domains\ApiV2\Services\Service;
use App\Domains\ApiV2\Services\Catalog\OptionService;
use App\Traits\Model\EloquentTrait;

class UserService extends Service
{
    use EloquentTrait;

    public $modelName;
    public $model;
    public $table;
    public $lang;

	public function __construct(private OptionService $OptionService)
    {
        $this->modelName = "\App\Models\User\User";
	}

    public function getSalutations()
    {
        $cacheName = app()->getLocale() . '_user_salutations';

        $salutations = cache()->remember($cacheName, 60*60*24*365, function(){
            // Option
            $filter_data = [
                'filter_code' => 'salutation',
                'with' => 'option_values.translation'
            ];
            $option = $this->OptionService->getRecord($filter_data);

            // Option Values
            $option_values = $option->option_values;

            foreach($option_values as $option_value){
                $key = $option_value->id;
                $result[$key] = $option_value;
            }

            return $result;
        });

        if(empty($salutations)){
            $salutations = [];
        }

        return $salutations;
    }
}
