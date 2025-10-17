<?php

namespace App\Repositories\Eloquent;

use Illuminate\Support\Facades\DB;
use App\Traits\Model\EloquentTrait;

/*
$repo = new SupplierRepository();

// 新增
$repo->save(['name' => '中華食材']);

// PUT 全量更新
$repo->save($request->all(), $id, true);

// PATCH 部分更新
$repo->save(['phone' => '02-12345678'], $id, false);
*/

class Repository
{
    use EloquentTrait;

    /** @var \Illuminate\Database\Eloquent\Model */
    // public $initialized = false;
    public $model;
    public $table;
    public $zh_hant_hans_transform;

    public function __construct()
    {
        // 讓子類自行設定 $this->model
        if (method_exists($this, 'initialize')) {
            $this->initialize();
        }

        // 檢查 model 是否設定
        if (!$this->model) {
            throw new \Exception(static::class . ' 必須在 initialize() 設定 $this->model');
        }
    }

    public function save(array $data, $id = null, bool $isFullUpdate = false)
    {
        // ✅ 用通用的 model 取代 Supplier
        $modelClass = get_class($this->model);
        $row = $id ? $modelClass::find($id) : new $modelClass();

        if ($id && !$row) {
            throw new \Exception("{$modelClass} id={$id} not found");
        }

        // 修改
        if (!empty($id)){
            unset($data['creator_id']);
            unset($data['created_by']);
            unset($data['created_by_id']); // 推薦
        }

        // 新增或修改共用
        unset($data['created_at']); // 由系統自行決定
        unset($data['updated_at']); // 由系統自行決定

        // 刪除不可使用的欄位
        $savableColumns = OrmHelper::getSavableColumns($row);

        foreach ($data as $key => $value) {
            if (!in_array($key, $savableColumns)){
                unset($data[$key]);
            }
        }

        // 🔹 取得欄位結構 & 預設值
        $table = $row->getTable();
        $connection = $row->getConnectionName();
        $tableMeta = $this->getTableColumnsWithDefaults($table, $connection);

        // 🔹 根據更新模式處理
        if ($isFullUpdate) {
            $this->applyFullUpdate($row, $data, $tableMeta);
        } else {
            $this->applyPartialUpdate($row, $data, $tableMeta);
        }

        $row->save();

        return $row;
    }

    protected function getTableColumnsWithDefaults(string $table, string $connection = null)
    {
        $connection = $connection ?: config('database.default');
        $database = DB::connection($connection)->getDatabaseName();

        $columns = DB::connection($connection)->select("
            SELECT COLUMN_NAME as name, COLUMN_DEFAULT as default_value
            FROM information_schema.columns
            WHERE table_schema = ? AND table_name = ?
        ", [$database, $table]);

        $meta = [];
        foreach ($columns as $col) {
            $meta[$col->name] = ['default' => $col->default_value];
        }

        return $meta;
    }

    protected function applyFullUpdate($row, array $data, array $tableMeta)
    {
        foreach ($tableMeta as $field => $meta) {
            if (in_array($field, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $row->$field = array_key_exists($field, $data)
                ? $data[$field]
                : ($meta['default'] ?? null);
        }
    }

    protected function applyPartialUpdate($row, array $data, array $tableMeta)
    {
        foreach ($data as $field => $value) {
            if (array_key_exists($field, $tableMeta) &&
                !in_array($field, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                $row->$field = $value;
            }
        }
    }
}
