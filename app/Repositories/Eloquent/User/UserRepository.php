<?php

namespace App\Repositories\Eloquent\User;

use App\Repositories\Eloquent\Repository;

class UserRepository extends Repository
{
    public $modelName = "\App\Models\User\User";
    
    public function getAdminUsers($data,$debug=0)
    {
        $data['filter_is_active'] = '-1';
        $data['whereHas']['userMeta'] = ['meta_key' => '=is_admin', 'meta_value' => '=1'];

        $users = $this->getRows($data, $debug);

        return $users;
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
            $option = $this->OptionService->getRow($filter_data);

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