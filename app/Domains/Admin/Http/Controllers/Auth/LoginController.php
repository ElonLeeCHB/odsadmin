<?php

namespace App\Domains\Admin\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use App\Libraries\TranslationLibrary;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

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

        $groups = [
            'admin/common/common',
            'admin/common/login',
        ];
        $this->lang = (new TranslationLibrary())->getTranslations($groups);
    }

    public function redirectTo()
    {
        return 'redirectTo';
    }

    /**
     * Overwrite
     */
    protected function authenticated(Request $request, $user)
    {
        $user->last_seen_at = Carbon::now()->format('Y-m-d H:i:s');
        $user->save();

        $prev_url = url()->previous();
        $query = parse_url($prev_url, PHP_URL_QUERY);
        parse_str($query, $params);

        if(!empty($params['prev_url']) ){
            return redirect($params['prev_url']);
        }

        return redirect(route('lang.admin.dashboard'));
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

    protected function sendFailedLoginResponse(Request $request)
    {
        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
            'password'=> [trans('auth.failed')],
        ]);
    }

    /**
     * Overwrite
     */
    public function showLoginForm()
    {
        $data['lang'] = $this->lang;

        $data['refresh_token_url'] = route('getToken');

        if(request()->has('access-key')){
            $data['action'] = route('lang.admin.login') . '?access-key=' . request()->query('access-key');
        }else{
            $data['action'] = route('lang.admin.login');
        }

        return view('admin.login', $data);
    }

    

    /**
     * Overwrite
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        if ($response = $this->loggedOut($request)) {
            return $response;
        }

        return redirect(route('lang.admin.login'));
    }
}
