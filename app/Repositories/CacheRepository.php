<?php

namespace App\Repositories;

/*
本檔用不到。請使用 D:\Codes\PHP\DTSCorp\huabing.tw\pos.huabing.tw\httpdocs\laravel\app\Caches\FileCustomCacheManager.php
*/

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CacheRepository
{
    protected string $basePath = 'cache';

    /**
     * 取得快取
     */
    public function get(
        string $locale,
        string $domain,
        string $cacheType,
        string $key,
        ?int $ttlSeconds = null
    ) {
        $path = $this->buildPath($locale, $domain, $cacheType, $key);

        if (!Storage::disk('local')->exists($path)) {
            return null;
        }

        $data = json_decode(Storage::disk('local')->get($path), true);

        if (!is_array($data)) {
            return null;
        }

        // 檢查 TTL
        if ($ttlSeconds && isset($data['_cached_at'])) {
            $cachedAt = Carbon::parse($data['_cached_at']);
            if (Carbon::now()->diffInSeconds($cachedAt) > $ttlSeconds) {
                $this->delete($locale, $domain, $cacheType, $key);
                return null;
            }
        }

        return $data['value'] ?? null;
    }

    /**
     * 寫入快取
     */
    public function put(
        string $locale,
        string $domain,
        string $cacheType,
        string $key,
        $value
    ) {
        $path = $this->buildPath($locale, $domain, $cacheType, $key);

        $directory = dirname($path);
        Storage::disk('local')->makeDirectory($directory);

        $payload = [
            '_cached_at' => Carbon::now()->toDateTimeString(),
            'value' => $value,
        ];

        Storage::disk('local')->put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * 刪除單一快取
     */
    public function delete(
        string $locale,
        string $domain,
        string $cacheType,
        string $key
    ) {
        $path = $this->buildPath($locale, $domain, $cacheType, $key);
        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }

    /**
     * 清除整個 cacheType
     * ex: zh-TW / terms / code_keyed
     */
    public function clearType(string $locale, string $domain, string $cacheType)
    {
        Storage::disk('local')->deleteDirectory(
            "$this->basePath/$locale/$domain/$cacheType"
        );
    }

    /**
     * 清除整個 domain
     * ex: zh-TW / terms
     */
    public function clearDomain(string $locale, string $domain)
    {
        Storage::disk('local')->deleteDirectory(
            "$this->basePath/$locale/$domain"
        );
    }

    /**
     * 清除 locale 下全部 cache
     */
    public function clearLocale(string $locale)
    {
        Storage::disk('local')->deleteDirectory(
            "$this->basePath/$locale"
        );
    }

    /**
     * 建構完整路徑
     *
     * @example cache/zh-TW/terms/code_keyed/myfile.json
     */
    protected function buildPath(
        string $locale,
        string $domain,
        string $cacheType,
        string $key
    ) {
        $safeKey = Str::slug($key, '_');
        return "$this->basePath/$locale/$domain/$cacheType/{$safeKey}.json";
    }
}
