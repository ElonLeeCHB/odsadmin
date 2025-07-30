<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;
use App\Enums\InvoiceStatus;
use App\Models\Sale\Order;

class Invoice extends Model
{
    // protected $guarded = ['id', 'created_at', 'deleted_at'];
    protected $guarded = [];

    protected $casts = [
        'status' => InvoiceStatus::class,
        'deleted_at' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'invoice_order_maps')
            ->withPivot('allocated_amount')
            ->withTimestamps();
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function invoiceOrderMaps()
    {
        return $this->hasMany(InvoiceOrderMap::class, 'invoice_id', 'id');
    }
}
