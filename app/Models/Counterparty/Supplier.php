<?php

namespace App\Models\Counterparty;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use App\Models\Counterparty\Organization;
use App\Models\Counterparty\OrganizationMeta;
use App\Models\Counterparty\PaymentTerm;
use App\Traits\ModelTrait;

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

}
