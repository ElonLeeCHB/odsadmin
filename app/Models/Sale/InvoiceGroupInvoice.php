<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceGroupInvoice extends Model
{
    protected $fillable = [
        'group_id',
        'invoice_id',
        'invoice_amount',
    ];

    protected $casts = [
        'invoice_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 所屬的群組
     */
    public function invoiceGroup(): BelongsTo
    {
        return $this->belongsTo(InvoiceGroup::class, 'group_id');
    }

    /**
     * 關聯的發票
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
