<?php

namespace App\Models\Counterparty;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use App\Models\Counterparty\Organization;
use App\Models\Counterparty\OrganizationMeta;
use App\Models\Counterparty\PaymentTerm;
use App\Traits\ModelTrait;
use App\Repositories\Eloquent\Common\TermRepository;

class Supplier extends Model
{
    protected $table = 'organizations';

    protected static function booted()
    {
        parent::booted();

        // 定義全域範圍，僅返回 is_supplier=1 的組織
        static::addGlobalScope('is_supplier', function (Builder $builder) {
            $builder->whereHas('metas', function ($query) {
                $query->where('meta_key', 'is_supplier')->where('meta_value', 1);
            });
        });
    }

    public function payment_term()
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function metas()
    {
        return $this->hasMany(OrganizationMeta::class, 'organization_id', 'id');
    }

    public function taxTypeName(): Attribute
    {
        $new_value = $this->metas()->where('meta_key', 'tax_type_code')->first();
       // echo '<pre>', print_r($new_value, 1), "</pre>"; exit;
        return Attribute::make(
            //get: fn ($value) => TermRepository::getNameByCodeAndTaxonomyCode($this->form_type_code, 'tax_type') ?? '',
            get: fn ($value) => 123,
        );
    }

}
