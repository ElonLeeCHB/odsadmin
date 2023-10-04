<?php

namespace App\Services\Member;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Repositories\Eloquent\Member\MemberRepository;
use App\Models\User\UserAddress;

class MemberService extends Service
{
    protected $modelName = "\App\Models\Member\Member";

    public function __construct(protected MemberRepository $MemberRepository)
    {}

    public function getSalutations()
    {
        return $this->MemberRepository->getSalutations();
    }

    /**
     * return Eloquent model
     */
	public function getMembers($data=[], $debug = 0)
	{
        return $this->MemberRepository->getUsers($data, $debug);
	}


    public function updateOrCreate($data)
    {
        DB::beginTransaction();

        try {
            extract($data); //$data['some_id'] => $some_id;

            $member_id = $data['member_id'] ?? null;

            $member = $this->findIdOrFailOrNew($member_id);
            $member->name = $data['name'];
            $member->salutation_id = $data['salutation_id'] ?? null;
            $member->email = $data['email'] ?? null;
            $member->telephone_prefix = $data['telephone_prefix'] ?? null;
            $member->telephone = str_replace('-','',$data['telephone']) ?? null;
            $member->mobile = str_replace('-','',$data['mobile']) ?? null;
    
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

            $shipping_address1 = '';
            if(!empty($shipping_lane)){
                $shipping_address1 .= $shipping_lane.$this->lang->text_address_lane;
            }
            if(!empty($shipping_alley)){
                $shipping_address1 .= $shipping_alley.$this->lang->text_address_alley;
            }
            if(!empty($shipping_no)){
                $shipping_address1 .= $shipping_no.$this->lang->text_address_no;
            }
            if(!empty($shipping_floor)){
                $shipping_address1 .= $shipping_floor.$this->lang->text_address_floor;
            }
            if(!empty($shipping_room)){
                $shipping_address1 .= $shipping_room.$this->lang->text_address_room;
            }
            $member->shipping_address1 = $data['shipping_address1'] ?? null;

            $member->shipping_address2 = $data['shipping_address2'] ?? null;
            $member->shipping_road_abbr = $data['shipping_road_abbr'] ?? null;
            $member->comment = $data['comment'] ?? null;
            
            $member->save();

            if (isset($data['addresses'])) {
                UserAddress::where('user_id', $member->id)->delete();
    
                foreach ($data['addresses'] as $key => $address){
                    if(empty($address['is_enabled'])){
                        continue;
                    }
    
                    $address['user_id'] = $member->id;
                    
                    if(isset($data['address_default']) && $data['address_default'] == $key){
                        $address['is_default'] = 1;
                    }
                    
                    UserAddress::create($address);
                }
            }

            DB::commit();

            $result['member_id'] = $member->id;
    
            return $result;

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' =>$ex->getMessage()];
        }
    }


    public function deleteMember($member_id)
    {
        try {

            DB::beginTransaction();

            $this->UserRepository->delete($member_id);

            DB::commit();

            $result['success'] = true;

            return $result;

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }


    public function getJsonMembers($data)
    {
        $json = $this->MemberRepository->getJsonList($data);

        return $json;

    }


    public function optimizeRow($row)
    {
        return $this->MemberRepository->optimizeRow($row);
    }


    public function sanitizeRow($row)
    {
        return $this->MemberRepository->sanitizeRow($row);
    }

}