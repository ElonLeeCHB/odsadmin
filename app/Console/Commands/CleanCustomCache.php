<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Caches\FileCustomCacheManager;

class CleanCustomCache extends Command
{
    protected $signature = 'custom-cache:clean-expired {prefix?}';
    protected $description = '清理自訂快取的過期檔案，可指定 prefix';

    public function handle()
    {
        $prefix = $this->argument('prefix');

        if ($prefix) {
            $this->info("清理 prefix: {$prefix} 的過期快取...");
            FileCustomCacheManager::cleanExpired($prefix);
        } else {
            $this->info("清理所有自訂快取過期檔案...");
            FileCustomCacheManager::cleanExpired();
        }

        $this->info('完成');
    }
}
