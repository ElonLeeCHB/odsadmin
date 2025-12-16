<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use App\Models\Common\Term;

class ProductChannelPrice extends Model
{
    protected $fillable = [
        'product_id',
        'channel_code',
        'price',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'channel_code' => 'integer',
        'price' => 'decimal:4',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 關聯通路 Term
     * channel_code → terms.code (WHERE taxonomy_code='sales_channel')
     */
    public function channel()
    {
        return $this->belongsTo(Term::class, 'channel_code', 'code')
            ->where('taxonomy_code', 'sales_channel');
    }

    /**
     * 取得目前有效的價格
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1)
            ->where(function ($q) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * 依通路篩選
     */
    public function scopeForChannel($query, int $channelCode)
    {
        return $query->where('channel_code', $channelCode);
    }
}
