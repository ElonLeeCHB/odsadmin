<?php

namespace App\Domains\ApiPos\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use App\Libraries\TranslationLibrary;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Auth;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = 'redirectTo';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function login(Request $request)
    {
        //前端欄位的 html name 必須是 username, 但值可以是 email 或 username
        $credentials = $request->only('username', 'password');
        $field = filter_var($credentials['username'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (auth()->attempt([$field => $credentials['username'], 'password' => $credentials['password']])) {
            $user = auth()->user();
            // $token = $user->createToken('posods')->plainTextToken;
            $token = $user->createToken('pos', ['pos'], now()->addDay())->plainTextToken;

            $guard = auth()->getDefaultDriver();
            echo "<pre>",print_r($guard,true),"</pre>";exit;

            return response()->json(['token' => $token], 200);
        }

        return response()->json(['error' => '帳號或密碼錯誤'], 401);
    }

    /**
     * Overwrite
     */
    protected function username()
    {
        $field = (filter_var(request()->email, FILTER_VALIDATE_EMAIL) || !request()->email) ? 'email' : 'username';
        request()->merge([$field => request()->email]);
        return $field;
    }
}
