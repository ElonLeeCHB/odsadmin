<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductOption;
use App\Models\Sale\OrderProduct;
use App\Models\SysData\Division;
use App\Models\Member\Organization;
use App\Models\Catalog\OptionValue;
use App\Models\Common\Term;
use DateTimeInterface;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Traits\Model\ModelTrait;

class Order extends Model
{
    use ModelTrait;

    // 官網指示這樣寫
    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;

    protected $guarded = [];

    protected $appends = ['delivery_date_ymd', 'delivery_date_hi', 'delivery_weekday','status_name'];

    protected $casts = [
        'is_closed' => 'boolean',
        'is_payed_off' => 'boolean',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

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
        return $this->hasMany(OrderProduct::class, 'order_id', 'id');
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

    public function order_product_ingredients()
    {
        return $this->hasManyThrough(OrderProductIngredients::class, OrderProduct::class);
    }

    public function totals()
    {
        return $this->hasMany(OrderTotal::class, 'order_id', 'id');
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

    public function shipping_city()
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

    //待廢。原本使用 options, option_values 資料表。以後改為使用 terms
    // public function status()
    // {
    //     return $this->belongsTo(OptionValue::class, 'status_id', 'id');
    // }

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
}
