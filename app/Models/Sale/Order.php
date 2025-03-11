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
use App\Models\SysData\Division;
use App\Models\Counterparty\Organization;
use App\Models\Catalog\OptionValue;
use App\Models\Common\Term;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Traits\Model\ModelTrait;
use DateTimeInterface;


class Order extends Model
{
    use ModelTrait;

    // 官網指示這樣寫
    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;

    protected $appends = ['order_date_ymd', 'delivery_date_ymd', 'delivery_date_hi', 'delivery_weekday','status_name'];

    protected $casts = [
        'is_closed' => 'boolean',
        'is_payed_off' => 'boolean',
        // 'order_date' => 'date:Y-m-d',
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

    //這應該不是慣例命名
    public function order_products()
    {
        return $this->orderProducts();
    }
    
    //這才是慣例命名
    public function orderProducts()
    {
        return $this->hasMany(OrderProduct::class, 'order_id', 'id');
    }

    public function order_product_options()
    {
        return $this->hasManyThrough(OrderProductOption::class, OrderProduct::class);
    }

    public function totals()
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

    public function tags()
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

    

    public function statusName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => TermRepository::getNameByCodeAndTaxonomyCode($this->status_code, 'order_status') ?? '',
        );
    }


    // Attribute

    public function id(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => !empty($value) ? $value : 0,
        );
    }

    protected function orderDateYmd(): Attribute
    {
        if(!empty($this->order_date)){
            $newValue = Carbon::parse($this->order_date)->format('Y-m-d');
        }else{
            $newValue = date('Y-m-d');
        }
        return Attribute::make(
            get: fn ($value) => $newValue ?? '',
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

    protected function mobile(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->parsePhone($value),
        );
    }

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
    public function setDefaultData($data)
    {
        $data['customer_id'] = !empty($data['customer_id']) ? $data['customer_id'] : 0;
        $data['telephone_prefix'] = !empty($data['telephone_prefix']) ? $data['telephone_prefix'] : '';

        return $data;
    }

    /**
     * $row: 傳入的資料，可以是array，或是 model
     * 改寫傳入資料，或者設定預設值。
     */
    public function prepareData($row)
    {
        $data = []; //傳入資料轉換成陣列
        
        if (is_array($row)){
            $data = $row;
        }
        else if(is_object($row)){
            if(method_exists($row, 'toArray')){
                $data = $row->toArray();
            }
            $data = (array) $row;
        }
        
        $data['source'] = $data['source'] ?? null;
        $data['location_id'] = $data['location_id'] ?? 0;
        $data['customer_id'] = (isset($data['customer_id']) && is_numeric($data['customer_id'])) ? $data['customer_id'] : 0;
        $data['quantity_for_control'] = $data['quantity_for_control'] ?? 0;
        $data['is_options_controlled'] = $data['is_options_controlled'] ?? 0;
        $data['mobile'] = preg_replace('/\D+/', '', $data['mobile'] ?? null);
        
        $data['payment_total'] = (isset($data['payment_total']) && is_numeric($data['payment_total'])) ? $data['payment_total'] : 0;
        $data['payment_paid'] = (isset($data['payment_paid']) && is_numeric($data['payment_paid'])) ? $data['payment_paid'] : 0;
        $data['payment_unpaid'] = (isset($data['payment_unpaid']) && is_numeric($data['payment_unpaid'])) ? $data['payment_unpaid'] : 0;
        $data['payment_unpaid'] = empty($data['payment_paid']) ? $data['payment_total'] : $data['payment_unpaid'];

        $data['shipping_personal_name'] = $data['shipping_personal_name'] ?? $data['personal_name'] ?? null;
        $data['shipping_company'] = $data['shipping_company'] ?? $data['payment_company'] ?? null;
        $data['shipping_country_code'] = $data['shipping_country_code'] ?? config('vars.default_country_code');
        $data['shipping_road_abbr'] = $data['shipping_road_abbr'] ?? $data['shipping_road'] ?? null;
        $data['shipping_road'] = $data['shipping_road'] ?? null;

        //delivery_date 如果送達時間的 時:分 是00:00, 則取時間範圍的結束時間做為送達時間。例如 1100-1200, 取 12:00
            if(empty($data['delivery_date_hi']) || $data['delivery_date_hi'] == '00:00'){
                if(!empty($data['delivery_time_range'])){
                    $arr = explode('-',$data['delivery_time_range']);
    
                    if(!empty($arr[1])){
                        $t2 = substr($arr[1],0,2).':'.substr($arr[1],-2);
                    }else if(!empty($arr[0])){
                        $t2 = substr($arr[0],0,2).':'.substr($arr[0],-2);
                    }
    
                    if(!empty($t2)){
                        $delivery_date_hi = $t2;
                    }else{
                        $delivery_date_hi = '';
                    }
                }
            }else if(!empty($data['delivery_date_hi'])){
                //避免使用者只打數字，例如 1630所以取開頭、跟結尾，中間插入冒號 :
                $delivery_date_hi = substr($data['delivery_date_hi'],0,2).':'.substr($data['delivery_date_hi'],-2);
            }

            if(!empty($data['delivery_date_ymd'])){
                if(!empty($delivery_date_hi)){
                    $delivery_date = $data['delivery_date_ymd'] . ' ' . $delivery_date_hi;
                }else{
                    $delivery_date = $data['delivery_date_ymd'];
                }
            }

            $data['delivery_date'] = $delivery_date ?? null;
        //

        $table_columns = $this->getTableColumns();

        foreach ($table_columns as $column) {
            if(is_array($row) && !isset($row[$column]) && isset($data[$column])){
                $row[$column] = $data[$column];
            }
    
            else if(is_object($row) && !isset($row->column) && isset($data[$column])){
                $row->{$column} = $data[$column];
            }
        }

        return $row;
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
}
