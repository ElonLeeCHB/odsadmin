<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    public function passwordReset(Request $request)
    {
        try {
            $validated = $request->validate([
                'password' => 'required|string|min:8|confirmed', // 確認密碼必須與 password 一致
            ]);
    
            $user = auth()->user();
    
            // 更新密碼
            $user->password = Hash::make($validated['password']);
            $user->save();
    
            return response()->json(['status' => 'ok','message' => '密碼已成功更新。',], 200);

        } catch (\Throwable $th) {
            if(env('APP_DEBUG') == true){
                return response()->json(['status' => 'error','message' => $th->getMessage(),], 400);
            }
            
            return response()->json(['status' => 'error','message' => '請洽詢管理員',], 400);
        }
    }
}
