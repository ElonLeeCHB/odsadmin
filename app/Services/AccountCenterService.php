<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AccountCenterService
{
    protected $connection;

    public function __construct()
    {
        // 建立帳號中心資料庫連線
        config(['database.connections.accounts' => [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'huabing_accounts_std',
            'username' => 'root',
            'password' => '123456',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ]]);

        $this->connection = DB::connection('accounts');
    }

    /**
     * 從帳號中心查詢使用者資料（透過 code）
     *
     * @param string $code
     * @return object|null
     */
    public function getUserByCode(string $code)
    {
        return $this->connection->table('users')
            ->where('code', $code)
            ->first();
    }

    /**
     * 驗證並取得帳號中心使用者資料
     *
     * @param string $code 使用者編號
     * @return array
     * @throws \Exception
     */
    public function fetchUserData(string $code): array
    {
        $accountUser = $this->getUserByCode($code);

        if (!$accountUser) {
            throw new \Exception("帳號中心查無此使用者編號：{$code}，請先至帳號中心建立使用者資料");
        }

        // 回傳需要同步的欄位
        return [
            'code' => $accountUser->code,
            'name' => $accountUser->name ?? '',
            'email' => $accountUser->email ?? null,
            'mobile' => $accountUser->mobile ?? null,
            'telephone' => $accountUser->telephone ?? null,
            'employee_code' => $accountUser->employee_code ?? null,
            // 可根據需求增加其他欄位
        ];
    }

    /**
     * 檢查 code 是否存在於帳號中心
     *
     * @param string $code
     * @return bool
     */
    public function codeExists(string $code): bool
    {
        return $this->connection->table('users')
            ->where('code', $code)
            ->exists();
    }
}
