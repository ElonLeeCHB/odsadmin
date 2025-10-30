<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class InvoiceGroup extends Model
{
    protected $fillable = [
        'group_no',
        'invoice_issue_mode',
        'status',
        'void_reason',
        'voided_by',
        'voided_at',
        'created_by',
        'order_count',
        'invoice_count',
        'total_amount',
    ];

    protected $casts = [
        'voided_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 群組包含的訂單（透過中間表）
     */
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'invoice_group_orders', 'group_id', 'order_id')
            ->withPivot('order_amount')
            ->withTimestamps();
    }

    /**
     * 群組包含的發票（透過中間表）
     */
    public function invoices(): BelongsToMany
    {
        return $this->belongsToMany(Invoice::class, 'invoice_group_invoices', 'group_id', 'invoice_id')
            ->withPivot('invoice_amount')
            ->withTimestamps();
    }

    /**
     * 群組-訂單關聯記錄
     */
    public function invoiceGroupOrders(): HasMany
    {
        return $this->hasMany(InvoiceGroupOrder::class, 'group_id');
    }

    /**
     * 群組-發票關聯記錄
     */
    public function invoiceGroupInvoices(): HasMany
    {
        return $this->hasMany(InvoiceGroupInvoice::class, 'group_id');
    }

    /**
     * 只查詢有效的群組
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * 只查詢已作廢的群組
     */
    public function scopeVoided($query)
    {
        return $query->where('status', 'voided');
    }
}
