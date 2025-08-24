<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use Illuminate\Support\Facades\Validator;
use App\Models\Sale\Coupon;
use App\Domains\ApiPosV2\Services\Sale\DriverService;
use App\Repositories\Eloquent\Sale\CouponRepository;
use App\Helpers\Classes\OrmHelper;

class CouponController extends ApiPosController
{
    public function __construct(private Request $request)
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }
    }

    /**
     * 顯示優惠券類型列表
     */
    public function index(Request $request)
    {
        try {
            $filters  = $this->url_data;

            // Rows
            $query = Coupon::query();
            $filters = $request->all();

            $filters['pagination'] = true;

            if (isset($filters['limit']) && $filters['limit'] == 0){
                $filters['pagination'] = false;
            }

            OrmHelper::prepare($query, $filters);

            $coupons = OrmHelper::getResult($query, $filters);

            foreach ($coupons as $coupon) {
                $coupon->discount_type_label = $coupon->discount_type_label;
            }

            return response()->json(['success' => true, 'data' => $coupons], 200, [], JSON_UNESCAPED_UNICODE); // JSON_UNESCAPED_UNICODE 使用原本的字串，不要轉成 unicode

        } catch (\Throwable $th) {
            throw $th;
        }
        // $data = [];

        // // $couponTypes = CouponType::orderBy('id', 'desc')->paginate(20);
        // $data['lang'] = $this->lang;



        // $data['list_url'] = route('lang.admin.sale.coupon_types.list');
        // $data['add_url'] = route('lang.admin.sale.coupon_types.form');
        // $data['delete_url'] = route('lang.admin.sale.coupon_types.destroy');

        // return view('admin.sale.coupon_type', $data);
    }
}