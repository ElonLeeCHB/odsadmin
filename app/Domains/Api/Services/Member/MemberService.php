<?php

namespace App\Domains\Api\Services\Member;

use Illuminate\Support\Facades\DB;
use App\Services\Member\MemberService as GlobalMemberService;

class MemberService extends GlobalMemberService
{

    public $modelName = "\App\Models\Member\Member";


    public function updateOrCreate($data)
    {
        DB::beginTransaction();

        try {
            $telephone = '';
            if(!empty($data['telephone'])){
                $telephone = str_replace('-','',$data['telephone']);
            }

            $mobile = '';
            if(!empty($data['mobile'])){
                $mobile = str_replace('-','',$data['mobile']);
            }

            $member_id = $data['member_id'] ?? null;

            $member = $this->findIdOrFailOrNew($member_id);
            $member->name = $data['name'];
            $member->salutation_id = $data['salutation_id'] ?? null;
            $member->email = $data['email'] ?? null;
            $member->telephone_prefix = $data['telephone_prefix'] ?? '';
            $member->telephone = $telephone ?? '';
            $member->mobile = $mobile ?? '';

            if(!empty($data['password'])){
                $member->password = bcrypt($data['password']);
            }

            //訂購資料
            $payment_company = $data['payment_company'] ?? ''; //will be used later

            $member->payment_company = $payment_company;
            $member->payment_department = $data['payment_department'] ?? '';
            $member->payment_tin = $data['payment_tin'] ?? '';

            //收件資料
            $member->shipping_personal_name = $data['shipping_personal_name'] ?? $data['name'];
            $member->shipping_company = $data['shipping_company'] ?? $payment_company;
            $member->shipping_phone = $data['shipping_phone'] ?? '';
            $member->shipping_state_id = $data['shipping_state_id'] ?? 0;
            $member->shipping_city_id = $data['shipping_city_id'] ?? 0;
            $member->shipping_road = $data['shipping_road'] ?? '';            
            $member->shipping_address1 = $data['shipping_address1'] ?? null;
            $member->shipping_address2 = $data['shipping_address2'] ?? null;
            $member->shipping_road_abbr = $data['shipping_road_abbr'] ?? null;
            $member->comment = $data['comment'] ?? null;

            $member->save();

            DB::commit();

            $result['data']['member_id'] = $member->id;

            return $result;

        } catch (\Exception $ex) {
            DB::rollback();
            $result['error'] = $ex->getMessage();
            return $result;
        }
    }
}
