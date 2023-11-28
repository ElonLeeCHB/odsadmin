<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductTranslation;
use App\Models\Sale\Order;

use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;

class OrderIngredientHour extends Model
{
    use ModelTrait;

    protected $appends = ['required_date_ymd', 'required_date_hi', 'required_weekday'];
    protected $guarded = [];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }


    protected function requiredDateYmd(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => !empty($this->required_date) ? Carbon::parse($this->required_date)->format('Y-m-d') : '',
        );
    }

    protected function requiredDateHi(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => !empty($this->required_time) ? Carbon::parse($this->required_time)->format('H:i') : '',
        );
    }


    protected function requiredWeekday(): Attribute
    {
        if(!empty($this->required_date)){
            $dayofweek = date('w', strtotime($this->required_date));
            $newValue = ['日', '一', '二', '三', '四', '五', '六'][$dayofweek];
        }else{
            $newValue = '';
        }

        return Attribute::make(
            get: fn ($value) => $newValue,
        );
    }
}