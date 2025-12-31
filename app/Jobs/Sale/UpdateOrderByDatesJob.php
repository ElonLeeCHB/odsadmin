<?php

namespace App\Jobs\Sale;

use App\Models\JobLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Setting\Setting;
use Illuminate\Support\Facades\DB;

/**
 * 訂單送達日期 delivery_date
 * 定期執行，例如每20分鐘或是每小時。
 */

class UpdateOrderByDatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // 執行時鎖定本任務，防止重複執行
        $lock = cache()->lock('sale-order-update-daily-requisition-job', 60);

        if ($lock->get()) {
            try {
                DB::transaction(function () {
                    $store_id = session('store_id', 1);
                    $setting_key = 'sale_order_queued_delivery_dates';
                    $setting = Setting::where('store_id', $store_id)->where('setting_key', $setting_key)->first();

                    $current_updated_at = $setting->updated_at ?? null;
                    $updated_dates = $setting->setting_value ?? [];
                    $updated_dates = array_unique($updated_dates); // 移除重複
                    sort($updated_dates, SORT_STRING);

                    // 控單表 order_date_limits。一次處理多日期
                    (new \App\Repositories\Eloquent\Sale\OrderDateLimitRepository)->resetFutureOrders(delivery_dates: $updated_dates);

                    foreach ($updated_dates ?? [] as $updated_date) {
                        // (new \App\Repositories\Eloquent\Sale\OrderDateLimitRepository)->refreshOrderedQuantityByDate($updated_date);

                        // 備料表。一次處理單一日期
                        (new \App\Repositories\Eloquent\Sale\OrderDailyRequisitionRepository)->getStatisticsByDate(required_date:$updated_date, force_update:false, is_return:false);
                    }

                    $setting = Setting::where('store_id', $store_id)->where('setting_key', $setting_key)->first();
                    $new_updated_at = $setting->updated_at;

                    if ($current_updated_at != $new_updated_at){
                        DB::rollBack();
                        return;
                    }

                    $setting->setting_value = '';
                    $setting->save();
                });

                (new \App\Repositories\LogToDbRepository)->log(['data'=>'','note'=>'UpdateOrderByDatesJob 執行成功']);

            } catch (\Throwable $th) {
                DB::rollBack();
                (new \App\Repositories\LogToDbRepository)->logErrorAfterRequest(['data' => $th->getMessage(), 'note' => 'App\Jobs\Sale\UpdateOrderByDates->handle()']);
            } finally {
                $lock->release();
            }
        }
    }
}