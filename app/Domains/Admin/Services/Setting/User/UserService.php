<?php

namespace App\Domains\Admin\Services\Setting\User;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Services\Service;
use App\Repositories\Eloquent\User\UserRepository;
use App\Repositories\Eloquent\User\UserMetaRepository;
use App\Models\User\User;
use App\Models\User\UserMeta;

class UserService extends Service
{
    protected $modelName = "\App\Models\User\User";


	public function __construct(private UserRepository $UserRepository, private UserMetaRepository $UserMetaRepository)
    {
        $this->repository = $UserRepository;
    }


    public function getAdminUsers($data, $debug=0)
    {
        return $this->UserRepository->getAdminUsers($data, $debug);
    }


    public function getSalutations()
    {
        return $this->getCodeKeyedTermsByTaxonomyCode('salutation', toArray:'false');
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

            if (isset($data['employee_code'])) {
                $user->employee_code = $data['employee_code'];
            }

            if(isset($data['email'])){
                $user->email = $data['email'];
            }

            if(isset($data['is_admin'])){
                $user->is_admin = $data['is_admin'];
            }

            if(!empty($data['password'])){
                $user->password = Hash::make($data['password']);
            }
            
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


            // 如果是員工，同步此 user 到差勤系統的 users 資料表
            if (!empty($data['employee_code'])) {
                $this->syncUserToHrm($user, auth()->user());
            }

            $result['data']['user_id'] = $user->id;

            DB::commit();

            return $result;
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    protected function syncUserToHrm(User $targetUser, User $loginUser)
    {
        try {
            // 必須是 APP_ENV=production 並且 APP_DEBUG=false 才會同步到差勤系統
            if (env('APP_ENV') == 'production' && env('APP_DEBUG') == true) {
                $hrm_base_url = 'https://hrm.huabing.tw';
            }
            // 否則一律同步到測試環境
            else{
                $hrm_base_url = 'https://hrm.huabing.test';
            }

            if (!auth()->check()) {
                throw new \Exception('目前未登入，請重新登入');
            }

            $http = new \GuzzleHttp\Client();

            // 使用內部 API，用 username 登入
            $loginResponse = $http->post($hrm_base_url . '/api/v1/poslogin', [
                'verify' => false, // 忽略自發憑證的信任問題
                'json' => [
                    'username'      => $loginUser->username,
                    'system_secret' => env('HRM_SYSTEM_SECRET'),
                ],
            ]);
            $loginData = json_decode((string) $loginResponse->getBody(), true);
            $accessToken = $loginData['access_token'];

            // upsert 差勤用戶
            $http = new \GuzzleHttp\Client();

            $syncResponse = $http->post($hrm_base_url . '/api/v1/sync-user', [
                'verify' => false,
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept'        => 'application/json',
                ],
                'json' => [
                    'id'     => $targetUser->id,
                    'username'     => $targetUser->username,
                    'code'         => $targetUser->code,
                    'name'         => $targetUser->name,
                    'employee_code' => $targetUser->employee_code,
                    'email'        => $targetUser->email,
                    'password'     => $targetUser->password, // 這邊同步密碼
                    'note'  => 'sync from pos '. date('Y-m-d H:i:s'),
                    'system_secret' => env('HRM_SYSTEM_SECRET'),
                ],
            ]);
            $syncData = json_decode((string) $syncResponse->getBody(), true);

            if (!$syncData['success']) {
                throw new \Exception('差勤同步失敗: ' . $syncData['message']);
            }

            return true;
        } catch (\Throwable $th) {
            throw $th;
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