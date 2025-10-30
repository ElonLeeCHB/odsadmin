<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;

class InvoiceCarrierType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'giveme_param',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 只查詢啟用的載具類型
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 依排序順序查詢
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
