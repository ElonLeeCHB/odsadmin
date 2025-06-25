<?php

namespace App\Console\Commands\System;

use Illuminate\Console\Command;
use App\Models\SysData\Log;
use Carbon\Carbon;

class DeleteLogsOlderThan extends Command
{
    protected $signature = 'app:delete-logs {days : Only keep logs within this number of days}';

    protected $description = 'Delete logs older than the specified number of days ago (strictly greater)';

    public function handle()
    {
        $days = $this->argument('days');

        if (is_null($days)) {
            $this->error('Missing required argument: days');
            return 1;
        }

        $days = (int) $days;

        if ($days <= 0) {
            $this->error('Please provide a valid number of days greater than 0.');
            return 1;
        }

        $cutoffDate = now()->subDays($days)->endOfDay();

        $deleted = \App\Models\SysData\Log::where('created_at', '<=', $cutoffDate)->delete();

        $this->info("Deleted {$deleted} log(s) older than " . $days . ' days (before ' . $cutoffDate->toDateString() . ').');

        return 0;
    }

}
