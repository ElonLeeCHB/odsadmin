<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductTranslation;
use App\Models\Sale\Order;

use App\Traits\Model\ModelTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;

class OrderProductIngredient extends Model
{
    use ModelTrait;
    protected $appends = ['required_date_ymd', 'required_date_hi', 'required_weekday', 'product_name', 'ingredient_product_name'];

    protected $guarded = [];


    public function product_translation()
    {
        $locale = app()->getLocale();

        return $this->belongsTo(ProductTranslation::class, 'product_id', 'product_id')->where('locale', $locale);
    }

    public function ingredient_product_translation()
    {
        $locale = app()->getLocale();

        return $this->belongsTo(ProductTranslation::class, 'ingredient_product_id', 'product_id')->where('locale', $locale);
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }


    protected function requiredDateYmd(): Attribute
    {
        if(!empty($this->required_date)){
            $newValue = Carbon::parse($this->required_date)->format('Y-m-d');
        }

        return Attribute::make(
            get: fn ($value) => $newValue ?? '',
        );
    }

    protected function requiredDateHi(): Attribute
    {
        if(!empty($this->required_time)){
            $newValue = Carbon::parse($this->required_time)->format('H:i');
        }

        // if(empty($newValue) || $newValue == '00:00'){
        //     $newValue = '33';
        // }
        
        return Attribute::make(
            get: fn ($value) => $newValue,
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



    protected function productName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->product_translation->name ?? '',
        );
    }

    protected function ingredientProductName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->ingredient_product_translation->name ?? '',
        );
    }







}