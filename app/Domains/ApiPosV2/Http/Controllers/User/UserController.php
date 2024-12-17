<?php

namespace App\Domains\ApiPosV2\Http\Controllers\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use App\Domains\ApiPosV2\Services\User\UserService;
use Illuminate\Validation\ValidationException;
use App\Rules\ValidPassword;
use Illuminate\Support\Facades\Hash;


class UserController extends ApiPosController
{
    public function __construct(private Request $request, private UserService $UserService)
    {
        // if (method_exists(parent::class, '__construct')) {
        //     parent::__construct();
        // }
    }

    public function list()
    {

    }

    public function info()
    {

    }

    public function resetPassword($user_id)
    {
        try {
            $json = [];

            if(empty(request()->input('old_password'))){
                $json['error'] = '請輸入舊密碼';
            }

            if(empty(request()->input('password')) || empty(request()->input('confirm_password'))){
                $json['error'] = '請輸入新密碼及舊舊密碼';
            }

            if(request()->input('password') !== request()->input('confirm_password')){
                $json['error'] = '確認密碼不符合';
            }

            $password = request()->input('password');
            $old_password = request()->input('old_password');

            $error_message = '密碼必須至少 6 個字元，並且不能包含連續的數字或英文字母。';

            // 檢查是否包含非大小寫字母、數字或特殊符號 (排除中文、日文、韓文… )
            if (!preg_match('/^[A-Za-z0-9@#$%^&*!]+$/', $password)) {
                $json['error'] = $error_message;
            }

            // // 檢查是否包含至少一個大寫字母
            // if (!preg_match('/[A-Z]/', $password)) {
            //     $json['error'] = $error_message;
            // }

            // // 檢查是否包含至少一個小寫字母
            // if (!preg_match('/[a-z]/', $password)) {
            //     $json['error'] = $error_message;
            // }

            // 檢查是否包含連續4個字母
            if (preg_match('/(abcdefghijklmnopqrstuvwxyz|ABCDEFGHIJKLMNOPQRSTUVWXYZ){4,}/', $password)) {
                $json['error'] = $error_message;
            }

            // 檢查是否包含連續3個數字
            if (preg_match('/(0123456789){3,}/', $password)) {
                $json['error'] = $error_message;
            }

            // 檢查是否包含6個相同的數字
            if (preg_match('/(\d)\1{5}/', $password)) {
                $json['error'] = $error_message;
            }

            if(empty($json)){
                $user = $this->UserService->getUser(['equal_id' => $user_id]);

                if (!Hash::check($old_password, $user?->password)) {
                    $json['error'] = '密碼不正確';
                }
            }

            if(empty($json)){
                $user->password = Hash::make($password);
                $user->save();

                $json['success'] = '更新成功';

                return response()->json($json, 200, [], JSON_UNESCAPED_UNICODE)->header('Content-Type','application/json');
            }

            if(!empty($json)){
                return response()->json($json, 400, [], JSON_UNESCAPED_UNICODE)->header('Content-Type','application/json');
            }

        } catch (\Throwable $th) {
            return response()->json([
                'error' => '程式發生意外',
                'message' => $th->getMessage(),
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ], 500);
        }

    }






}
