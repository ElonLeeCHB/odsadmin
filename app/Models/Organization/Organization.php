<?php

namespace App\Models\Organization;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use App\Models\Localization\Division;
use App\Models\Organization\OrganizationMeta;

class Organization extends Model
{
    protected $guarded = [];

    public $meta_keys = [
        'supplier_contact_name',
        'supplier_contact_email',
        'supplier_contact_jobtitle',
        'supplier_contact_telephone',
        'supplier_contact_mobile',
    ];

    public function meta_dataset()
    {
        return $this->hasMany(OrganizationMeta::class);
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
