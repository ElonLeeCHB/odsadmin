<?php

namespace App\Helpers\Classes;

use Illuminate\Support\Facades\Log;

class LogHelper
{
    public static function error(\Throwable $th, string $context = ''): void
    {
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? [];
        $class = $caller['class'] ?? 'N/A';
        $function = $caller['function'] ?? 'N/A';

        Log::error('[' . ($context ?? 'Exception') . '] ' . $th->getMessage(), [
            'exception' => get_class($th),
            'file' => $th->getFile(),
            'line' => $th->getLine(),
            'in' => "$class::$function",
        ]);
    }
}
