<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceItem extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'name',
        'quantity',
        'unit_price',
        'amount',
        'note',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
