<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\User\User;
use App\Models\Sale\Order;
use App\Models\Localization\City;
use App\Models\Member\MemberMeta;
use App\Traits\Model\ModelTrait;

class Member extends User
{
    use ModelTrait;
    
    public $table = 'users';    
    protected $guarded = [];
    protected $foreignKey = 'user_id';
    
    public $meta_keys = [
        'is_admin',
        'first_name',
        'last_name',
        'short_name',
        'find_us',
        'find_us_comment',
    ];
    
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
    
    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    public function shipping_city()
    {
        return $this->belongsTo(City::class, 'shipping_city_id', 'id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id', 'id');
    }
    
    public function hasOrders()
    {
        return $this->orders()->exists();
    }

    public function latestOrder()
    {
        return $this->hasOne(Order::class)->latestOfMany();
    }


    public function oldestOrder()
    {
        return $this->hasOne(Order::class)->oldestOfMany();
    }
    

    public function largestOrder()
    {
        return $this->hasOne(Order::class)->ofMany('total', 'max');
    }

    protected function shippingStateId(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value ?? 0, 
        );
    }

    protected function shippingCityId(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value ?? 0, 
        );
    }

    /**
     * $row: 傳入的資料，可以是array，或是 model
     * 改寫傳入資料，或者設定預設值。
     * 
     * $type = updateOnlyInput, updateAll
     *     updateOnlyInput: 不會動到輸入資料以外的資料。如果 $row 是一個已存在的記錄，包括 $row->is_admin，但是輸入的資料沒有，那就不會動到 is_admin。
     *     updateAll: 如果輸入資料沒有，就清空。
     * 
     */
    public function prepareData($row, $data, $type = 'updateOnlyInput')
    {
        if (strlen($data['mobile']) != 10 || !is_numeric($data['mobile']) || substr($data['mobile'], 0, 2) != '09') {
            throw new \Exception('手機號碼錯誤！');
        }

        $data['mobile'] = preg_replace('/\D+/', '', $data['mobile'] ?? null);
        $data['telephone_prefix'] = $data['telephone_prefix'] ?? null;
        $data['shipping_personal_name'] = $data['shipping_personal_name'] ?? $data['personal_name'] ?? null;
        $data['shipping_company'] = $data['shipping_company'] ?? $data['payment_company'] ?? null;
        $data['shipping_country_code'] = $data['shipping_country_code'] ?? config('vars.default_country_code');
        $data['shipping_road_abbr'] = $data['shipping_road_abbr'] ?? $data['shipping_road'] ?? null;
        $data['shipping_road'] = $data['shipping_road'] ?? null;

        return $this->processPrepareData($row, $data, $type);
    }
}
