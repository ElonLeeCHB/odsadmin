<?php

namespace App\Enums\Sales;

enum InvoiceItemTaxType: int
{
    case Taxable = 0;    // 應稅
    case ZeroRate = 1;   // 零稅率
    case Exempt = 2;     // 免稅

    /**
     * 取得所有 value 值（[0, 1, 2]）
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * 取得所有 name 值（['Taxable', 'ZeroRate', 'Exempt']）
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
     * 取得對應中文標籤
     */
    public function label(): string
    {
        return match ($this) {
            self::Taxable => '應稅',
            self::ZeroRate => '零稅率',
            self::Exempt => '免稅',
        };
    }
}
