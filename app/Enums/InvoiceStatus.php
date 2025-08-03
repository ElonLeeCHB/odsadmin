<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Unpaid = 'unpaid';
    case Paid = 'paid';
    case Canceled = 'canceled';

    /**
     * 取得所有 value 值（['unpaid', 'paid', 'canceled']）
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * 取得所有 name 值（['Unpaid', 'Paid', 'Canceled']）
     */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    /**
     * 取得所有 name + value 配對（for 下拉選單等用途）
     */
    public static function options(): array
    {
        return array_map(fn($case) => [
            'name' => $case->name,
            'value' => $case->value,
        ], self::cases());
    }

    /**
     * 取得對應中文標籤（如需）
     */
    public function label(): string
    {
        return match ($this) {
            self::Unpaid => '未付款',
            self::Paid => '已付款',
            self::Canceled => '已取消',
        };
    }
}

/* 使用範例：
InvoiceStatus::values();
// ['unpaid', 'paid', 'canceled']

InvoiceStatus::names();
// ['Unpaid', 'Paid', 'Canceled']

InvoiceStatus::options();
// [
//     ['name' => 'Unpaid', 'value' => 'unpaid'],
//     ['name' => 'Paid', 'value' => 'paid'],
//     ['name' => 'Canceled', 'value' => 'canceled'],
// ]

InvoiceStatus::Unpaid->label();
*/