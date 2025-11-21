<?php

namespace App\Domains\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/*
本檔案用於檢查使用者是否具有管理員權限。
現在不再使用。由獨立的帳號系統判斷可否使用本系統。
本系統另外會有角色與權限做個別功能的控管。
*/

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $acting_user = auth()->user();
        app()->instance('acting_user', $acting_user);
        
        if(!empty($acting_user) && $acting_user->is_admin){
            return $next($request);
        }else{
            auth()->logout();

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            $url = route('lang.admin.login') . '?prev_url=' . url()->current();
            
            return redirect($url)->with('error_warning',"您沒有後台權限");
        }
    }
}
