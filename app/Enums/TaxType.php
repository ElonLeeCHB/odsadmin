<?php

namespace App\Enums;

enum TaxType: string
{
    case Taxable = 'taxable';     // 應稅
    case Exempt = 'exempt';       // 免稅
    case ZeroRate = 'zero_rate';  // 零稅率

    /**
     * 取得所有 value 值（如 ['taxable', 'exempt', 'zero_rate']）
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * 取得所有 name 值（如 ['Taxable', 'Exempt', 'ZeroRate']）
     */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    /**
     * 中文標籤（用於 UI 顯示）
     */
    public function label(): string
    {
        return match ($this) {
            self::Taxable => '應稅',
            self::Exempt => '免稅',
            self::ZeroRate => '零稅率',
        };
    }

    /**
     * 取得 name + value 配對清單（如下拉選單）
     */
    public static function options(): array
    {
        return array_map(fn($case) => [
            'name' => $case->name,
            'value' => $case->value,
            'label' => $case->label(), // 可加可不加
        ], self::cases());
    }
}

/* 使用範例
    TaxType::values();
    // ['taxable', 'exempt', 'zero_rate']

    TaxType::names();
    // ['Taxable', 'Exempt', 'ZeroRate']

    TaxType::options();
    // [
    //     ['name' => 'Taxable', 'value' => 'taxable', 'label' => '應稅'],
    //     ['name' => 'Exempt', 'value' => 'exempt', 'label' => '免稅'],
    //     ['name' => 'ZeroRate', 'value' => 'zero_rate', 'label' => '零稅率'],
    // ]

    TaxType::ZeroRate->label();
    // '零稅率'
*/


/*
    如果需要翻譯，可以使用 Laravel 的翻譯功能
    resources/lang/en/tax.php：
    return [
        'taxable' => 'Taxable',
        'exempt' => 'Exempt',
        'zero_rate' => 'Zero Rate',
    ];
    
    // return trans('tax.' . $this->value);
*/