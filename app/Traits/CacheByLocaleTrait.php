<?php

namespace App\Traits;

use App\Caches\FileCustomCacheManager;

trait CacheByLocaleTrait
{
    /**
     * 產生 keyParts
     */
    protected static function keyParts(int $id, string $locale): array
    {
        return array_merge(static::$baseKeyParts, [$locale, "id-{$id}"]);
    }

    /**
     * 產生字串 key
     */
    protected static function key(int $id, string $locale): string
    {
        return implode(':', self::keyParts($id, $locale));
    }

    /**
     * 清除快取
     */
    public static function forget(int $id, string $locale): void
    {
        FileCustomCacheManager::clearStructured(self::keyParts($id, $locale));
    }

    /**
     * 強制刷新
     */
    public static function refresh(int $id, string $locale, int $ttl = 3600)
    {
        self::forget($id, $locale);
        return self::getById($id, $locale, $ttl);
    }
}
