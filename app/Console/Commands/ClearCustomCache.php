<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Caches\FileCustomCacheManager;

class ClearCustomCache extends Command
{
    protected $signature = 'custom-cache:clear {prefix?}';
    protected $description = '刪除所有自訂快取檔案，可指定 prefix';

    public function handle()
    {
        $prefix = $this->argument('prefix');

        if ($prefix) {
            $this->info("刪除 prefix: {$prefix} 的所有快取...");
            FileCustomCacheManager::clearAll($prefix);
        } else {
            $this->info("刪除所有自訂快取...");
            FileCustomCacheManager::clearAll();
        }

        $this->info('完成');
    }
}
