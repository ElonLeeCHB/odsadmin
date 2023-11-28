<?php

namespace App\Domains\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

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
            $route = route('lang.admin.login') . "?prev_url=" . url()->current();
            return redirect($route)->with('error_warning',"您沒有後台權限");
        }
    }
}
