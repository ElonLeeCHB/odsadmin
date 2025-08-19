<?php

namespace App\Enums\Sale;

class CouponDiscountType
{
    const FIXED = 'fixed';
    const PERCENT = 'percent';

    public static function all(): array
    {
        return [
            self::FIXED,
            self::PERCENT,
        ];
    }

    public static function labels(): array
    {
        return [
            // self::FIXED   => __('coupon.discount_type.fixed'),
            // self::PERCENT => __('coupon.discount_type.percent'),
            self::FIXED   => '固定金額',
            self::PERCENT => '百分比',
        ];
    }

    public static function label(string $value): string
    {
        return self::labels()[$value] ?? $value;
    }
}
