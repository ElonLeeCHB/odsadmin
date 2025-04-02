<?php

namespace App\Repositories\Eloquent\Member;

use Illuminate\Support\Facades\DB;
use App\Models\Member\Member;
use App\Models\User\User;
use App\Repositories\Eloquent\User\UserRepository;
use App\Http\Resources\MemberBasicListCollection;

class MemberRepository extends UserRepository
{
    public $modelName = "\App\Models\Member\Member";

    public function getMembers($filter_data = [], $debug = 0)
    {
        foreach ($filter_data as $key => $value) {
            if (substr($key, -8) === '_is_admin') {
                unset($filter_data[$key]);
            }
        }

        return parent::getUsers($filter_data, $debug);
    }

    public function saveMember($input)
    {
        try {
            DB::beginTransaction();
    
            $member_id = $input['member_id'] ?? null;

            $result = $this->findIdOrFailOrNew($member_id);

            if(!empty($result['data'])){
                $member = $result['data'];
            }else if($result['error']){
                throw new \Exception($result['error']);
            }
            unset($result);
            
            $member->name = $input['name'];
            $member->salutation_id = $input['salutation_id'] ?? null;
            $member->salutation_code = $input['salutation_code'] ?? null;
            $member->email = $input['email'] ?? null;
            $member->telephone_prefix = $input['telephone_prefix'] ?? null;
            $member->telephone = str_replace('-','',$input['telephone']) ?? null;
            $member->mobile = str_replace('-','',$input['mobile']) ?? null;
    
            if(!empty($input['password'])){
                $member->password = bcrypt($input['password']);
            }

            //訂購資料
            $member->payment_company = $input['payment_company'] ?? '';
            $member->payment_department = $input['payment_department'] ?? '';
            $member->payment_tin = $input['payment_tin'] ?? '';
            //收件資料
            $member->shipping_personal_name = $input['shipping_personal_name'] ?? $input['name'];
            $member->shipping_company = $input['shipping_company'] ?? $member->payment_company;
            $member->shipping_phone = $input['shipping_phone'] ?? '';
            $member->shipping_state_id = $input['shipping_state_id'] ?? 0;
            $member->shipping_city_id = $input['shipping_city_id'] ?? 0;
            $member->shipping_road = $input['shipping_road'] ?? '';
            $member->shipping_salutation_id = $input['shipping_salutation_id'] ?? 0;
            $member->shipping_salutation_id2 = $input['shipping_salutation_id2'] ?? 0;
            $member->shipping_personal_name2 = $input['shipping_personal_name2'] ?? '';
            $member->shipping_phone2 = $input['shipping_phone2'] ??  '';
            $member->shipping_address1 = $input['shipping_address1'] ??  '';
            $member->shipping_address2 = $input['shipping_address2'] ??  '';
            $member->shipping_road_abbr = $input['shipping_road_abbr'] ??  '';

            $member->comment = $input['comment'] ?? null;
            $member->save();
            
            $result = $this->saveRowMetaData($member, $input);

            DB::commit();
    
            return ['id' => $member->id];

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' =>$ex->getMessage()];
        }
    }


    // 使用 UserRepository 的 destroy()
    // public function destroy($ids)
    // {}

    
    
    public function deleteMember($member_id)
    {
        try {

            DB::beginTransaction();

            parent::delete($member_id);

            DB::commit();

            $result['success'] = true;

            return $result;

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


    // public function getMembersBasicListArray($input, $debug = 0)
    // {
    //     $params = $this->resetQueryData($input);
    //     $tmprows = $this->getRows($params, $debug);
    //     $collection = new MemberBasicListCollection($tmprows);

    //     return $collection->toArray(request());
    // }

    public function getJsonList($data = [], $debug = 0)
    {
        $data = $this->resetQueryData($data);

        if(!isset($data['pagination'])){
            $data['pagination'] = false;
        }
        
        $members = $this->getRows($data, $debug);
        $rows = [];

        foreach ($members as $row) {

            $rows[] = array(
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
                'has_order' => ($row->orders()->count() > 0) ? 1 : 0,
            );
        }

        $keys = array_keys($rows[0]);

        foreach ($keys as $key) {
            $empty_arr[$key] = '';
            $empty_arr['label'] = ' -- ';
        }
        
        array_unshift($rows,$empty_arr);

        return $rows;
    }
}

