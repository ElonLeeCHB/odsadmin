<?php

namespace App\Repositories\Eloquent\User;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Models\User\User;
use App\Models\User\UserMeta;
use App\Models\User\UserAddress;
use App\Models\Catalog\Option;

class UserRepository extends Repository
{

    public $modelName = "\App\Models\User\User";
    
    
    public function getAdminUsers($query_data,$debug=0)
    {
        $query_data['whereHas']['userMeta'] = ['meta_key' => 'is_admin', 'meta_value' => 1];

        $users = $this->getRows($query_data);

        return $users;
    }


    public function getUsers($data = [], $debug = 0)
    {
        $data = $this->resetQueryData($data);

        $users = $this->getRows($data, $debug);

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

        foreach ($salutations as $key => $salutation) {
            $salutation = $salutation->toArray();
            unset($salutation['translation']);
            $salutations[$key] = (object) $salutation;
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


    private function resetQueryData($data)
    {
        if(!empty($data['filter_phone'])){
            $data['filter_phone'] = str_replace('-','',$data['filter_phone']);
            $data['filter_phone'] = str_replace(' ','',$data['filter_phone']);

            $data['andOrWhere'][] = [
                'filter_mobile' => $data['filter_phone'],
                'filter_telephone' => $data['filter_phone'],
                'filter_shipping_phone' => $data['filter_phone'],
            ];
            unset($data['filter_phone']);
        }

        if(!empty($data['filter_name'])){
            $data['andOrWhere'][] = [
                'filter_name' => $data['filter_name'],
                'filter_shipping_personal_name' => $data['filter_name'],
            ];
            unset($data['filter_name']);
        }

        if(!empty($data['filter_company'])){
            $data['andOrWhere'][] = [
                'payment_company' => $data['filter_company'],
                'shipping_company' => $data['filter_company'],
            ];
            unset($data['filter_company']);
        }

        return $data;
    }


    public function getJsonList($data = [], $debug = 0)
    {
        $data = $this->resetQueryData($data);

        if(!isset($data['pagination'])){
            $data['pagination'] = false;
        }
        
        $users = $this->getRows($data, $debug);
        $json = [];

        foreach ($users as $row) {

            $json[] = array(
                'label' => $row->personal_name . '_' . $row->mobile,
                'value' => $row->id,
                'customer_id' => $row->id,
                'personal_name' => $row->name,
                'salutation_id' => $row->salutation_id,
                'telephone' => $row->telephone,
                'mobile' => $row->mobile,
                'email' => $row->email,
                'payment_company' => $row->payment_company,
                'payment_department' => $row->payment_department,
                'payment_tin' => $row->payment_tin,
                'shipping_personal_name' => $row->shipping_personal_name,
                'shipping_phone' => $row->shipping_phone,
                'shipping_company' => $row->shipping_company,
                'shipping_state_id' => $row->shipping_state_id,
                'shipping_city_id' => $row->shipping_city_id,
                'shipping_road_id' => $row->shipping_road_id,
                'shipping_road' => $row->shipping_road,
                'shipping_address1' => $row->shipping_address1,
                'shipping_lane' => $row->shipping_lane,
                'shipping_alley' => $row->shipping_alley,
                'shipping_no' => $row->shipping_no,
                'shipping_floor' => $row->shipping_floor,
                'shipping_room' => $row->shipping_room,
                'has_order' => ($row->orders) ? 1 : 0,
            );

        }

        
        array_unshift($json,[
            'value' => '',
            'label' => ' -- ',
            'customer_id' => '',
            'personal_name' => '',
            'salutation_id' => '',
            'telephone' => '',
            'mobile' => '',
            'email' => '',
            'payment_company' => '',
            'payment_department' => '',
            'payment_tin' => '',
            'shipping_personal_name' => '',
            'shipping_phone' => '',
            'shipping_company' => '',
            'shipping_state_id' => '',
            'shipping_city_id' => '',
            'shipping_road_id' => '',
            'shipping_road' => '',
            'shipping_address1' => '',
            'shipping_lane' => '',
            'shipping_alley' => '',
            'shipping_no' => '',
            'shipping_floor' => '',
            'shipping_room' => '',
        ]);

        return $json;
    }

}