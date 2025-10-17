<?php

namespace App\Libraries\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SnGenerator
{
    /**
     * 產生年月流水號
     *
     * @param string $table      資料表名稱
     * @param string $column     欄位名稱（要存流水號的欄位）
     * @param int    $yearDigits 年月格式長度（4=YYYY, 6=YYYYMM）
     * @param int    $snDigits   流水號位數
     * @return string
     * @throws \Exception
     */
    public static function generateYearMonthSn(string $table, string $column, int $yearDigits = 6, int $snDigits = 4): string
    {
        if ($yearDigits !== 4 && $yearDigits !== 6) {
            throw new \InvalidArgumentException("yearDigits 必須是 4 或 6");
        }
        
        $prefix = now()->format($yearDigits === 4 ? 'Y' : 'Ym');

        $maxTries = 5;
        $tries = 0;

        while ($tries < $maxTries) {
            $tries++;

            // 找出最大流水號
            $lastSn = DB::table($table)
                ->where($column, 'like', $prefix . '%')
                ->orderByDesc($column)
                ->value($column);

            $lastNumber = $lastSn ? intval(substr($lastSn, strlen($prefix))) : 0;
            $newNumber  = str_pad($lastNumber + 1, $snDigits, '0', STR_PAD_LEFT);

            $newSn = $prefix . $newNumber;

            try {
                // 先嘗試插入，若 UNIQUE 衝突會丟出例外
                DB::table($table)->insert([
                    $column => $newSn,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                return $newSn;
            } catch (\Exception $e) {
                continue;
            }
        }

        throw new \Exception("產生流水號失敗，請稍後再試");
    }
}
