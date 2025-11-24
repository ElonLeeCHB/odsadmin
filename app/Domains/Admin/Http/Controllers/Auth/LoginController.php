<?php

namespace App\Domains\Admin\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use App\Libraries\TranslationLibrary;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Huabing\AccountsOAuth\AccountsOAuthClient;
use Huabing\AccountsOAuth\Exceptions\AccountsConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

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

        if (!empty($params['prev_url'])) {
            return redirect($params['prev_url']);
        }

        return redirect(route('lang.admin.dashboard'));
    }

    /**
     * 智能判斷登入欄位類型
     *
     * 判斷邏輯：
     * 1. 純數字 + 09開頭 → mobile（台灣手機號碼格式）
     * 2. 符合 email 格式 → email
     * 3. 其他 → username（不含純數字、不含特殊符號）
     */
    protected function username()
    {
        $input = request()->email; // 表單欄位名稱是 email，但實際可能是各種類型

        // 判斷 1: 純數字 + 09開頭 → mobile
        if (ctype_digit($input) && str_starts_with($input, '09')) {
            $field = 'mobile';
        }
        // 判斷 2: 符合 email 格式 → email
        elseif (filter_var($input, FILTER_VALIDATE_EMAIL)) {
            $field = 'email';
        }
        // 判斷 3: 其他 → username
        else {
            $field = 'username';
        }

        // 將值 merge 到對應的欄位
        request()->merge([$field => $input]);

        return $field;
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        // 檢查是否有特定的錯誤訊息（例如來自 UserSync 的錯誤）
        $errorMessage = session('login_error');

        if ($errorMessage) {
            // 清除 session 中的錯誤訊息
            session()->forget('login_error');

            throw ValidationException::withMessages([
                $this->username() => [$errorMessage],
            ]);
        }

        // 預設的帳號密碼錯誤訊息
        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
            'password' => [trans('auth.failed')],
        ]);
    }

    /**
     * Overwrite
     */
    public function showLoginForm()
    {
        $data['lang'] = $this->lang;

        // $data['refresh_token_url'] = route('getToken');
        $data['refresh_token_url'] = route('lang.admin.getToken');

        if (request()->has('access-key')) {
            $data['action'] = route('lang.admin.login') . '?access-key=' . request()->query('access-key');
        } else {
            $data['action'] = route('lang.admin.login');
        }

        return view('admin.login', $data);
    }

    /**
     * 覆寫登入方法以整合 Accounts 中心認證
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        // 檢查登入嘗試次數
        if (
            method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)
        ) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }

        // 根據 AUTH_DRIVER 選擇認證方式
        $authDriver = config('accounts-oauth.auth_driver', 'accounts-center');

        if ($authDriver === 'accounts-center') {
            // 使用 Accounts 中心認證
            $result = $this->attemptOAuthLogin($request);
        } else {
            // 使用本地認證（備援模式）
            $result = $this->attemptLocalLogin($request);
        }

        if ($result) {
            return $this->sendLoginResponse($request);
        }

        // 登入失敗，增加嘗試次數
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    /**
     * 嘗試使用 Accounts 中心認證
     */
    protected function attemptOAuthLogin(Request $request): bool
    {

        try {
            $oauthClient = app(AccountsOAuthClient::class);

            $field = $this->username();
            $account = $request->input('email'); // email 欄位實際上可能是 username 或 email
            $password = $request->input('password');

            Log::info('POS Backend Login - Attempting OAuth login', [
                'account' => $account,
                'field' => $field,
                'system_code' => config('accounts-oauth.system_code'),
                'client_code' => config('accounts-oauth.client_code'),
            ]);

            // 呼叫 Accounts 中心進行驗證
            $oauthResult = $oauthClient->login($account, $password);

            Log::info('POS Backend Login - OAuth result', [
                'success' => $oauthResult['success'] ?? false,
                'error_code' => $oauthResult['error_code'] ?? null,
                'message' => $oauthResult['message'] ?? null,
                'status_code' => $oauthResult['status_code'] ?? null,
            ]);

            if (!$oauthResult['success']) {
                Log::warning('POS Backend Login - OAuth login failed', [
                    'account' => $account,
                    'result' => $oauthResult,
                ]);
                return false;
            }

            // 取得 OAuth 使用者資料
            $oauthUserData = $oauthResult['data']['user'] ?? null;

            if (!$oauthUserData) {
                Log::error('POS Backend Login - No user data in OAuth result');
                return false;
            }

            Log::info('POS Backend Login - OAuth user data received', [
                'full_data' => $oauthUserData,
                'user_id' => $oauthUserData['id'] ?? null,
                'email' => $oauthUserData['email'] ?? null,
                'mobile' => $oauthUserData['mobile'] ?? null,
            ]);

            // 同步使用者資料
            $user = $oauthClient->syncUser($oauthUserData);

            if (!$user) {
                Log::error('POS Backend Login - User sync failed');
                return false;
            }

            Log::info('POS Backend Login - User synced', [
                'local_user_id' => $user->id,
                'email' => $user->email,
            ]);

            // ✨ 同步密碼到本地作為備援
            $user->password = Hash::make($password);
            $user->save();

            // 建立 Session 登入
            Auth::login($user, $request->filled('remember'));

            return true;
        } catch (AccountsConnectionException $e) {
            // Accounts 中心連線失敗，嘗試本地認證（自動降級）
            Log::warning('POS Backend Login - Accounts connection failed, trying local auth', [
                'error' => $e->getMessage(),
            ]);

            if (config('accounts-oauth.auto_fallback', true)) {
                return $this->attemptLocalLogin($request);
            }

            return false;
        } catch (\Exception $e) {
            Log::error('POS Backend Login - Unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // 將異常訊息儲存到 session，以便在登入頁面顯示
            session()->flash('login_error', $e->getMessage());

            return false;
        }
    }

    /**
     * 嘗試使用本地認證
     */
    protected function attemptLocalLogin(Request $request): bool
    {
        $credentials = $this->credentials($request);

        Log::info('POS Backend Login - Attempting local login', [
            'credentials_field' => array_keys($credentials)[0] ?? 'unknown',
        ]);

        // 使用 Laravel 標準認證
        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $user = Auth::user();

            Log::info('POS Backend Login - Local auth successful', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return true;
        }

        Log::warning('POS Backend Login - Local auth failed');
        return false;
    }



    /**
     * 覆寫登出方法以整合 Accounts 中心登出
     */
    public function logout(Request $request)
    {
        // 執行標準登出流程
        $this->guard()->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        if ($response = $this->loggedOut($request)) {
            return $response;
        }

        return redirect(route('lang.admin.login'));
    }
}
