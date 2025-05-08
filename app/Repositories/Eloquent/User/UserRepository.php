<?php

namespace App\Repositories\Eloquent\User;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Models\User\User;
use App\Models\User\UserMeta;
use App\Models\User\UserAddress;
use App\Models\Catalog\Option;
use App\Repositories\Eloquent\Common\TermRepository;

class UserRepository extends Repository
{

    public $modelName = "\App\Models\User\User";

    public function __construct(private TermRepository $TermRepository)
    {
        parent::__construct();
        $this->TermRepository = $TermRepository;
    }

    public function getAdminUserIds()
    {
        return UserMeta::where('meta_key', 'is_admin')->where('meta_value', 1)->pluck('user_id');
    }
    
    
    public function getAdminUsers($query_data,$debug=0)
    {
        $query_data['whereHas']['userMeta'] = ['meta_key' => 'is_admin', 'meta_value' => 1];

        $users = $this->getRows($query_data);

        return $users;
    }


    public function getUsers($data = [], $debug = 0)
    {
        $data = $this->resetQueryData($data);

        if(!empty($data['filter_keyword'])){
            $data['filter_name'] = $data['filter_keyword'];
            unset($data['filter_keyword']);
        }

        $users = $this->getRows($data, $debug);

        return $users;
    }


    public function getSalutations()
    {
        // $cacheName = app()->getLocale() . '_user_salutations';

        // $salutations = cache()->remember($cacheName, 60*60*24*365, function(){
        //     // Option
        //     $option = Option::where('code', 'salutation')->with('option_values.translation')->first();

        //     // Option Values
        //     $option_values = $option->option_values;

        //     foreach($option_values as $option_value){
        //         $key = $option_value->id;
        //         $result[$key] = $option_value;
        //     }

        //     return $result;
        // });

        // if(empty($salutations)){
        //     $salutations = [];
        // }

        // foreach ($salutations as $key => $salutation) {
        //     $salutation = $salutation->toArray();
        //     unset($salutation['translation']);
        //     $salutations[$key] = (object) $salutation;
        // }
        $salutations = $this->TermRepository::getCodeKeyedTermsByTaxonomyCode('salutation', toArray:'false');
        
        return $salutations;
    }


    public function destroy($ids, $debug = 0)
    {
        try {
            DB::beginTransaction();
    
            $rows = User::whereIn('id', $ids)->get();

            foreach ($rows as $row) {
                $row->metas()->delete();
                $row->addresses()->delete();
                $row->delete();
            }
            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
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
                // 'filter_shipping_phone' => $data['filter_phone'],
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
}