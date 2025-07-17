<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductOption;
use App\Models\User\User;
use App\Models\Sale\OrderProduct;
use App\Models\Sale\OrderPacking;
use App\Models\SysData\Division;
use App\Models\Counterparty\Organization;
use App\Models\Catalog\OptionValue;
use App\Models\Common\Term;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Repositories\Eloquent\SysData\DivisionRepository;
use App\Traits\Model\ModelTrait;
use DateTimeInterface;
use App\Helpers\Classes\DataHelper;
use App\Helpers\Classes\DateHelper;
use App\Helpers\Classes\OrmHelper;
use Illuminate\Support\Facades\Storage;
use App\Models\Sale\Invoice;

/*
delivery_date 應該改為 date 格式，不要有時分秒。因為已經有了 delivery_time_range。
先前花很多心力拆解 delivery_date, 不值得。
就算按通則來說，delivery_date 有可能仍然需要表達時間，幾點幾分送達。但本系統已經有使用 delivery_time_range，會混亂。
*/

class Order extends Model
{
    use ModelTrait;

    // 官網指示這樣寫
    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;

    protected $appends = ['order_date_ymd', 'delivery_date_ymd', 'delivery_date_hi', 'delivery_weekday'
                        ,'status_name', 'salutation_name'];

    protected $casts = [
        'is_closed' => 'boolean',
        'is_payed_off' => 'boolean',
        'order_date' => 'date:Y-m-d',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function getJsonInfoCacheKey($code)
    {
        return 'cache/orders/orderCode-' . $code.'.json';
    }

    protected static function booted()
    {
        parent::boot();

        static::observe(\App\Observers\OrderObserver::class);
    }

    // Relationships
    /*
    orders
        order_products
            order_product_option
                product_options
                    product_option_values
                        options
                            option_values
    */

    //待廢
    public function order_products()
    {
        return $this->hasMany(OrderProduct::class, 'order_id', 'id');
    }
    
    //這才是慣例命名
    public function orderProducts()
    {
        return $this->hasMany(OrderProduct::class, 'order_id', 'id');
    }

    //待廢
    public function order_product_options()
    {
        return $this->hasManyThrough(OrderProductOption::class, OrderProduct::class);
    }

    public function orderProductOptions()
    {
        return $this->hasManyThrough(OrderProductOption::class, OrderProduct::class);
    }

    public function orderTotals()
    {
        return $this->hasMany(OrderTotal::class, 'order_id', 'id');
    }

    /**
     * 應該用 order_id 但是前人用了 order_code
     */
    public function payments()
    {
        return $this->hasMany(OrderPayment::class, 'order_code', 'code');
    }

    // 廢棄，改用 orderTags()
    // public function tags()
    // {
    //     return $this->belongsToMany(Term::class, 'order_tags');
    // }

    public function orderTags()
    {
        return $this->belongsToMany(Term::class, 'order_tags');
    }
    //

    public function shipping_state()
    {
        return $this->belongsTo(Division::class, 'shipping_state_id', 'id');
    }

    public function shippingState()
    {
        return $this->belongsTo(Division::class, 'shipping_state_id', 'id');
    }

    public function shipping_city()
    {
        return $this->belongsTo(Division::class, 'shipping_city_id', 'id');
    }

    public function shippingCity()
    {
        return $this->belongsTo(Division::class, 'shipping_city_id', 'id');
    }

    // public function shipping_state_name()
    // {
    //     return $this->belongsTo(Division::class, 'shipping_state_id', 'id')->name;
    // }

    public function store()
    {
        return $this->belongsTo(Organization::class, 'store_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id', 'id'); 
    }

    public function deliveries()
    {
        return $this->hasMany(OrderDelivery::class, 'order_code', 'code'); 
    }
    

    //待廢。原本使用 options, option_values 資料表。以後改為使用 terms
    public function status()
    {
        return $this->belongsTo(OptionValue::class, 'status_id', 'id');
    }

    // 製餐記錄
    public function orderPacking()
    {
        return $this->hasOne(OrderPacking::class, 'order_id', 'id');
    }

    // 製餐記錄 待廢
    // public function packing()
    // {
    //     return $this->hasOne(OrderPacking::class, 'order_id', 'id');
    // }

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'invoice_order_maps')
            ->withPivot('allocated_amount')
            ->withTimestamps();
    }
    
    public function orderGroup()
    {
        return $this->belongsTo(OrderGroup::class, 'order_group_id');
    }


    /**
     * Attribute
     */

    public function statusName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => TermRepository::getNameByCodeAndTaxonomyCode($this->status_code, 'order_status') ?? '',
        );
    }

    protected function salutationName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => TermRepository::getNameByCodeAndTaxonomyCode($this->salutation_code, 'Salutation') ?? '',
            
        );
    }

    protected function shippingSalutationName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => TermRepository::getNameByCodeAndTaxonomyCode($this->shipping_salutation_code, 'Salutation') ?? '',
            
        );
    }

    protected function shippingSalutation2Name(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => TermRepository::getNameByCodeAndTaxonomyCode($this->shipping_salutation_code2, 'Salutation') ?? '',
            
        );
    }

    // protected function shippingStateName(): Attribute
    // {
    //     // // DiviDivisionRepository
    //     // $divisions = DivisionRepository::getDivisions();
    //     // echo "<pre>",print_r($city_name,true),"</pre>";exit;
    //     // return Attribute::make(
    //     //     get: fn () => $this->shippingState?->name,
    //     // );



    //     $divisions = DivisionRepository::getDivisions();
        
    //     return Attribute::make(
    //         get: fn () => $divisions[$this->shipping_state_id].'xx' ?? '',
    //     );
    // }

    protected function shippingStateName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->shippingState?->name ?? null,
        );
    }

    protected function shippingCityName(): Attribute
    {
        $divisions = DivisionRepository::getDivisions();
        
        return Attribute::make(
            get: fn () => $this->shippingCity?->name ?? null,
        );
    }
    
    public function CustomerComment(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => optional($this->customer)->comment ?? '',
        );
    }

    protected function orderDateYmd(): Attribute
    {
        if(!empty($this->order_date)){
            $order_date = Carbon::parse($this->order_date)->format('Y-m-d');
        }else{
            $order_date = date('Y-m-d');
        }
        
        return Attribute::make(
            get: fn ($value) => $order_date ?? '',
        );
    }

    public function shippingStateId(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => !empty($value) ? $value : 0,
        );
    }

    public function shippingCityId(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => !empty($value) ? $value : 0,
        );
    }

    // Mobile or Telephone

    protected function parsePhone($phone)
    {
		$phone = str_replace('-', '', $phone);
        $part3 = '';
        $new_phone = '';

        //Taiwan's mobile
        if(str_starts_with($phone, '09')){
            $new_phone = substr($phone, 0, 4) . '-' . substr($phone, 4) ;
        }
        // Telephone
        else{
            preg_match('/(\d+)#?(\d+)?/', $phone, $matches);

            if(!empty($matches[0])){
                $part1 = substr($matches[1],0,-4);
                $part2 = substr($matches[1],-4);
                if(!empty($matches[2])){
                    $part3 = '#' . $matches[2];
                }
                $new_phone = $part1 . '-' . $part2 . $part3;
            }
        }

        if(empty($new_phone)){
            $new_phone = '';
        }

        return $new_phone;
    }

    // 不需要為了加橫線而解析。前台前端使複製貼上反而造成混亂。
    // protected function mobile(): Attribute
    // {
    //     return Attribute::make(
    //         get: fn ($value) => $this->parsePhone($value),
    //     );
    // }

    protected function telephone(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->parsePhone($value),
        );
    }

    protected function shippingPhone(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->parsePhone($value),
        );
    }

    protected function shippingDate(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($value)->format('Y-m-d'),
        );
    }

    protected function deliveryDateYmd(): Attribute
    {
        if(!empty($this->delivery_date)){
            $newValue = Carbon::parse($this->delivery_date)->format('Y-m-d');
        }else{
            $newValue = date('Y-m-d');
        }
        return Attribute::make(
            get: fn ($value) => $newValue ?? '',
        );
    }

    protected function deliveryDateHi(): Attribute
    {
        if(!empty($this->delivery_date)){
            $newValue = Carbon::parse($this->delivery_date)->format('H:i');
        }

        if(empty($newValue) || $newValue == '00:00'){
            $newValue = '';
        }

        return Attribute::make(
            get: fn ($value) => $newValue,
        );
    }


    protected function deliveryWeekday(): Attribute
    {
        if(!empty($this->delivery_date)){
            $dayofweek = date('w', strtotime($this->delivery_date));
            $newValue = ['日', '一', '二', '三', '四', '五', '六'][$dayofweek];
        }else{
            $newValue = '';
        }

        return Attribute::make(
            get: fn ($value) => $newValue,
        );
    }

    protected function paymentTotal(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ?? 0,
            set: fn ($value) => empty($value) ? 0 : str_replace(',', '', $value),
        );
    }

    protected function paymentPaid(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ?? 0,
            set: fn ($value) => empty($value) ? 0 : str_replace(',', '', $value),
        );
    }

    protected function paymentUnpaid(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ?? 0,
            set: fn ($value) => empty($value) ? 0 : str_replace(',', '', $value),
        );
    }


    // Functoins

    /**
     * $row: 當前資料庫記錄
     * $data 新資料
     * 改寫傳入資料，或者設定預設值。
     * 
     * $type = updateOnlyInput, updateAll
     *     updateOnlyInput: 不會動到輸入資料以外的資料。如果 $row 是已存在的內容，包括 $row->is_admin=1，但是輸入的資料沒有，那就不會動到 is_admin，仍然保持1。
     *     updateAll: 如果輸入資料沒有，就清空。
     * 
     */
    public function prepareData($data)
    {
        $data['source'] = $data['source'] ?? null;
        $data['store_id'] = $data['store_id'] ?? 0;
        $data['customer_id'] = (isset($data['customer_id']) && is_numeric($data['customer_id'])) ? $data['customer_id'] : 0;
        $data['quantity_for_control'] = $data['quantity_for_control'] ?? 0;
        $data['is_option_qty_controlled'] = $data['is_option_qty_controlled'] ?? 0;
        $data['mobile'] = preg_replace('/\D+/', '', $data['mobile'] ?? null);
        $data['salutation_code'] = $data['salutation_code'] ?? 0;
        
        $data['payment_total'] = (isset($data['payment_total']) && is_numeric($data['payment_total'])) ? $data['payment_total'] : 0;
        $data['payment_paid'] = (isset($data['payment_paid']) && is_numeric($data['payment_paid'])) ? $data['payment_paid'] : 0;
        $data['payment_unpaid'] = (isset($data['payment_unpaid']) && is_numeric($data['payment_unpaid'])) ? $data['payment_unpaid'] : 0;
        $data['payment_unpaid'] = empty($data['payment_paid']) ? $data['payment_total'] : $data['payment_unpaid'];

        $data['shipping_personal_name'] = $data['shipping_personal_name'] ?? $data['personal_name'] ?? null;
        $data['shipping_company'] = $data['shipping_company'] ?? $data['payment_company'] ?? null;
        $data['shipping_country_code'] = $data['shipping_country_code'] ?? config('vars.default_country_code');
        $data['shipping_road_abbr'] = $data['shipping_road_abbr'] ?? $data['shipping_road'] ?? null;
        $data['shipping_road'] = $data['shipping_road'] ?? null;
        $data['shipping_salutation_code'] = $data['shipping_salutation_code'] ?? 0;
        $data['shipping_salutation_code2'] = $data['shipping_salutation_code2'] ?? 0;

        //delivery_date 如果送達時間的 時分秒 是00:00:00, 則取時間範圍的結束時間做為送達時間。例如 1100-1200, 取 12:00。
            
            // $delivery_date_ymd 必須來自 $data['delivery_date'] 或 $data['delivery_date_ymd']
            if (!empty($data['delivery_date_ymd'])){
                $delivery_date_ymd = $data['delivery_date_ymd'];
            } else {
                $delivery_date_ymd = preg_match('/^\d{4}-\d{2}-\d{2}/', $data['delivery_date']) ?? '';
            }

            // $delivery_date_hi 必須來自 $data['delivery_date'] 或 $data['delivery_date_hi'] 注意沒有秒數 s
            if (!empty($data['delivery_date_hi'])) {
                //避免使用者只打數字，例如 1630所以取開頭、跟結尾，中間插入冒號 :
                $delivery_date_hi = substr($data['delivery_date_hi'],0,2).':'.substr($data['delivery_date_hi'],-2);
            } else if (!empty($data['delivery_date'])) {
                $time = substr($data['delivery_date'], 11, 5);
                $delivery_date_hi = !empty($time) ? $time : '00:00';
            } else {
                $delivery_date_hi = '00:00';
            }

            $delivery_date = $delivery_date_ymd . ' ' . $delivery_date_hi . ':00';

            if (!DateHelper::isValid($delivery_date)){
                throw new \Exception('送達時間錯誤！');
            }

            // 如果時分是00:00，並且存在時間範圍，, 取結尾時間
            if ($delivery_date_hi == '00:00' && !empty($data['delivery_time_range'])){
                
                $arr = explode('-',$data['delivery_time_range']);

                // 前兩位數 + 冒號 + 倒數兩位數
                if(!empty($arr[1])){
                    $t2 = substr($arr[1],0,2).':'.substr($arr[1],-2);
                }else if(!empty($arr[0])){
                    $t2 = substr($arr[0],0,2).':'.substr($arr[0],-2);
                }

                if(!empty($t2)){
                    $delivery_date = $delivery_date_ymd . ' ' . $t2 . ':00';
                }
            }
        //

        $data['delivery_date'] = $delivery_date;

        return $this->processPrepareData($data);
    }

    /* 更新全部的控單數量
-- 更新 order_product_options.quantity_for_control
UPDATE order_product_options opo
JOIN product_option_values pov ON opo.product_option_value_id = pov.id
JOIN option_values ov ON pov.option_value_id = ov.id
JOIN products p ON ov.product_id = p.id
SET opo.quantity_for_control = 
    CASE
        WHEN p.quantity_for_control IS NOT NULL AND opo.quantity IS NOT NULL THEN p.quantity_for_control * opo.quantity
        ELSE 0
    END;


-- 更新 order_products.quantity_for_control
UPDATE order_products op
JOIN (
    SELECT opo.order_product_id, SUM(opo.quantity_for_control) AS total_quantity_for_control
    FROM order_product_options opo
    GROUP BY opo.order_product_id
) AS sub ON op.id = sub.order_product_id
SET op.quantity_for_control = 
    CASE
        WHEN sub.total_quantity_for_control IS NOT NULL THEN sub.total_quantity_for_control
        ELSE 0
    END;

-- 更新 orders.quantity_for_control
UPDATE orders o
JOIN (
    SELECT op.order_id, SUM(op.quantity_for_control) AS total_quantity_for_control
    FROM order_products op
    GROUP BY op.order_id
) AS sub ON o.id = sub.order_id
SET o.quantity_for_control = 
    CASE
        WHEN sub.total_quantity_for_control IS NOT NULL THEN sub.total_quantity_for_control
        ELSE 0
    END;

-- 查 order_product_options
select opo.name, opo.value, opo.quantity, opo.quantity_for_control
from order_product_options opo
where opo.order_id=9219 and opo.name='主餐' 
    -- and opo.option_value_id=69062

-- 查 order_products
select id, name, quantity, quantity_for_control
from order_products op
where op.order_id=9219
    */

    /**
     * cache
     */

    // 不使用。但先留著。
    public function getCacheKeyById($order_id)
    {
        return 'cache/sale/order/id-' . $order_id . '.serialized.txt';
    }

    // 廢棄
    public function getCacheKeyByCode($order_code)
    {
        return 'cache/sale/order/code-'. $order_code . '.json';
    }

    // 最好用實例呼叫，速度比較快
    public function deleteCacheById($id = null, $order = null)
    {
        $id = '';
        $code = '';

        if (!empty($this->id)){
            $id = $this->id;
        }

        if (!empty($this->code)){
            $code = $this->code;
        }

        if (empty($code) && !empty($id)){
            $order = self::select('code')->find($id);
            $code = $order?->code;
        }

        $cache_key = $this->getCacheKeyById($id);
        DataHelper::deleteDataFromStorage($cache_key);
    }

    // getOrderByIdOrCode
    public function getOrderByIdOrCode($value, $type, $params = [])
    {
        if ($type == 'code'){
            $order_id = self::where('code', $value)->value('id');
        } else {
            $order_id = $value;
        }

        $cache_key = $this->getCacheKeyById($order_id);

        if(request()->has('no-cache') && request()->query('no-cache') == 1){
            DataHelper::deleteDataFromStorage($cache_key);
        }

        return DataHelper::remember($cache_key, 60*60*24, 'serialize', function() use ($order_id) {
            $builder = $this->query();
            $builder->where('id', $order_id);

            $builder->with(['orderProducts' => function($qry) {
                        $qry->with('orderProductOptions');
                    }]);

            $builder->with('orderTotals')
                    ->with('OrderTags')
                    ->with('shippingState')
                    ->with('shippingCity')
                    ->with('customer:id,comment');


            $order = $builder->first();

            $order->setRelation('orderTotals', $order->orderTotals->keyBy('code'));

            if ($order){
                $order->shipping_state_name = optional($order->shipping_state)->name ?? '';
                $order->shipping_city_name = optional($order->shipping_city)->name ?? '';
            }

            return $order ?? [];
        });
    }

    public static function getDefaultListColumns()
    {
        return [
            'id', 'code', 'source', 'personal_name', 'mobile', 'telephone_prefix', 'telephone', 'payment_company', 'delivery_date', 'status_code'
            , 'print_status', 'order_taker', 'salutation_code'
        ];

    }


    // abandoned
    public function setDefaultData($data)
    {
        $data['customer_id'] = !empty($data['customer_id']) ? $data['customer_id'] : 0;
        $data['telephone_prefix'] = !empty($data['telephone_prefix']) ? $data['telephone_prefix'] : '';

        return $data;
    }
}
