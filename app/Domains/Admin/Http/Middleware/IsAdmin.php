<?php

namespace App\Domains\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Auth;

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
        $user = Auth::user();

        if($user){
            $is_admin = $user->is_admin;
        }else{
            $is_admin = 0;
        }
        
        if($is_admin){
            return $next($request);
        }
        
        $route = route('lang.admin.login') . "?prev_url=" . url()->current();

        return redirect($route)->with('error',"There is something wrong!!");
    }
}
