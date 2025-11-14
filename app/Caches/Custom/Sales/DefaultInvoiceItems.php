<?php

namespace App\Caches\Custom\Sales;

use App\Models\Common\Term;
use App\Caches\FileCustomCacheManager;

class DefaultInvoiceItems
{
    private string $cacheKey = 'invoice_items';
    private int $ttlSeconds = 3600;

    /**
     * 取得快取資料，如果快取不存在，從資料庫讀取並存快取
     * 預設使用當前 locale
     *
     * @param string|null $locale
     * @return array
     */
    public function getData(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $key = "{$locale}/terms/code_keyed/{$this->cacheKey}";

        return FileCustomCacheManager::remember($key, $this->ttlSeconds, function () use ($locale) {
            return Term::where('taxonomy_code', $this->cacheKey)
                ->with(['translations' => function ($query) use ($locale) {
                    $query->where('locale', $locale)->select('term_id', 'name');
                }])
                ->get(['id'])
                ->map(function ($term) {
                    return [
                        'id' => $term->id,
                        'name' => $term->translations->first()?->name ?? '',
                        // 'price' => $term->price ?? null,
                    ];
                })
                ->values()
                ->toArray();
        });
    }

    /**
     * 生成所有語系的快取
     *
     * @param array $locales 例: ['zh-TW', 'en-US']
     */
    public function cacheAllLocales(array $locales): void
    {
        foreach ($locales as $locale) {
            $terms = Term::where('taxonomy_code', $this->cacheKey)
                ->with(['translations' => function ($query) use ($locale) {
                    $query->where('locale', $locale)->select('term_id', 'name');
                }])
                ->get(['id', 'price'])
                ->map(function ($term) {
                    return [
                        'id' => $term->id,
                        'name' => $term->translations->first()?->name ?? '',
                        'price' => $term->price ?? 0,
                    ];
                })
                ->values()
                ->toArray();

            $key = "{$locale}/terms/code_keyed/{$this->cacheKey}";
            FileCustomCacheManager::put($key, $terms, $this->ttlSeconds);
        }
    }
}
