<?php

namespace App\Models\Counterparty;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use App\Models\Counterparty\Organization;
use App\Models\Counterparty\OrganizationMeta;
use App\Models\Counterparty\PaymentTerm;
use App\Repositories\Eloquent\Common\TermRepository;

class Supplier extends Organization
{
    public $meta_keys = [
        'supplier_contact_name',
        'supplier_contact_email',
        'supplier_contact_jobtitle',
        'supplier_contact_telephone',
        'supplier_contact_mobile',
        'supplier_bank_name',
        'supplier_bank_code',
        'supplier_bank_account',
        'tax_type_code',
        'is_often_used_supplier',
        'www',
        'line_id',
        'line_uid',
    ];
    public $master_key = 'organization_id';
    public $meta_model = 'App\Models\Counterparty\OrganizationMeta';
    protected $table = 'organizations';
    protected $appends = ['tax_type_name'];

    protected static function booted()
    {
        parent::booted();

        static::addGlobalScope('is_supplier', function (Builder $builder) {
            $builder->where('is_supplier', 1);
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
        return Attribute::make(
            get: fn ($value) => TermRepository::getNameByCodeAndTaxonomyCode($this->tax_type_code, 'tax_type') ?? '',
        );
    }

}
