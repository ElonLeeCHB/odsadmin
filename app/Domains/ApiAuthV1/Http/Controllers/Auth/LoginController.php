<?php

namespace App\Domains\ApiAuthV1\Http\Controllers\Auth;

use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\Carbon;
use App\Models\User\User;

class LoginController extends ApiPosController
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

    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');

        $user = User::where('username', $credentials['username'])
                    ->orWhere('email', $credentials['username'])
                    ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['error' => '帳號或密碼錯誤！'], 401);
        }

        $tokenName = $request->header('X-Client-Source', 'default');
        $plainTextToken = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'token' => $plainTextToken,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
            ]
        ]);
    }


    public function logout()
    {
        $device_id = Session::get('device_id') ?? '';
        
        request()->user()->tokens()->where('device_id', $device_id)->orWhere('expires_at', '<', Carbon::now())->delete();
        
        return response()->json(['message' => '已成功登出']);
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

