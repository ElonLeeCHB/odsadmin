<?php

namespace App\Domains\Admin\Services\Setting\User;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Services\Service;
use App\Repositories\Eloquent\User\UserRepository;
use App\Repositories\Eloquent\User\UserMetaRepository;

class UserService extends Service
{
    protected $modelName = "\App\Models\User\User";


	public function __construct(private UserRepository $UserRepository, private UserMetaRepository $UserMetaRepository)
    {}


    public function getAdminUsers($data, $debug=0)
    {
        return $this->UserRepository->getAdminUsers($data, $debug);
    }


    public function getSalutations()
    {
        return $this->UserRepository->getSalutations();
    }


    public function updateOrCreate($data)
    {
        DB::beginTransaction();

        try {
            $result = $this->findIdOrFailOrNew(id:$data['user_id']);

            if(empty($result['error']) && !empty($result['data'])){
                $user = $result['data'];
            }else{
                return response(json_encode($result))->header('Content-Type','application/json');
            }

            $user->username = $data['username'];
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

            //$user->is_admin = $data['is_admin'] ?? 0;
            
            if($user->isDirty()){
                $user->save();
            }

            $this->saveRowMetaData($user, $data);

            //is_admin
            // $upsertData[] = [
            //     'user_id' => $user->id,
            //     'meta_key' => 'is_admin',
            //     'meta_value' => 1,
            // ];

            // if(!empty($upsertData)){
            //     $this->UserMetaRepository->newModel()->upsert($upsertData, ['user_id','meta_key']);
            // }

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


    public function removeAdmin($user_id)
    {
        $this->UserMetaRepository->removeAdmin($user_id);
    }

}