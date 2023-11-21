<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;
use App\Traits\ModelTrait;
use App\Models\Catalog\Product;
use App\Models\Counterparty\Supplier;
use App\Models\Inventory\Bom;

class OrderIngredientDaily extends Model
{
    use ModelTrait;

    public $table = 'order_ingredients_dailies';
    protected $appends = ['required_date_ymd', 'required_weekday'];
    protected $guarded = [];


    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    public function bom()
    {
        return $this->belongsTo(Bom::class, 'product_id', 'product_id');
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