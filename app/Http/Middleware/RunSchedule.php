<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class RunSchedule
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // $output  = Artisan::call('schedule:run');
            // $output = shell_exec('"C:/Servers/php/php83/php.exe" '.base_path().'/artisan app:fill-order-date-limit 2>&1');
            $output = shell_exec('"C:/Servers/php/php83/php.exe" ' . base_path() . '/artisan schedule:run');


            if($output != 'FillOrderDateLimit 執行完成'){
                throw new \Exception($output);
            }
            
            Log::info('RunSchedule Middleware 排程執行結果: ' . $output);
        } catch (\Exception $e) {
            Log::error('執行排程錯誤: ' . $e->getMessage());
        } 

        return $next($request);
    }
}
