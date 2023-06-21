<?php

namespace App\Domains\Admin\Services\Setting\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Domains\Admin\Services\Service;
use App\Repositories\Eloquent\User\UserRepository;
use App\Repositories\Eloquent\User\UserMetaRepository;

class UserService extends Service
{
    private $lang;
    private $modelName;
    private $UserMetaRepository;

	public function __construct(public UserRepository $repository, UserMetaRepository $UserMetaRepository)
    {
        $this->modelName = "\App\Models\User\User";
        $this->repository = $repository;
        $this->UserMetaRepository = $UserMetaRepository;
	}

    public function getAdminUsers($data, $debug=0)
    {
        return $this->repository->getAdminUsers($data, $debug);
    }

    public function getSalutations()
    {
        return $this->repository->getSalutations();
    }

    public function updateOrCreate($data)
    {
        DB::beginTransaction();

        try {
            $user = $this->findIdOrFailOrNew(id:$data['user_id']);

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
                $this->UserMetaRepository->newModel()->upsert($upsertData, ['user_id','meta_key']);
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