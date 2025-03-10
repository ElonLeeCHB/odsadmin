<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User\User;
use App\Helpers\Classes\IpHelper;

/**
 * 略過 sanctum 檢查
 * 條件：
 *      如果是私有 ip
 *      APP_ENV = local或test
 *      Headers 有X-User-ID並且該使用者存在
 * 系統使用指定的 X-User-ID 做登入，不須驗證 sanctum token。
 */
class BypassSanctumMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $api_ip = $request->ip();

        if(IpHelper::isPrivateIp($api_ip) && app()->environment('local', 'testing')){
            // 允許透過 Header 使用 `X-User-ID` 做登入
            if ($request->hasHeader('X-User-ID') && $request->hasHeader('X-Developer-Key') && $request->header('X-Developer-Key') == env('ADMIN_DEVELOPER_KEY')) {
                if (app()->environment('local', 'testing')) {
                    $userId = $request->header('X-User-ID');
            
                    $user = User::find($userId);
        
                    if ($user) {
                        auth()->login($user);
                        auth()->setUser($user);
                        $request->setUserResolver(fn() => $user); 
                    }
                }
            }
        }

        return $next($request);
    }
}
