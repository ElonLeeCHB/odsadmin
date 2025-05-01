<?php

namespace App\Libraries\Database;

use Illuminate\Support\Facades\DB;

class MysqlLibrary
{
    // 重建 id 主索引，id 從當前最小 id 開始遞增
    public function rebuildPrimaryKey($tableName)
    {
        // 1. 檢查 id 是否被其他表引用
        $referencedTables = $this->getReferencedTables($tableName);

        if (count($referencedTables) > 0) {
            return "Error: The 'id' column is referenced by other tables: " . implode(', ', $referencedTables);
        }

        // 2. 取得當前最小 id
        $minId = DB::table($tableName)->min('id') ?? 1;

        // 3. 刪除可能已存在的新表
        DB::statement("DROP TABLE IF EXISTS `{$tableName}_new`");

        // 4. 建立新表，結構與舊表相同
        DB::statement("CREATE TABLE `{$tableName}_new` LIKE `{$tableName}`");

        // 5. 產生不包含 id 的欄位清單
        $columns = DB::select("SHOW COLUMNS FROM `{$tableName}` WHERE Field != 'id'");
        $columnsList = implode(', ', array_map(fn($col) => "`{$col->Field}`", $columns));

        // 6. 插入資料，從最小 id 開始遞增，使用變數模擬 row_number()
        DB::statement("SET @new_id := {$minId}");
        DB::statement("
            INSERT INTO `{$tableName}_new` (id, {$columnsList})
            SELECT @new_id := @new_id + 1 AS id, {$columnsList}
            FROM `{$tableName}` ORDER BY id
        ");

        // 7. 刪除舊表
        DB::statement("DROP TABLE IF EXISTS `{$tableName}`");

        // 8. 將新表改名為原來的表名
        DB::statement("RENAME TABLE `{$tableName}_new` TO `{$tableName}`");

        return "Primary key rebuilt from ID {$minId} successfully.";
    }

    // 檢查 id 是否被其他表格引用
    private function getReferencedTables($tableName)
    {
        $referencedTables = [];

        $query = "
            SELECT TABLE_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE REFERENCED_TABLE_NAME = ? 
              AND REFERENCED_COLUMN_NAME = 'id'
              AND TABLE_SCHEMA = DATABASE();
        ";

        $result = DB::select($query, [$tableName]);

        foreach ($result as $row) {
            $referencedTables[] = $row->TABLE_NAME;
        }

        return $referencedTables;
    }
}
