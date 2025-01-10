<?php

namespace App\Traits\Model;

use Illuminate\Database\Eloquent\Builder;

trait FilterTrait
{
    /**
     * 應用網址參數作為查詢條件
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeApplyFilters(Builder $query, $params)
    {
        // 獲取所有的過濾參數
        $params = request()->query();

        foreach ($params as $key => $value) {
            // 處理 filter_ 開頭的參數
            if (str_starts_with($key, 'filter_')) {
                $column = substr($key, 7); // 去掉 'filter_'
                
                // 檢查是否包含範圍操作符 > 或 <
                if (str_starts_with($key, '>')) {
                    $val = trim(substr($value, 1));
                    $query->where($column, '>', $val);
                } else if (str_starts_with($key, '<')) {
                    $val = trim(substr($value, 1));
                    $query->where($column, '<', $val);
                } elseif (strpos($value, '*') !== false) {
                    // 如果有 '*'，則使用模糊匹配處理
                    if (str_starts_with($value, '*')) {
                        $pattern = substr($value, 1);
                        $query->whereRaw("{$column} REGEXP ?", ['.*' . preg_quote($pattern, '/') . '$']);
                    } elseif (str_ends_with($value, '*')) {
                        $pattern = substr($value, 0, -1);
                        $query->whereRaw("{$column} REGEXP ?", ['^' . preg_quote($pattern, '/') . '.*']);
                    } else {
                        $pattern = str_replace('*', '.*', $value);
                        $query->whereRaw("{$column} REGEXP ?", [$pattern]);
                    }
                } else {
                    // 沒有 '*' 或範圍符號時，執行模糊匹配
                    $query->where($column, 'like', '%' . $value . '%');
                }
            }
            // 處理 equal_ 開頭的參數
            elseif (str_starts_with($key, 'equal_')) {
                $column = substr($key, 6); // 去掉 'equal_'
                $query->where($column, '=', $value); // 精確匹配
            }
        }

        return $query;
    }
}
