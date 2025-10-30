<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceGroupOrder extends Model
{
    protected $fillable = [
        'group_id',
        'order_id',
        'order_amount',
    ];

    protected $casts = [
        'order_amount' => 'decimal:2',
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
     * 關聯的訂單
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
