<?php

namespace App\Domains\ApiPosV2\Services\Member;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Helpers\Classes\OrmHelper;
use App\Repositories\Eloquent\User\UserRepository;

class MemberService
{
    public $modelName = "\App\Models\User\User";

    public function getMembers($params)
    {
        $query = (new UserRepository)->newModel()->query();

        OrmHelper::prepare($query, $params);

        return OrmHelper::getResult($query, $params);
    }

    public function getMemberById($member_id, $params)
    {
        $query = (new UserRepository)->newModel()->query();

        $query->with(['userCoupons']);

        if (!empty($member_id)){
            return $query->find($member_id);
        }

        if (!empty($params['equal_mobile'])) {
            $query->orderByDesc('id');

            unset($params['sort']);
            unset($params['order']);
        }        

        OrmHelper::prepare($query, $params);

        $params['first'] = true;
        
        $member = OrmHelper::getResult($query, $params);
        if ($member instanceof \App\Models\User\User) {
            $member->append('shipping_salutation_name');
            $member->append('shipping_salutation_name2');
            $member->append('shipping_state_name');
            $member->append('shipping_city_name');
        }

        return $member;
    }

    public function saveMember($request, $member_id = null)
    {
        try {
            DB::beginTransaction();

            $data = $request->all();

            // ✅ 手機必填
            if (empty($data['mobile'])) {
                throw ValidationException::withMessages(['mobile' => '手機號碼是必填欄位',]);
            }

            // ✅ 移除非數字
            $data['mobile'] = preg_replace('/\D/', '', $data['mobile']);

            // ✅ 手機格式檢查
            if (!preg_match('/^09\d{8}$/', $data['mobile'])) {
                throw ValidationException::withMessages(['mobile' => '手機號碼格式錯誤',]);
            }

            $userRepo = new UserRepository;

            if (empty($member_id)) {
                // ➕ 新增模式
                $user = null;
                $exists = $userRepo->newModel()->where('mobile', $data['mobile'])->exists();
            } else {
                // ✏️ 修改模式
                $user = $userRepo->newModel()->findOrFail($member_id);

                $exists = $userRepo->newModel()
                    ->where('mobile', $data['mobile'])
                    ->where('id', '!=', $member_id)
                    ->exists();
            }

            // ✅ 唯一性檢查
            if ($exists) {
                return response()->json(['message' => '手機號碼已被使用', 'errors' => ['mobile' => ['手機號碼已被使用']]], 409);
            }

            // ✅ 密碼修改檢查
            if ($user && $request->filled('password')) {
                if (!$request->filled('old_password') || !Hash::check($request->input('old_password'), $user->password)) {
                    throw ValidationException::withMessages(['old_password' => '舊密碼不正確']);
                }
                $data['password'] = Hash::make($request->input('password'));
            } elseif ($request->filled('password')) {
                // 新增模式
                $data['password'] = Hash::make($request->input('password'));
            }

            // ✅ 寫入資料庫
            if ($user) {
                $user->update($data);
            } else {
                $user = $userRepo->newModel()->create($data);
            }

            // ✅ 處理 user_coupons
            if (!empty($data['user_coupons']) && is_array($data['user_coupons'])) {
                // 取得傳入 coupon_id 列表
                $couponIds = array_column($data['user_coupons'], 'coupon_id');

                // 刪除資料庫裡會員原本有，但傳入沒有的 coupon
                $user->userCoupons()->whereNotIn('coupon_id', $couponIds)->delete();

                // 同步新增或更新
                foreach ($data['user_coupons'] as $coupon) {
                    $user->userCoupons()->updateOrCreate(
                        ['user_id'   => $user->id,'coupon_id' => $coupon['coupon_id']],
                        ['quantity' => $coupon['quantity']]
                    );
                }
            } else {
                // 如果傳入空陣列或沒有 user_coupons，刪除全部會員優惠券
                $user->userCoupons()->delete();
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => '資料已更新']);
        } catch (\Throwable $ex) {
            DB::rollBack();
            throw $ex;
        }
    }
}