<?php

namespace App\Models\Reports;

use Illuminate\Database\Eloquent\Model;

class MonthlyOperationReport extends Model
{
    protected $connection = 'sysdata';
    protected $table = 'monthly_operation_reports';

    protected $fillable = [
        'year',
        'month',
        'order_total_amount',
        'order_count',
        'order_customer_count',
        'new_customer_count',
        'purchase_total_amount',
        'supplier_count',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'order_total_amount' => 'decimal:2',
        'order_count' => 'integer',
        'order_customer_count' => 'integer',
        'new_customer_count' => 'integer',
        'purchase_total_amount' => 'decimal:2',
        'supplier_count' => 'integer',
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
     * Scope: 查詢特定年份
     */
    public function scopeYear($query, $year)
    {
        return $query->where('year', $year)->orderBy('month');
    }

    /**
     * 取得該月前十大商品
     */
    public function topProducts($limit = 10)
    {
        return MonthlyProductReport::where('year', $this->year)
            ->where('month', $this->month)
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get();
    }

    /**
     * 關聯：該月所有商品銷售
     */
    public function productReports()
    {
        return $this->hasMany(MonthlyProductReport::class, 'year', 'year')
            ->where('month', $this->month);
    }
}
