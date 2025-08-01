<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'name',
        'quantity',
        'price',
        'amount',
        'note',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
