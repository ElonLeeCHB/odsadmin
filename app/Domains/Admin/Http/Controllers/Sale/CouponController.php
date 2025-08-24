<?php

namespace App\Domains\Admin\Http\Controllers\Sale;

use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Helpers\Classes\OrmHelper;
use Illuminate\Http\Request;
use App\Models\Sale\CouponType;
use App\Repositories\Eloquent\Sale\CouponTypeRepository;

class CouponController extends BackendController
{
    private $breadcumbs;
    
    public function __construct(private Request $request)
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }

        $this->middleware(function ($request, $next) {
            $this->getLang(['admin/common/common', 'admin/sale/coupon_type']);
            $this->setBreadcumbs();
            return $next($request);
        })->only(['index', 'list', 'form']);
    }

    private function setBreadcumbs()
    {
        $this->breadcumbs = [];

        $this->breadcumbs[] = (object)[
            'text' => '銷售管理',
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];

        $this->breadcumbs[] = (object)[
            'text' => '優惠券類別',
            'href' => route('lang.admin.sale.coupon_types.index'),
        ];
    }

    /**
     * 顯示優惠券類型列表
     */
    public function index()
    {
        $data = [];
        
        // $couponTypes = CouponType::orderBy('id', 'desc')->paginate(20);
        $data['lang'] = $this->lang;

        $data['breadcumbs'] = (object)$this->breadcumbs;




        $data['list'] = $this->getList();

        $data['list_url'] = route('lang.admin.sale.coupon_types.list');
        $data['add_url'] = route('lang.admin.sale.coupon_types.form');
        $data['delete_url'] = route('lang.admin.sale.coupon_types.destroy');

        return view('admin.sale.coupon_type', $data);
    }

    /**
     * 顯示建立表單
     */
    public function create()
    {
        return view('admin.sale.coupon_types.create');
    }

    /**
     * 儲存新的優惠券類型
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:fixed,percent',
            'discount_amount' => 'required|numeric|min:0',
        ]);

        CouponType::create($data);

        return redirect()->route('sale.coupon_types.index')
            ->with('success', '優惠券類型已建立');
    }

    /**
     * 顯示編輯表單
     */
    public function edit(CouponType $couponType)
    {
        return view('admin.sale.coupon_types.edit', compact('couponType'));
    }

    /**
     * 更新優惠券類型
     */
    public function update(Request $request, CouponType $couponType)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:fixed,percent',
            'discount_amount' => 'required|numeric|min:0',
        ]);

        $couponType->update($data);

        return redirect()->route('sale.coupon_types.index')
            ->with('success', '優惠券類型已更新');
    }

    /**
     * 刪除優惠券類型
     */
    public function destroy(CouponType $couponType)
    {
        $couponType->delete();

        return redirect()->route('sale.coupon_types.index')
            ->with('success', '優惠券類型已刪除');
    }

    // 以上 RESTful

    private function getList()
    {
        $data['lang'] = $this->lang;

        // Prepare query_data for records
        $query_data  = $this->url_data;

        // Rows
        $query = (new CouponTypeRepository)->newModel()->newQuery();
        $filters = $this->url_data;

        OrmHelper::prepare($query, $filters);
        $couponTypes = OrmHelper::getResult($query, $filters);

        foreach ($couponTypes ?? [] as $row) {
            $row->edit_url = route('lang.admin.sale.coupon_types.form', array_merge([$row->id], $query_data));
        }

        $data['couponTypes'] = $couponTypes;


        // Prepare links for list table's header
        if (isset($query_data['order']) && $query_data['order'] == 'ASC') {
            $order = 'DESC';
        } else {
            $order = 'ASC';
        }

        $data['sort'] = strtolower($query_data['sort'] ?? '');
        $data['order'] = strtolower($order);

        $query_data = $this->unsetUrlQueryData($query_data);


        // link of table header for sorting
        $url = '';

        foreach ($query_data as $key => $value) {
            if (is_string($value)) {
                $url .= "&$key=$value";
            }
        }

        $route = route('lang.admin.sale.coupon_types.list');

        $data['sort_id'] = $route . "?sort=id&order=$order" . $url;
        $data['sort_code'] = $route . "?sort=code&order=$order" . $url;
        $data['sort_name'] = $route . "?sort=name&order=$order" . $url;
        $data['sort_sort_order'] = $route . "?sort=sort_order&order=$order" . $url;
        $data['sort_taxonomy_name'] = $route . "?sort=taxonomy_name&order=$order" . $url;
        $data['sort_is_active'] = $route . "?sort=is_active&order=$order" . $url;
        $data['sort_date_added'] = $route . "?sort=created_at&order=$order" . $url;

        $data['list_url'] = route('lang.admin.sale.coupon_types.list');

        return view('admin.sale.coupon_type_list', $data);
    }

    public function form($coupon_type_id = null)
    {
        $data = [];

        $data['breadcumbs'] = (object)$this->breadcumbs;

        $data['lang'] = $this->lang;

        // Get Record
        $query = (new CouponTypeRepository)->newModel()->newQuery();
        $filters = $this->url_data;
        $filters['first'] = true;
        $couponType = OrmHelper::findIdOrFailOrNew($query, $coupon_type_id);

        $data['couponType'] = $couponType;

        if (!empty($data['couponType']) && $coupon_type_id == $couponType->id) {
            $data['coupon_type_id'] = $coupon_type_id;
        } else {
            $data['coupon_type_id'] = null;
        }

        $queries  = $this->url_data;

        $data['save_url'] = route('lang.admin.sale.coupon_types.save', ['coupon_type_id' => $coupon_type_id]);
        $data['back_url'] = route('lang.admin.sale.coupon_types.index', $queries);

        return view('admin.sale.coupon_type_form', $data);
    }


}
