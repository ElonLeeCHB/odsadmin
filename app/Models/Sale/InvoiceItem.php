<?php

namespace App\Models\Sale;

use App\Enums\Sales\InvoiceItemTaxType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'sort_order',
        'name',
        'is_tax_included',
        'quantity',
        'price',
        'subtotal',
        'remark',
        'item_tax_type',
    ];

    protected $casts = [
        'is_tax_included' => 'boolean',
        'quantity' => 'decimal:3',
        'price' => 'decimal:3',
        'subtotal' => 'decimal:3',
        'item_tax_type' => InvoiceItemTaxType::class,
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
