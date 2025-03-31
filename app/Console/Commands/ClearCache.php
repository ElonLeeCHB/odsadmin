<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class ClearCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '清除所有快取';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->call('config:clear');
        $this->call('route:clear');
        $this->call('view:clear');
        $this->call('optimize:clear');
        $this->deleteCacheFilesByDays(90);

        $this->info('所有快取已成功清除！');
    }

    // 刪除超過指定天數的快取
    public function deleteCacheFilesByDays($days)
    {
        $directory = storage_path('framework/cache/data');
    
        $files = File::allFiles($directory);
    
        $ninetyDaysAgo = Carbon::now()->subDays($days);
    
        foreach ($files as $file) {
            $fileCreationTime = Carbon::createFromTimestamp(File::lastModified($file));
    
            if ($fileCreationTime->lt($ninetyDaysAgo)) {
                File::delete($file);
            }
        }
    }
}
