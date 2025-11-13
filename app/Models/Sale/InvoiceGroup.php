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
     * 默認只返回活動中的訂單（is_active = 1）
     */
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'invoice_group_orders', 'group_id', 'order_id')
            ->withPivot('order_amount', 'is_active')
            ->wherePivot('is_active', 1) // 只查詢活動中的訂單
            ->withTimestamps();
    }

    /**
     * 群組包含的所有訂單（包含歷史記錄）
     */
    public function allOrders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'invoice_group_orders', 'group_id', 'order_id')
            ->withPivot('order_amount', 'is_active')
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

    /**
     * 作廢群組
     *
     * @param string $reason 作廢原因
     * @param int|null $voidedBy 作廢人ID
     * @return bool
     */
    public function voidGroup(string $reason, ?int $voidedBy = null): bool
    {
        // 檢查群組狀態
        if ($this->status === 'voided') {
            return false; // 已經作廢，不能重複作廢
        }

        // 開始交易
        return \DB::transaction(function () use ($reason, $voidedBy) {
            // 1. 更新群組狀態
            $this->update([
                'status' => 'voided',
                'void_reason' => $reason,
                'voided_by' => $voidedBy ?? auth()->id(),
                'voided_at' => now(),
            ]);

            // 2. 將該群組所有訂單的 is_active 改為 NULL（釋放訂單，允許重新加入其他群組）
            InvoiceGroupOrder::where('group_id', $this->id)
                ->where('is_active', 1)
                ->update(['is_active' => null]);

            return true;
        });
    }
}
