<?php

namespace App\Models\Sale;

use App\Enums\InvoiceStatus;
use App\Enums\TaxType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Invoice extends Model
{
    protected $fillable = [
        'order_group_id',
        'invoice_number',
        'invoice_date',
        'buyer_name',
        'seller_name',
        'tax_id_number',
        'customer_id',
        'tax_type',
        'tax_amount',
        'total_amount',
        'status',
        'creator_id',
        'modifier_id',
    ];

    protected $casts = [
        'invoice_date' => 'date:Y-m-d',
        'tax_amount' => 'integer',
        'total_amount' => 'integer',
        'tax_type' => TaxType::class,
        'status' => InvoiceStatus::class,
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    // 單身項目
    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    // 對應到多張訂單
    public function orders()
    {
        return $this->hasManyThrough(
            Order::class, // 你可以替換成實際的 Order Model
            InvoiceOrderMap::class,
            'invoice_id', // 中介表 invoice_order_maps 中的外鍵
            'id',         // Order 的主鍵
            'id',         // Invoice 的主鍵
            'order_id'    // 中介表中的訂單 ID
        );
    }

    public function invoiceOrderMaps()
    {
        return $this->hasMany(InvoiceOrderMap::class, 'invoice_id', 'id');
    }
}
