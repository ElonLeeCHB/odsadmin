<?php

namespace App\Domains\Api\Services\Member;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Domains\Api\Services\Service;
use App\Traits\Model\EloquentTrait;
use App\Libraries\TranslationLibrary;

class MemberService extends Service
{
    use EloquentTrait;

    public $modelName;
    public $model;
    public $table;
    public $lang;

	public function __construct()
	{
        $this->modelName = "\App\Models\Member\Member";
        $this->lang = (new TranslationLibrary())->getTranslations(['admin/common/common','admin/member/member']);
	}

	public function getMembers($data=[], $debug = 0)
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

        $records = $this->getModelCollection($data);

        return $records;
	}


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


    public function validator(array $data)
    {
        return Validator::make($data, [
                //code' =>'nullable|unique:users,code,'.$data['member_id'],
                //'username' =>'nullable|unique:users,username,'.$data['member_id'],
                //'email' =>'nullable|required|unique:users,email,'.$data['member_id'],
                // 'mobile' =>'nullable|min:9|max:15|unique:users,mobile,'.$data['member_id'],
                'name' =>'nullable|min:2|max:20',
                // 'first_name' =>'min:2|max:10',
                // 'short_name' =>'nullable|max:10',
                // 'job_title' =>'nullable|max:20',
                // 'password' =>'nullable|min:6|max:20',
            ],[
                //'code.unique' => $this->lang->error_code_exists,
                // 'username.required' => $this->lang->error_username,
                // 'username.unique' => $this->lang->error_username_exists,
                //'email.required' => $this->lang->error_email,
                //'email.unique' => $this->lang->error_email_exists,
                'name.*' => $this->lang->error_name,
                // 'mobile.required' => $this->lang->error_mobile,
                // 'mobile.min' => $this->lang->error_mobile,
                // 'mobile.max' => $this->lang->error_mobile,
                // 'mobile.unique' => $this->lang->error_mobile_exists,
                // 'first_name.*' => $this->lang->error_first_name,
                // 'short_name.*' => $this->lang->error_short_name,
                // 'job_title.*' => $this->lang->error_job_title,
                // 'password.*' => $this->lang->error_password,
        ]);
    }

}
