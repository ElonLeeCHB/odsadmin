<?php

namespace App\Helpers;


// 假設是稅外價 1000
$result = TaxHelper::calculate(1000);
// 回傳：
/*
[
    'tax' => 50,
    'exclusive' => 1000,
    'inclusive' => 1050,
]
*/

// 假設是稅內價 1050
$result = TaxHelper::calculate(1050, taxRate: 5.0, taxIncluded: true);
/*
[
    'tax' => 50,
    'exclusive' => 1000,
    'inclusive' => 1050,
]
*/

class TaxHelper
{
    /**
     * @param float $amount 原始金額（可為稅內或稅外）
     * @param float $taxRate 稅率，例如 5%
     * @param bool $taxIncluded 是否為稅內價
     * @return array ['tax' => 稅額, 'exclusive' => 未稅金額, 'inclusive' => 含稅金額]
     */
    public static function calculate(float $amount, float $taxRate = 5.0, bool $taxIncluded = false): array
    {
        if ($taxIncluded) {
            // 稅內價格計算
            $exclusive = round($amount / (1 + $taxRate / 100), 0, PHP_ROUND_HALF_EVEN);
            $tax = $amount - $exclusive;
            $inclusive = $amount;
        } else {
            // 稅外價格計算
            $exclusive = $amount;
            $tax = round($exclusive * $taxRate / 100, 0, PHP_ROUND_HALF_EVEN);
            $inclusive = $exclusive + $tax;
        }

        return [
            'tax' => (int) $tax,
            'exclusive' => (int) $exclusive,
            'inclusive' => (int) $inclusive,
        ];
    }
}
