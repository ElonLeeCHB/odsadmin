<?php

// app/Enums/Sale/CouponDiscountType.php

namespace App\Enums\Sale;

enum CouponDiscountType: string
{
    case Fixed = 'fixed';
    case Percent = 'percent';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => '固定金額',
            self::Percent => '百分比',
        };
    }
}