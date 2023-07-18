<?php

namespace App\Repositories\Eloquent\User;

use Illuminate\Support\Facades\DB;
use App\Traits\EloquentTrait;
use App\Repositories\Eloquent\Repository;
use App\Models\User\User;
use App\Models\User\UserMeta;
use App\Models\User\UserAddress;
use App\Models\Common\Option;

class UserRepository extends Repository
{
    use EloquentTrait;

    public $modelName = "\App\Models\User\User";
    
    
    public function getAdminUsers($query_data,$debug=0)
    {
        $query_data['whereHas']['userMeta'] = ['meta_key' => 'is_admin', 'meta_value' => 1];

        $users = $this->getRows($query_data);

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


    public function delete($user_id)
    {
        try {

            DB::beginTransaction();

            UserAddress::where('user_id', $user_id)->delete();
            UserMeta::where('user_id', $user_id)->delete();
            User::where('id', $user_id)->delete();

            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }

}