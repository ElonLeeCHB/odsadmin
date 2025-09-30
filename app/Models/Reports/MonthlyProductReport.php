<?php

namespace App\Models\Reports;

use Illuminate\Database\Eloquent\Model;

class MonthlyProductReport extends Model
{
    protected $connection = 'sysdata';
    protected $table = 'monthly_product_reports';

    protected $fillable = [
        'year',
        'month',
        'product_code',
        'product_name',
        'quantity',
        'total_amount',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'quantity' => 'decimal:3',
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope: 查詢特定年月
     */
    public function scopeYearMonth($query, $year, $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    /**
     * Scope: 查詢前 N 名
     */
    public function scopeTop($query, $limit = 10)
    {
        return $query->orderByDesc('total_amount')->limit($limit);
    }
}
