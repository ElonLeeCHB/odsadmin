<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use App\Events\SaleOrderSavedEvent;
use App\Jobs\Sale\SendOrderEmailJob;
use App\Jobs\Sale\UpdateOrderQuantityControlJob;
use App\Models\Setting\Setting;
use Carbon\Carbon;

class SaleOrderSavedListener
{
    public function handle(SaleOrderSavedEvent $event)
    {
        // Log::info('SaleOrderSavedListener', ['order_id' => $event->saved_order->order_id]);

        // 寄信 立刻排入佇列執行
        // if ($event->new_order->status === 'completed') {
        //     dispatch(new SendOrderEmailJob($event->new_order));
        // }

        // 計算當前訂單的控單數量
        dispatch(new UpdateOrderQuantityControlJob($event->saved_order->id));

        // 更新有異動的日期 delivery_date
            // 由 Job 根據 setting_key = sale_order_queued_delivery_date 再加上排程，執行真正的任務。
            $store_id = session('store_id', 1);
            $setting_key = 'sale_order_queued_delivery_dates';
            $setting = Setting::where('store_id', $store_id)->where('group', 'sale')->where('setting_key', $setting_key)->first();
            $updated_dates = $setting->setting_value ?? [];

            if (!empty($event->old_order->delivery_date ?? null)) {
                $updated_dates[] = Carbon::parse($event->old_order->delivery_date)->format('Y-m-d');
            }

            if (!empty($event->saved_order->delivery_date ?? null)) {
                $updated_dates[] = Carbon::parse($event->saved_order->delivery_date)->format('Y-m-d');
            }

            $updated_dates = json_encode(array_unique($updated_dates));

            if ($setting) {
                // 如果資料已存在，只更新 setting_value
                $setting->update(['setting_value' => $updated_dates]);
            } else {
                // 如果資料不存在，創建新記錄
                Setting::create([
                    'store_id' => $store_id,
                    'group' => 'sale',
                    'setting_key' => $setting_key,
                    'setting_value' => $updated_dates,
                    'is_autoload' => 0,
                    'is_json' => 1,
                    'comment' => '需要佇列處理的訂單日期'
                ]);
            }
        //
    }
}
