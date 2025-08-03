<?php

namespace App\Enums;

enum OrderPaymentStatus: string
{
    case Pending = 'pending';     // 等待確認（或處理中，已付款未到帳)
    case Complete = 'complete';   // 完成
    case Canceled = 'canceled';   // 取消
    case Failed = 'failed';       // 失敗
    case Refunded = 'refunded';   // 已退款

    /**
     * 取得所有 value 值（['pending', 'complete', ...]）
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * 取得所有 name 值（['Pending', 'Complete', ...]）
     */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    /**
     * 取得 name + value + label 配對清單（for 選單等用途）
     */
    public static function options(): array
    {
        return array_map(fn($case) => [
            'name' => $case->name,
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }

    /**
     * 中文標籤（用於 UI 顯示）
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending  => '等待確認',
            self::Complete => '已完成',
            self::Canceled => '已取消',
            self::Failed   => '付款失敗',
            self::Refunded => '已退款',
        };
    }
}


/* 使用範例：
OrderPaymentStatus::values();
// ['pending', 'complete', 'canceled', 'failed', 'refunded']

OrderPaymentStatus::options();
// [
//   ['name' => 'Pending', 'value' => 'pending', 'label' => '等待付款'],
//   ...
// ]

OrderPaymentStatus::Pending->label();
// '等待付款'
*/