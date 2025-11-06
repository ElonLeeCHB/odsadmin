<?php

namespace App\Console\Commands;

use App\Repositories\LogFileRepository;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * 壓縮舊日誌的 Artisan Command
 *
 * 使用方式：
 * - php artisan logs:compress             （壓縮上個月的日誌）
 * - php artisan logs:compress 2025-01     （壓縮指定月份）
 * - php artisan logs:compress --list      （列出所有日誌檔案）
 *
 * 定時任務設定（在 app/Console/Kernel.php）：
 * $schedule->command('logs:compress')->monthlyOn(1, '02:00');
 */
class CompressLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:compress
                            {month? : 月份 (格式: Y-m, 例如 2025-01)}
                            {--list : 列出所有日誌檔案}
                            {--auto : 自動壓縮上個月}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '壓縮舊日誌檔案成 ZIP 格式';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $logRepo = new LogFileRepository();

        // 列出所有日誌檔案
        if ($this->option('list')) {
            $this->listLogFiles($logRepo);
            return 0;
        }

        // 取得要壓縮的月份
        $month = $this->argument('month');

        if (!$month || $this->option('auto')) {
            // 自動壓縮上個月
            $month = Carbon::now()->subMonth()->format('Y-m');
            $this->info("自動壓縮上個月的日誌：{$month}");
        } else {
            // 驗證月份格式
            if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
                $this->error('月份格式錯誤，應為 Y-m (例如 2025-01)');
                return 1;
            }
        }

        // 確認壓縮
        if (!$this->option('auto') && !$this->confirm("確定要壓縮 {$month} 的日誌嗎？")) {
            $this->info('已取消');
            return 0;
        }

        // 開始壓縮
        $this->info("開始壓縮 {$month} 的日誌...");

        $result = $logRepo->compressMonthLogs($month);

        if ($result['success']) {
            $this->info("✅ " . $result['message']);
            $this->info("壓縮檔路徑：{$result['zip_path']}");
            $this->info("壓縮的檔案：");
            foreach ($result['files'] as $file) {
                $this->line("  - {$file}");
            }
        } else {
            $this->error("❌ " . $result['message']);
            return 1;
        }

        return 0;
    }

    /**
     * 列出所有日誌檔案
     */
    protected function listLogFiles(LogFileRepository $logRepo): void
    {
        $files = $logRepo->listLogFiles();

        if (empty($files)) {
            $this->info('沒有日誌檔案');
            return;
        }

        $this->info("日誌檔案列表：");
        $this->newLine();

        // 顯示表格
        $headers = ['檔名', '大小', '類型', '修改時間'];
        $rows = [];

        foreach ($files as $file) {
            $rows[] = [
                $file['filename'],
                $file['size'],
                $file['type'] === 'compressed' ? '📦 壓縮檔' : '📝 日誌',
                $file['modified'],
            ];
        }

        $this->table($headers, $rows);

        $this->newLine();
        $this->info("總共 " . count($files) . " 個檔案");
    }
}
