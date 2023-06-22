<?php

namespace App\Repositories\Eloquent\User;

use App\Domains\Admin\Traits\Eloquent;
use App\Repositories\Eloquent\Repository;
use App\Models\User\User;
use App\Models\Common\Option;

class UserRepository extends Repository
{
    use Eloquent;

    public $modelName = "\App\Models\User\User";
    
    public function getAdminUsers($data,$debug=0)
    {
        $users = User::whereHas('userMeta', function($query) {
            $query->where('meta_key', 'is_admin')->where('meta_value', '1');
        });

        return $users;
    }


    public function getSalutations()
    {
        $cacheName = app()->getLocale() . '_user_salutations';

        $salutations = cache()->remember($cacheName, 60*60*24*365, function(){
            // Option
            $option = Option::where('code', 'salutation')->with('option_values.translation')->first();

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