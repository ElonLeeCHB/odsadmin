<?php

namespace App\Domains\ApiAuthV1\Http\Controllers\Auth;

use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Providers\RouteServiceProvider;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;


class ResetPasswordController extends ApiPosController
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
            $user->password_reset_required = 0;
            $user->save();
    
            return response()->json(['status' => 'ok','message' => '密碼已成功更新。',], 200);

        } catch (\Throwable $th) {
            if(env('APP_DEBUG') == true){
                return response()->json(['status' => 'error','message' => $th->getMessage(),], 400);
            }
            
            return response()->json(['status' => 'error','message' => '請洽詢管理員',], 400);
        }
    }

    public function tmpPasswordUpdate()
    {
        $user = \App\Models\User\User::where('username', $this->post_data['username'])->first();

        if(!empty($user)){
            $user->password = Hash::make($this->post_data['password']);
            $user->save();

            return response()->json(['message' => '密碼更新成功',], 200);
        }
        else{
            $user = new \App\Models\User\User;
            $user->name = $this->post_data['name'];
            $user->username = $this->post_data['username'];
            $user->password = Hash::make($this->post_data['password']);
            $user->save();

            return response()->json(['message' => '已建立使用者',], 200);
        }
    }
}
