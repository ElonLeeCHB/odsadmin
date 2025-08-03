<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;

class InvoiceOrderMap extends Model
{
    public $timestamps = false;
    protected $fillable = ['invoice_id', 'order_id'];

    // 如果你想寫關聯
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
