<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Auth;

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
        try{
            //前端欄位的 html name 必須是 username, 但值可以是 email 或 username
            $credentials = $request->only('username', 'password');

            $user = User::where('username', $credentials['username'])->orWhere('email', $credentials['username'])->first();

            if ($user && Hash::check($credentials['password'], $user->password)) {

                $permissions = $user->permissions()->where('name', 'like', 'pos.%')->pluck('name')->toArray();
                $plainTextToken = $user->createToken('pos')->plainTextToken;

                //更新用戶端識別碼
                $ip = $request->ip();
                $userAgent = $request->header('User-Agent');
                $device_id = hash('sha256', $ip . $userAgent);

                $token = $user->tokens->last();
                $token->device_id = $device_id;
                $token->save();

                Session::put('device_id', $device_id);

                if (Hash::check($user->username, $user->password)) {
                    $json = [
                        'token' => $plainTextToken,
                        'permissions' => [],
                        'password_reset_required' => 1,
                        'message' => 'username 跟密碼不能一樣',
                    ];
                }
                elseif ($user->password_reset_required) {
                    $json = [
                        'token' => $plainTextToken,
                        'permissions' => [],
                        'password_reset_required' => 1,
                        'message' => '請重新設定帳號密碼',
                    ];
                }
                else{
                    $json = [
                        'token' => $plainTextToken,
                        'permissions' => $permissions,
                        'user_id' => $user->id,
                        'username' => $user->username,
                        'name' => $user->name,
                        'message' => '登入成功',
                    ];

                    //順便登入後台
                        $credentials = $request->only('username', 'password');
                        $field = filter_var($credentials['username'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
                        auth()->attempt([$field => $credentials['username'], 'password' => $credentials['password']]);
                    //
                }

                return response()->json($json, 200);
            }
            
            return response()->json(['error' => '帳號或密碼錯誤！'], 400);         

        } catch (\Exception $ex) {
            return $this->getErrorResponse($ex->getMessage(), '發生未知錯誤！', 500);
        }

        // //testman
        // $user = User::where('username', 'testman')->first();
        // $user->givePermissionTo(['pos.MainPage', 'pos.Member', 'pos.SalesOrder', 'pos.SalesOrderControl', 'pos.Financial']);
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

