<?php

namespace App\Models\Sale;

use App\Enums\Sales\InvoiceStatus;
use App\Enums\TaxType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'invoice_type',
        'invoice_format',
        'invoice_date',
        'customer_id',
        'tax_id_number',
        'buyer_name',
        'seller_name',
        'tax_type',
        'tax_state',
        'tax_amount',
        'net_amount',
        'total_amount',
        'api_request_data',
        'api_response_data',
        'api_error',
        'random_code',
        'content',
        'email',
        'carrier_type',
        'carrier_number',
        'donation_code',
        'status',
        'void_reason',
        'voided_by',
        'voided_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'invoice_date' => 'date:Y-m-d',
        'tax_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'tax_state' => 'integer',
        'tax_type' => TaxType::class,
        'status' => InvoiceStatus::class,
        'api_request_data' => 'array',
        'api_response_data' => 'array',
        'voided_at' => 'datetime',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    // 發票明細項目
    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    // 所屬的發票群組（透過中介表）
    public function invoiceGroups()
    {
        return $this->belongsToMany(
            InvoiceGroup::class,
            'invoice_group_invoices',
            'invoice_id',
            'group_id'
        )->withPivot('invoice_amount')->withTimestamps();
    }

    // 客戶關聯
    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class, 'customer_id');
    }
}
