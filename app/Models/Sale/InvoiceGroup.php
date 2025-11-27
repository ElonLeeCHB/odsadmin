<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class InvoiceGroup extends Model
{
    protected $fillable = [
        'group_no',
        'invoice_issue_mode',
        'status',
        'invoice_status',
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
     * 只查詢發票待開立的群組 (invoice_status = pending)
     */
    public function scopeInvoicePending($query)
    {
        return $query->where('invoice_status', 'pending');
    }

    /**
     * 只查詢發票部分開立的群組 (invoice_status = partial)
     */
    public function scopeInvoicePartial($query)
    {
        return $query->where('invoice_status', 'partial');
    }

    /**
     * 只查詢發票全部開立完成的群組 (invoice_status = issued)
     */
    public function scopeInvoiceIssued($query)
    {
        return $query->where('invoice_status', 'issued');
    }

    /**
     * 計算群組內訂單金額總和
     * 使用 orders.payment_total
     *
     * @return float
     */
    public function calculateOrdersTotal(): float
    {
        return (float) $this->orders()->sum('orders.payment_total');
    }

    /**
     * 計算群組內發票金額總和
     * 使用 invoices.total_amount
     *
     * @return float
     */
    public function calculateInvoicesTotal(): float
    {
        return (float) $this->invoices()->sum('invoices.total_amount');
    }

    /**
     * 檢查群組金額是否平衡（訂單總額 = 發票總額）
     *
     * @return bool
     */
    public function isAmountBalanced(): bool
    {
        $ordersTotal = $this->calculateOrdersTotal();
        $invoicesTotal = $this->calculateInvoicesTotal();

        // 使用 bccomp 避免浮點數精度問題，精確到小數點後 2 位
        return bccomp((string) $ordersTotal, (string) $invoicesTotal, 2) === 0;
    }

    /**
     * 更新群組的開票狀態（invoice_status）
     * 根據群組內發票的狀態自動判斷
     *
     * @return string 更新後的狀態
     * @throws \Exception 當要標記為 issued 但金額不平衡時
     */
    public function refreshInvoiceStatus(): string
    {
        $invoices = $this->invoices()->get();

        if ($invoices->isEmpty()) {
            $this->invoice_status = 'pending';
            $this->save();
            return 'pending';
        }

        $allIssued = $invoices->every(fn($invoice) => $invoice->status === 'issued');
        $anyIssued = $invoices->contains(fn($invoice) => $invoice->status === 'issued');

        if ($allIssued) {
            // 要標記為 issued 前，必須檢查金額平衡
            if (!$this->isAmountBalanced()) {
                $ordersTotal = $this->calculateOrdersTotal();
                $invoicesTotal = $this->calculateInvoicesTotal();
                throw new \Exception(
                    "群組 {$this->group_no} 金額不平衡，無法標記為全部開立完成。" .
                    "訂單總額: {$ordersTotal}, 發票總額: {$invoicesTotal}"
                );
            }
            $this->invoice_status = 'issued';
        } elseif ($anyIssued) {
            $this->invoice_status = 'partial';
        } else {
            $this->invoice_status = 'pending';
        }

        $this->save();
        return $this->invoice_status;
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
        return DB::transaction(function () use ($reason, $voidedBy) {
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
