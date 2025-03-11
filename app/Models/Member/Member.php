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

        if (strlen($data['mobile']) != 10 || !is_numeric($data['mobile']) || substr($data['mobile'], 0, 2) != '09') {
            return false;
        }

        $data['telephone_prefix'] = $data['telephone_prefix'] ?? null;
        $data['shipping_personal_name'] = $data['shipping_personal_name'] ?? $data['personal_name'] ?? null;
        $data['shipping_company'] = $data['shipping_company'] ?? $data['payment_company'] ?? null;
        $data['shipping_country_code'] = $data['shipping_country_code'] ?? config('vars.default_country_code');
        $data['shipping_road_abbr'] = $data['shipping_road_abbr'] ?? $data['shipping_road'] ?? null;
        $data['shipping_road'] = $data['shipping_road'] ?? null;

        $table_columns = $this->getTableColumns();
        $input_keys = array_keys($data);
        $delete_keys = array_diff($input_keys, $table_columns);

        $data['telephone_prefix'] = $data['telephone_prefix'] ?? null;

        foreach ($table_columns as $column) {
            if(is_array($row)){
                if(in_array($column, $delete_keys)){
                    unset($row[$column]);
                    continue;
                }

                // $data有值才改寫，沒有則略過
                if(isset($data[$column])){
                    $row[$column] = $data[$column];
                }
            }
    
            else if(is_object($row) && !isset($row->column) && isset($data[$column])){
                if(in_array($column, $delete_keys)){
                    unset($row->{$column});
                    continue;
                }

                // $data有值才改寫，沒有則略過
                if(isset($data[$column])){
                    $row->{$column} = $data[$column];
                }
            }
        }

        return $row;
    }
}
