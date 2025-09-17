<?php

namespace App\Caches;

use Illuminate\Support\Facades\File;
use App\Jobs\ClearCustomCacheJob;

class FileCustomCacheManager
{
    protected static string $basePath;

    /** 初始化 basePath */
    protected static function init()
    {
        if (!isset(self::$basePath)) {
            self::$basePath = storage_path('app/cache');
        }
    }

    /** 建立或取得快取 */
    public static function remember(string $key, int $ttl, callable $callback)
    {
        self::init();
        $file = self::getPath($key);

        if (File::exists($file)) {
            $data = unserialize(File::get($file));
            if ($data['expires_at'] === 0 || $data['expires_at'] > time()) {
                return $data['value'];
            }
        }

        $value = $callback();
        self::put($key, $value, $ttl);
        return $value;
    }

    /** 單純寫入快取 */
    public static function put(string $key, $value, int $ttl = 0)
    {
        self::init();
        $file = self::getPath($key);
        File::ensureDirectoryExists(dirname($file));
        File::put($file, serialize([
            'value' => $value,
            'expires_at' => $ttl > 0 ? time() + $ttl : 0
        ]));
    }

    /** 永久快取 */
    public static function forever(string $key, $value)
    {
        self::put($key, $value, 0);
    }

    /** 取得完整檔案路徑 */
    protected static function getPath(string $key): string
    {
        return self::$basePath . '/' . str_replace(':', '/', $key);
    }

    /** 清除指定結構化快取（刪整個資料夾） */
    public static function clearStructured(array $keyParts): void
    {
        self::init();
        $keyParts = array_filter($keyParts);
        $path = self::$basePath . '/' . implode('/', $keyParts);

        if (!File::exists($path)) return;

        if (app()->environment('production')) {
            dispatch(new ClearCustomCacheJob($path));
        } else {
            File::deleteDirectory($path);
        }
    }

    /** 清除所有 product_id = uniqueKey 的快取檔案 */
    public static function clearByUniqueKey(string|int $uniqueKey, ?array $prefixParts = null)
    {
        self::init();
        $basePath = $prefixParts
            ? self::$basePath . '/' . implode('/', $prefixParts)
            : self::$basePath;

        if (!File::exists($basePath)) return;

        // 遞迴掃描所有檔案
        $files = File::allFiles($basePath);
        foreach ($files as $file) {
            if (basename($file) == (string)$uniqueKey) {
                File::delete($file);
            }
        }
    }

    /** 刪除過期快取，可指定 prefixParts */
    public static function cleanExpired(?array $prefixParts = null)
    {
        self::init();
        $path = $prefixParts
            ? self::$basePath . '/' . implode('/', $prefixParts)
            : self::$basePath;

        if (!File::exists($path)) return;

        $files = File::allFiles($path);
        foreach ($files as $file) {
            $data = unserialize(File::get($file));
            if (isset($data['expires_at']) && $data['expires_at'] > 0 && $data['expires_at'] <= time()) {
                File::delete($file);
            }
        }
    }

    public static function clearAll(string $prefix = ''): void
    {
        $path = self::getCachePath($prefix); // 取得快取資料夾路徑
        if (is_dir($path)) {
            $files = glob($path . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                } elseif (is_dir($file)) {
                    self::deleteDir($file);
                }
            }
        }
    }

    protected static function deleteDir(string $dir): void
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? self::deleteDir($path) : unlink($path);
        }
        rmdir($dir);
    }

    /** 取得快取資料夾路徑 */
    protected static function getCachePath(?string $prefix = null): string
    {
        self::init();
        if ($prefix) {
            // 將 prefix 中的 : 轉成資料夾結構
            $prefixPath = str_replace(':', '/', $prefix);
            return self::$basePath . '/' . $prefixPath;
        }

        return self::$basePath;
    }
}
