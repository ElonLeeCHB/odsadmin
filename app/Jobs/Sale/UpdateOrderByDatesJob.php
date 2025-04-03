<?php

namespace App\Jobs\Sale;

use App\Models\JobLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use App\Models\Setting\Setting;
use App\Jobs\Sale\UpdateOrderQuantityControlJob;
use App\Jobs\Sale\UpdateDailyRequisitionJob;
use App\Jobs\Sale\UpdateDailyReuirementJob;
use Illuminate\Support\Facades\DB;

/**
 * 訂單送達日期 delivery_date
 */

class UpdateOrderByDatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $lock = cache()->lock('sale-order-update-daily-requisition-job', 60);

        if ($lock->get()) {
            try {
                DB::transaction(function () {
                    $store_id = session('store_id', 1);
                    $setting_key = 'sale_order_queued_delivery_dates';
                    $setting = Setting::where('store_id', $store_id)->where('setting_key', $setting_key)->first();
        
                    $current_updated_at = $setting->updated_at ?? null;
                    $updated_dates = $setting->setting_value ?? [];
            
                    foreach ($updated_dates ?? [] as $updated_date) {
                        (new \App\Repositories\Eloquent\Sale\OrderDateLimitRepository)->refreshOrderedQuantityByDate($updated_date);
                        (new \App\Repositories\Eloquent\Sale\OrderDailyRequisitionRepository)->handleByDate(required_date:$updated_date, force_update:false, is_return:false);
                        (new \App\Repositories\Eloquent\Sale\OrderDailyRequirementRepository)->handleByDate(required_date:$updated_date, force_update:false, is_return:false);
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

            } catch (\Throwable $th) {
                DB::rollBack();
                (new \App\Libraries\LogLibrary)->logErrorNotRequest(['data' => $th->getMessage(), 'note' => 'App\Jobs\Sale\UpdateOrderByDates->handle()']);
            } finally {
                $lock->release();
            }
        }
    }
}