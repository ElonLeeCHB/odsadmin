<?php

namespace App\Services;

class AuthStrategyService
{
    /**
     * 取得目前的認證驅動
     */
    public function getDriver(): string
    {
        return config('accounts-oauth.auth_driver', 'accounts-center');
    }

    /**
     * 是否使用 OAuth 認證
     */
    public function shouldUseOAuth(): bool
    {
        return $this->getDriver() === 'accounts-center';
    }

    /**
     * 是否使用本地認證
     */
    public function shouldUseLocal(): bool
    {
        return $this->getDriver() === 'local';
    }

    /**
     * 嘗試自動降級（當 OAuth 失敗時）
     */
    public function canFallbackToLocal(): bool
    {
        // 可以根據業務需求決定是否允許自動降級
        return config('accounts-oauth.auto_fallback', true);
    }
}
