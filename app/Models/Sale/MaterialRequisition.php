<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;
use App\Models\Catalog\ProductTranslation;
use App\Models\Sale\OrderProduct;

class MaterialRequisition extends Model
{    
    protected $guarded = [];
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
    
    protected function deliveryDateYmd(): Attribute
    {
        if(!empty($this->delivery_date)){
            $newValue = Carbon::parse($this->delivery_date)->format('Y-m-d');
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




}
