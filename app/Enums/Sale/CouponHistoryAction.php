<?php

// app/Enums/Sale/CouponDiscountType.php

namespace App\Enums\Sale;

enum CouponHistoryAction: string
{
    case Plus = 'plus';
    case Minus = 'minus';

    public function label(): string
    {
        return match ($this) {
            self::Plus => '增加',
            self::Minus => '減少',
        };
    }
}