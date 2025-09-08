<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Member;

use Illuminate\Http\Request;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use App\Helpers\Classes\OrmHelper;
use App\Models\Sale\UserCoupon;

class UserCouponController extends ApiPosController
{
    public function __construct(private Request $request)
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }

        $this->middleware(function ($request, $next) {
            $this->setLang(['admin/common/common']);
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $filters = $this->url_data;

        $json = [];

        // if (empty($filters['equal_user_id'])) {
        //     $json['errors']['user_id'] = 'user_id 必填';
        // }

        if (isset($json['errors']) && !isset($json['errors']['warning'])) {
            $json['errors']['warning'] = $this->lang->error_warning;

            $json['errors']['warning'] = $this->lang->error_warning;
        }

        // 返回錯誤
        if (!empty($json)) {
            return response()->json($json, 422);
        }

        // 獲取資料集
        $query = UserCoupon::query()->with('coupon');

        if ($request->filled('equal_user_id')) {
            $query->where('user_id', $request->input('equal_user_id'));
        }

        $userCoupons = OrmHelper::getResult($query, $filters);

        if ($userCoupons->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => '查無結果'
            ]);
        }

        $select_coupon_columns = ['name'];

        $userCoupons->getCollection()->transform(function ($item) use ($select_coupon_columns) {
            $item->subtotal = 0;
            if ($item->coupon->discount_type == 'fixed') {
                $item->subtotal = $item->quantity * $item->coupon->discount_value;
            }

            // 只保留特定欄位
            $item->coupon->makeHidden(array_diff(
                array_keys($item->coupon->getAttributes()),
                $select_coupon_columns
            ));

            return $item;
        });

        return response()->json(['success' => true, 'data' => $userCoupons]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $this->validated($request);

            $userCoupon = UserCoupon::create([
                'user_id'       => $validated['user_id'],
                'coupon_id'     => $validated['coupon_id'],
                'name'          => $validated['name'],
                'action'        => $validated['action'],
                'valid_from'    => $validated['valid_from'] ?? null,
                'valid_to'      => $validated['valid_to'] ?? null,
                'quantity'      => $validated['quantity'] ?? null,
                'discount_amount'=> $validated['discount_amount'] ?? 0,
                'issued_by_name'=> $validated['issued_by_name'] ?? null,
            ]);

            return response()->json(['success' => true, 'message' => '新增成功', 'data' => $userCoupon]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors(), 'message' => '驗證失敗'], 422);
        } catch (\Throwable $th) {
            return $this->sendJsonErrorResponse(data: ['sys_error' => $th->getMessage()], status_code: 500);
        }
    }

    public function storeMany(Request $request)
    {
        try {
            $validated = $request->validate([
                'issued_by_name' => 'nullable|string',
                'user_id' => 'required|integer|exists:users,id',
                'coupons' => 'required|array|min:1', // 必須是陣列，至少一個元素
                'coupons.*.coupon_id' => 'required|integer|exists:coupons,id',
                'coupons.*.name' => 'required|string',
                'coupons.*.quantity' => 'required|integer|min:1',
                'coupons.*.discount_amount' => 'required|integer|min:1',
                'coupons.*.valid_from' => 'nullable|date',
                'coupons.*.valid_to' => 'nullable|date|after_or_equal:coupons.*.valid_from',
            ], [
                'issued_by_name.string' => '發放人名稱必須是字串',

                'user_id.required' => '使用者 ID 為必填',
                'user_id.integer' => '使用者 ID 必須是整數',
                'user_id.exists' => '指定的使用者不存在',

                'coupons.required' => '優惠券資料為必填',
                'coupons.array' => '優惠券資料格式錯誤',

                'coupons.*.coupon_id.required' => '優惠券 ID 必填',
                'coupons.*.coupon_id.integer' => '優惠券 ID 必須是整數',
                'coupons.*.coupon_id.exists' => '指定的優惠券不存在',

                'coupons.*.name.required' => '優惠券名稱必填',
                'coupons.*.name.string' => '優惠券名稱必須是字串',

                'coupons.*.quantity.required' => '數量必填',
                'coupons.*.quantity.integer' => '數量必須是整數',
                'coupons.*.quantity.min' => '數量不能小於 1',

                'coupons.*.discount_amount.required' => '優惠金額必填',
                'coupons.*.discount_amount.integer' => '優惠金額必須是整數',
                'coupons.*.discount_amount.min' => '優惠金額不能小於 1',

                'coupons.*.valid_from.date' => '有效起始日期格式錯誤',
                'coupons.*.valid_to.date' => '有效截止日期格式錯誤',
                'coupons.*.valid_to.after_or_equal' => '有效截止日期必須大於等於有效起始日期',
            ]);

            $user_id = $validated['user_id'];

            $create_data = [];

            foreach ($validated['coupons'] as $coupon) {
                $userCoupon = UserCoupon::create([
                    'issued_by_name'   => $validated['issued_by_name'] ?? null,
                    'user_id' => $user_id,
                    'coupon_id' => $coupon['coupon_id'],
                    'quantity'   => $coupon['quantity'],
                    'discount_amount'   => $coupon['discount_amount'],
                    'valid_from' => $coupon['valid_from'],
                    'valid_to' => $coupon['valid_to'],
                    'action' => 'plus',
                ]);
                $create_data[] = $userCoupon;
            }

            return response()->json(['success' => true,'message' => '批量新增成功', 'data' => $create_data,]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors(), 'message' => '驗證失敗'], 422);
        } catch (\Throwable $th) {
            return $this->sendJsonErrorResponse(data: ['sys_error' => $th->getMessage()], status_code: 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            // 驗證資料
            $validated = $this->validated($request);

            // 找出資料
            $userCoupon = UserCoupon::find($id);

            // 資料不存在
            if (!$userCoupon) {
                return response()->json(['success' => false, 'message' => '資料不存在'], 404);
            }

            // 檢查 user_id 是否相符
            if ($userCoupon->user_id !== $validated['user_id']) {
                return response()->json(['success' => false, 'message' => '驗證失敗', 'errors' => ['user_id' => '禁止修改其他使用者的資料']], 403);
            }

            // 檢查是否已超過一天
            if ($userCoupon->created_at->diffInHours(now()) > 24) {
                return response()->json(['success' => false, 'message' => '已超過可修改時間，禁止修改'], 403);
            }

            // 更新資料
            $userCoupon->user_id = $validated['user_id'];
            $userCoupon->coupon_id = $validated['coupon_id'];
            $userCoupon->name = $validated['name'];
            $userCoupon->quantity = $validated['quantity'];
            $userCoupon->discount_amount = $validated['discount_amount'];
            $userCoupon->action = $validated['action'];
            $userCoupon->valid_from = $validated['valid_from'] ?? null;
            $userCoupon->valid_to = $validated['valid_to'] ?? null;
            $userCoupon->save();

            return response()->json(['success' => true, 'message' => '更新成功']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false,'errors' => $e->errors(),'message' => '驗證失敗'], 422);
        } catch (\Throwable $th) {
            return $this->sendJsonErrorResponse(data: ['sys_error' => $th->getMessage()], status_code: 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $validated =  $request->validate([
                'user_id' => 'required|integer|exists:users,id',
            ], [
                'user_id.required' => '使用者 ID 為必填',
                'user_id.integer' => '使用者 ID 必須是整數',
                'user_id.exists' => '指定的使用者不存在',
            ]);

            $userCoupon = UserCoupon::find($id);

            if (!$userCoupon) {
                return response()->json(['success' => false, 'message' => '資料不存在'], 404);
            }

            if ($userCoupon->user_id !== $validated['user_id']) {
                return response()->json(['success' => false, 'message' => '驗證失敗', 'errors' => ['user_id' => '禁止修改其他使用者的資料']], 403);
            }

            if ($userCoupon->created_at->diffInHours(now()) > 24) {
                return response()->json(['success' => false, 'message' => '已超過可修改時間，禁止修改'], 403);
            }

            $userCoupon->delete();

            return response()->json(['success' => true, 'message' => '刪除成功']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors(), 'message' => '驗證失敗'], 422);
        } catch (\Throwable $th) {
            return $this->sendJsonErrorResponse(data: ['sys_error' => $th->getMessage()], status_code: 500);
        }
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'coupon_id' => 'required|integer|exists:coupons,id',
            'name' => 'required|string|exists:coupons,name',
            'quantity' => 'required|integer|min:1',
            'discount_amount' => 'discount_amount|integer',
            'action' => 'required|string|in:plus,minus',
        ], [
            
            'user_id.required' => '使用者 ID 為必填',
            'user_id.integer' => '使用者 ID 必須是整數',
            'user_id.exists' => '指定的使用者不存在',

            'coupon_id.required' => '優惠券 ID 為必填',
            'coupon_id.integer' => '優惠券 ID 必須是整數',
            'coupon_id.exists' => '指定的優惠券不存在',

            'name.required' => '優惠券名稱必填',
            'name.string' => '優惠券名稱必須是字串',
            'name.exists' => '指定的優惠券不存在',

            'quantity.required' => '數量為必填',
            'quantity.integer' => '數量必須是整數',
            'quantity.min' => '數量不能小於 1',

            'action.required' => '動作為必填',
            'action.string' => '動作必須為字串',
            'action.in' => '動作必須是 plus 或 minus',
        ]);
    }
}
