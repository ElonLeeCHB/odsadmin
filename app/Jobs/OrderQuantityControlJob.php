<?php

namespace App\Jobs;

use App\Models\JobLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

/**
 * 暫時用不到
 */

class OrderQuantityControlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $jobCode = 'OrderQuantityControl';
    private string $jobName = '訂單數量控制';

    public function handle(): void
    {
        // 取得 Job 的執行紀錄
        $JobLog = JobLog::firstOrCreate(
            ['job_name' => $this->jobName],
        );

        // 檢查最後執行時間是否超過 10 分鐘
        if ($JobLog->updated_at && Carbon::parse($JobLog->updated_at)->addMinutes(10)->isFuture()) {
            return; // 10 分鐘內不執行
        }

        // === 執行統計分析 ===
        \Log::info('執行 OrderStatisticsJob，進行訂單統計分析');

        // 更新最後執行時間
        $jobExecution->update(['last_executed_at' => now()]);
    }
}