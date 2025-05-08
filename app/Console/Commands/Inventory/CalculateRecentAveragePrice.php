<?php

namespace App\Console\Commands\Inventory;

use Illuminate\Console\Command;
use App\Jobs\Inventory\CalculateRecentMaterialAveragePriceJob;

class CalculateRecentAveragePrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:inventory-calculate-average {--days=90 : Days to look back}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');

        CalculateRecentMaterialAveragePriceJob::dispatch($days);

        $this->info("已派發計算平均價格的任務，天數：{$days}");
    }
}
