<?php

namespace App\Enums;

enum TaxType: string
{
    case Taxable = 'taxable';   // 應稅
    case Exempt = 'exempt';     // 免稅
    case ZeroRate = 'zero_rate'; // 零稅率

    public function label(): string
    {
        return match ($this) {
            self::Taxable => '應稅',
            self::Exempt => '免稅',
            self::ZeroRate => '零稅率',
        };

        /*
        如果需要翻譯，可以使用 Laravel 的翻譯功能
        resources/lang/en/tax.php：
        return [
            'taxable' => 'Taxable',
            'exempt' => 'Exempt',
            'zero_rate' => 'Zero Rate',
        ];
        */
        // return trans('tax.' . $this->value);
    }
}