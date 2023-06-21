<?php

namespace App\Domains\Admin\Services\Member;

use App\Domains\Admin\Services\Service;
use App\Libraries\TranslationLibrary;
use App\Repositories\Eloquent\User\UserRepository;
use App\Repositories\Eloquent\Member\MemberRepository;
use App\Repositories\Eloquent\Sale\OrderRepository;
use App\Repositories\Eloquent\Localization\DivisionRepository;
use App\Repositories\Eloquent\Localization\AddressRepository;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MemberService extends Service
{
    protected $modelName = "\App\Models\Member\Member";

    private $lang;

	public function __construct(protected MemberRepository $repository
    , private OrderRepository $OrderRepository
    , private DivisionRepository $DivisionRepository
    , private AddressRepository $AddressRepository)
	{
        
        $this->lang = (new TranslationLibrary())->getTranslations(['admin/member/member',]);
	}

    public function getSalutations()
    {
        $UserRepository = new UserRepository;
        return $UserRepository->getSalutations();
    }

    // Use only id to search
    public function findOrNew($data)
    {
        $member = $this->repository->findOrNew($data);

        if(!empty($member)){
            $member = $this->parseShippingAddress($member);
        }

        return $member;
    }

	public function getRows($data=[], $debug = 0)
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

        $members = $this->repository->getRows($data, $debug);

        if(!empty($members)){
            foreach ($members as $row) {
                $row->edit_url = route('lang.admin.member.members.form', array_merge([$row->id], $data));
            }
        }

        return $members;
	}


    public function updateOrCreate($data)
    {
        DB::beginTransaction();

        try {
            extract($data); //$data['some_id'] => $some_id;

            $member_id = $data['member_id'] ?? null;

            $member = $this->repository->findIdOrFailOrNew($member_id);
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
                $this->AddressRepository->newModel()->where('user_id', $member->id)->delete();
    
                foreach ($data['addresses'] as $key => $address){
                    if(empty($address['is_enabled'])){
                        continue;
                    }
    
                    $address['user_id'] = $member->id;
                    
                    if(isset($data['address_default']) && $data['address_default'] == $key){
                        $address['is_default'] = 1;
                    }
                    
                    $this->AddressRepository->create($address);
                }
            }

            DB::commit();

            $result['data']['member_id'] = $member->id;
    
            return $result;

        } catch (\Exception $ex) {
            DB::rollback();
            $result['error'] = $ex->getMessage();
            return $result;
        }
    }


    public function parseShippingAddress($row)
    {
        $names =[
            'shipping_lane' => $this->lang->text_address_lane,
            'shipping_alley' => $this->lang->text_address_alley,
            'shipping_no' => $this->lang->text_address_no,
            'shipping_floor' => $this->lang->text_address_floor,
            'shipping_room' => $this->lang->text_address_room,
        ];

        foreach ($names as $key => $val) {
            $pattern = '/(?<'.$key.'>\d+)'.$val.'/';
            preg_match($pattern, $row->shipping_address1,$matches);
            if(!empty($matches[$key])){
                $row->$key = $matches[$key];
            }
        }

        if(!empty($row->shipping_city_id)){
            $city = $this->DivisionRepository->newModel()->find($row->shipping_city_id);
            if(!empty($city)){
                $row->shipping_city_name = $city->name;
            }
        }

        return $row;
    }


    public function validator(array $data)
    {
        return Validator::make($data, [
                //code' =>'nullable|unique:users,code,'.$data['member_id'],
                //'username' =>'nullable|unique:users,username,'.$data['member_id'],
                //'email' =>'nullable|required|unique:users,email,'.$data['member_id'],
                //'mobile' =>'nullable|min:9|max:15|unique:users,mobile,'.$data['member_id'],
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
                 //'mobile.unique' => $this->lang->error_mobile_exists,
                // 'first_name.*' => $this->lang->error_first_name,
                // 'short_name.*' => $this->lang->error_short_name,
                // 'job_title.*' => $this->lang->error_job_title,
                // 'password.*' => $this->lang->error_password,
        ]);
    }

}