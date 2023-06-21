<?php

namespace App\Domains\Admin\Services\Admin;

use App\Domains\Admin\Services\Service;
use Illuminate\Support\Facades\DB;
use Validator;

class PermissionService extends Service
{
    private $lang;

	public function __construct()
    {
        $this->modelName = "\App\Models\User\Permission";
	}

    public function getPermissions($data, $debug=0)
    {
        $permissions = $this->getRows($data, $debug);

        return $permissions;
    }

    public function save($data)
    {
        DB::beginTransaction();

        try {
            $user = $this->findOrFailOrNew(id:$data['user_id']);

            $user->username = $data['username'] ?? null;
            $user->name = $data['name'] ?? '';

            if(isset($data['code'])){
                $user->code = $data['code'];
            }

            if(isset($data['email'])){
                $user->email = $data['email'];
            }

            if(!empty($data['password'])){
                $user->password = Hash::make($data['password']);
            }
            
            $user->save();

            //is_admin
            $upsertData[] = [
                'user_id' => $user->id,
                'meta_key' => 'is_admin',
                'meta_value' => $data['is_admin'],
            ];

            if(!empty($upsertData)){
                UserMeta::upsert($upsertData, ['user_id','meta_key']);
            }		
		
            DB::commit();

            $result['data']['user_id'] = $user->id;

            return $result;

        } catch (\Exception $ex) {
            DB::rollback();
            $msg = $ex->getMessage();
            $json['error'] = $msg;
            return $json;
        }
    }


    public function validator(array $data)
    {
        return Validator::make($data, [
                'email' => 'nullable|email',
                'password' => 'nullable|confirmed|min:6',
            ],[
                'password.confirmed' => '密碼不符合',
                'password.min' => '至少6位數',
                'email.*' => 'email錯誤',
        ]);
    }

}