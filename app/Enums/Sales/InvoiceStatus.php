<?php

namespace App\Enums\Sales;

enum InvoiceStatus: string
{
    case Pending = 'pending';
    case Issued = 'issued';
    case Voided = 'voided';

    /**
     * 取得所有 value 值（['pending', 'issued', 'voided']）
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * 取得所有 name 值（['Pending', 'Issued', 'Voided']）
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
            self::Pending => '待開立',
            self::Issued => '已開立',
            self::Voided => '已作廢',
        };
    }
}

/* 使用範例：
InvoiceStatus::values();
// ['pending', 'issued', 'voided']

InvoiceStatus::names();
// ['Pending', 'Issued', 'Voided']

InvoiceStatus::options();
// [
//     ['name' => 'Pending', 'value' => 'pending'],
//     ['name' => 'Issued', 'value' => 'issued'],
//     ['name' => 'Voided', 'value' => 'voided'],
// ]

InvoiceStatus::Pending->label();
// '待開立'
*/
