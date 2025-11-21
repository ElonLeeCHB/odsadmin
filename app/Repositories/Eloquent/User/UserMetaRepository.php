<?php

namespace App\Repositories\Eloquent\User;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Models\User\User;
use App\Models\User\UserMeta;

class UserMetaRepository extends Repository
{
    public $modelName = "\App\Models\User\UserMeta";


    // DEPRECATED: 已廢棄，is_admin 改由帳號中心的 user_systems 管理
    // public function removeAdmin($user_id)
    // {
    //     try {
    //
    //         DB::beginTransaction();
    //
    //         UserMeta::where('user_id', $user_id)->where('meta_key', 'is_admin')->delete();
    //
    //         DB::commit();
    //
    //     } catch (\Exception $ex) {
    //         DB::rollback();
    //         return ['error' => $ex->getMessage()];
    //     }
    // }
}