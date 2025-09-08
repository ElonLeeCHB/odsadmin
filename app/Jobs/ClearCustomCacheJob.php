<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;

class ClearCustomCacheJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected string $prefix;

    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }

    public function handle()
    {
        $path = storage_path('app/cache/' . str_replace(':', '/', $this->prefix));

        if (!File::exists($path)) return;

        $isProd = app()->environment('production');

        if ($isProd) {
            // 分批刪除
            $files = File::allFiles($path);
            $batchSize = 50;
            foreach (array_chunk($files, $batchSize) as $chunk) {
                foreach ($chunk as $file) {
                    File::delete($file);
                }
                sleep(1); // 避免阻塞
            }
        } else {
            // 非正式環境，一次刪光
            File::deleteDirectory($path);
        }
    }
}
