<?php

namespace App\Models\Counterparty;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use App\Models\Counterparty\OrganizationMeta;
use App\Models\Counterparty\PaymentTerm;
use App\Traits\Model\ModelTrait;

class Organization extends Model
{
    use ModelTrait;
    
    protected $guarded = [];

    public $meta_keys = [
        'www',
        'line_id',
        'line_uid',
    ];

    public function payment_term()
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id', 'id');
    }

    public function company()
    {
        return $this->belongsTo(self::class, 'company_id', 'id');
    }

    public function corporation()
    {
        return $this->belongsTo(self::class, 'corporation_id', 'id');
    }


    // Attribute

    // protected function paymentTermName(): Attribute
    // {
    //     return Attribute::make(
    //         get: fn () => $this->payment_term->name ?? '',
    //     );
    // }

    protected function type1_txt(): Attribute
    {
        if(!empty($this->type1)){
            $arr = [
                '10' => '營利事業',
                '20' => '政府機關',
                '30' => '各級學校',
                '40' => '非營利事業',
            ];

            if(!empty($arr[$this->type1])){
                $value = $arr[$this->type1];
            }
        }

        if(empty($value)){
            $value = '';
        }

        return Attribute::make(
            get: fn ($value) => $value,
        );
    }

}
