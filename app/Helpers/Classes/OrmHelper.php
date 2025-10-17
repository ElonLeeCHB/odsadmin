<?php

namespace App\Helpers\Classes;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrmHelper
{
    public static function prepare($query, &$params = [])
    {
        self::select($query, $params);
        self::applyFilters($query, $params);
        self::sortOrder($query, $params);
    }
    
    // 處理查詢欄位
    public static function applyFilters(EloquentBuilder $query, &$params = [])
    {
        $model = $query->getModel();
        $table = self::getPrefix($model) . $model->getTable();
        $table_columns = self::getTableColumns($table);

        if (!empty($params['select'])){
            $query->select($params['select']);
        }

        // is_active
            // 如果不存在 equal_is_active ，預設=1
            if(!isset($params['equal_is_active'])){
                $params['equal_is_active'] = 1;
            }
            // 存在 equal_is_active
            else {
                // 值 = '*', 取消此條件
                if($params['equal_is_active'] == '*'){
                    unset($params['equal_is_active']);
                } 
                else {
                    $params['equal_is_active'] = (int) $params['equal_is_active'];
                }
            }
        //

        // 建構查詢
            foreach ($params ?? [] as $key => $value) {
                $column = preg_replace('/^(filter_|equal_)/', '', $key);

                // 查詢本表欄位
                if (in_array($column, $table_columns) && !in_array($column, $model->translation_keys ?? [])){
                    self::filterOrEqualColumn($query, $key, $value);
                }

                // 翻譯欄位另外用 whereHas 
                else if(in_array($column, $model->translation_keys ?? [])){
                    $params['whereHas']['translation'][$key] = $params[$key];
                    unset($params[$key]);
                    continue;
                }
            }
        //

        // 查詢翻譯欄位
            self::setWhereHas($query, $params);
        //
    }

    // 選擇本表欄位。不包括關聯欄位。
    public static function select($query, &$params, $table = '')
    {
        if (!empty($params['select'])) {
            $select = $params['select'];
            $model = $query->getModel();
            $table = self::getPrefix($model) . $model->getTable();
            $table_columns = self::getTableColumns($table);

            // 取交集
            $select = array_intersect($select, $table_columns);

            $query = $query->select(array_map(function($field) use ($table) {
                return "{$table}.{$field}";
            }, $select));
        }
    }

    public static function filterOrEqualColumn($query, $key, $value)
    {
        $column = preg_replace('/^(filter_|equal_)/', '', $key);

        // 如果沒有指定開頭，一律加上 filter_
        if (!str_starts_with($key, 'filter_') && !str_starts_with($key, 'equal_')) {
            $key = 'filter_';
        }

        if (str_starts_with($key, 'filter_')) {
            $value = trim($value);
    
            if(strlen($value) == 0){
                return;
            }
    
            // escapes Ex. phone number (123)456789 => \(123\)456789
            $arr = ['(', ')', '+'];
            foreach ($arr as $symble) {
                if(str_contains($value, $symble)){
                    $value = str_replace($symble, '\\'.$symble, $value);
                }
            }
    
            $operators = ['=','<','>','*'];
    
            // *foo woo* => foo woo
            if(str_starts_with($value, '*')  && str_ends_with($value, '*') ){
                $value = substr($value,1);
                $value = substr($value,0,-1);
            }
    
            $has_operator = false;
            foreach ($operators as $operator) {
                if(str_starts_with($value, $operator) != false || str_ends_with($value,'*')){
                    $has_operator = true;
                    break;
                }
            }
    
            // No operator
            if($has_operator == false){
                // 'foo woo' => 'foo*woo'
                $value = str_replace(' ', '*', $value);
                // 'foo*woo' => 'foo(.*)woo'
                $value = str_replace('*', '(.*)', $value);
                $query->where($column, 'REGEXP', $value);
                return $query;
            }
    
            // '=' Empty or null
            if($value === '='){
                $query->where(function ($query) use($column) {
                    $query->orWhereNull($column);
                    $query->orWhere($column, '=', '');
                });
            }
            // '=foo woo' Completely Equal 'foo woo'
            else if(str_starts_with($value, '=') && strlen($value) > 1){
                $value = substr($value,1); // 'foo woo'
                $query->where($column, '=', $value);
            }
            // '<>' Not empty or not null
            else if($value === '<>'){
                $query->where(function ($query) use($column) {
                    $query->orWhereNotNull($column);
                    $query->orWhere($column, '<>', '');
                });
            }
            // '<>foo woo' Not equal 'foo woo'
            else if(str_starts_with($value, '<>') && strlen($value) > 2){
                $value = substr($value,2); // 'foo woo'
                $query->where($column, '<>', $value);
            }
            // '<123' Smaller than 123
            else if(str_starts_with($value, '<') && strlen($value) > 1){
                $value = substr($value,1); // '123'
                $query->where($column, '<', $value);
            }
            // '>123' bigger than 123
            else if(str_starts_with($value, '>') && strlen($value) > 1){
                $value = substr($value,1);
                $query->where($column, '>', $value);
            }
            // '*foo woo'
            else if(substr($value,0, 1) == '*' && substr($value,-1) != '*'){
                $value = str_replace(' ', '(.*)', $value);
                $value = "(.*)".substr($value,1).'$';
                $query->where($column, 'REGEXP', "$value");
            }
            // 'foo woo*'
            else if(substr($value,0, 1) != '*' && substr($value,-1) == '*'){
                $value = substr($value,0,-1); // foo woo
                $value = str_replace(' ', '(.*)', $value); //foo(.*)woo
                $value = '^' . $value . '(.*)';
                $query->where($column, 'REGEXP', "$value");
            }
        }
        else if (str_starts_with($key, 'equal_')) {
            $value = trim($value);
            $query->where($column, $value);
        }
    }

    public static function setWhereHas($query, $params = [])
    {
        // 只有 EloquentBuilder 才能使用 whereHas
        if($query instanceof EloquentBuilder && !empty($params['whereHas'])){
            foreach ($params['whereHas'] as $relation_name => $relation) {
                $query->whereHas($relation_name, function($qry) use ($relation) {
                    foreach ($relation as $key => $value) {
                        self::filterOrEqualColumn($qry, $key, $value);
                    }
                });
            }
        }
    }
    
    // 排序。可以使用本表欄位、關聯欄位
    public static function sortOrder($query, $params)
    {
        $sort = $params['sort'] ?? null;
        $order = $params['order'] ?? null;

        if($query instanceof EloquentBuilder){
            $masterModel = $query->getModel();
            $mainTable = self::getPrefix($masterModel) . $masterModel->getTable();
            $foreign_key = $masterModel->getForeignKey();
            $table_columns = self::getTableColumns($mainTable);

            if(empty($sort) && in_array('id', $table_columns)){
                $sort = 'id';
            }

            if (!empty($params['order'])){
                $order = $params['order'];
            } else {
                $order = 'DESC';
            }

            // 本表欄位
            if (in_array($sort, $table_columns)) {
                $query->orderBy("{$mainTable}.{$sort}", $order);
            }
            // 翻譯欄位
            else if (!empty($masterModel->translation_keys)){
                $translation_table = $masterModel->getTranslationTable() ?? '';
                $query->orderByRaw("(SELECT {$sort} FROM {$translation_table} 
                                    WHERE {$translation_table}.{$foreign_key} = {$mainTable}.id 
                                    AND {$translation_table}.locale = ?) {$order}", 
                                    [app()->getLocale()]);
            }
        }
    }

    // 取得資料集
    public static function getResult($query, $params, $debug = 0)
    {
        if($debug){
            self::showSqlContent($query);
        }

        $result = [];

        if(isset($params['first']) && $params['first'] = true){
            if(empty($params['pluck'])){
                $result = $query->first();
            }else{
                $result = $query->pluck($params['pluck'])->first();
            }
        }else{

            // Limit
            if(isset($params['limit'])){
                $limit = (int) $params['limit'];
            }else{
                $limit = (int) config('settings.config_admin_pagination_limit');

                if(empty($limit)){
                    $limit = 10;
                }
            }

            // Pagination default to true
            if(isset($params['pagination']) ){
                $pagination = (boolean)$params['pagination'];
            }else{
                $pagination = true;
            }

            // Get result
            if($pagination == true && $limit > 0){  // Get some result per page
                $result = $query->paginate($limit);
            }
            else if($pagination == true && $limit == 0){  // get all but keep LengthAwarePaginator
                $result = $query->paginate($query->count());
            }
            else if($pagination == false && $limit != 0){  // Get some result without pagination
                $result = $query->limit($limit)->get();
            }
            else if($pagination == false && $limit == 0){  // Get all result
                $result = $query->get();
            }
            
            // Pluck
            if(!empty($params['pluck'])){
                $result = $result->pluck($params['pluck']);
            }

            if(!empty($params['keyBy'])){
                $result = $result->keyBy($params['keyBy']);
            }
        }

        return $result;
    }
    
    public static function deleteKeys($rows, $deleteKeys)
    {
        // 定義刪除鍵的邏輯
        $mapFunction = function ($row) use ($deleteKeys) {
            foreach ($deleteKeys as $deleteKey) {
                if (is_array($row) && array_key_exists($deleteKey, $row)) {
                    unset($row[$deleteKey]);
                } elseif (is_object($row) && isset($row->$deleteKey)) {
                    unset($row->$deleteKey);
                }
            }
            return $row;
        };
    
        // LengthAwarePaginator 結構
        if ($rows instanceof LengthAwarePaginator) {
            $realRows = $rows->get('data');
            return $realRows->map($mapFunction);
        }

        // 如果 $rows 是 Collection 或 Eloquent\Collection，使用 map()
        if ($rows instanceof \Illuminate\Support\Collection || $rows instanceof \Illuminate\Database\Eloquent\Collection) {
            
            return $rows->map($mapFunction);
        }
    
        // 如果 $rows 是陣列，使用 array_map()
        if (is_array($rows)) {
            return array_map($mapFunction, $rows);
        }
    
        // 如果 $rows 不是 Collection、陣列或 Paginator，直接回傳原值
        return $rows;
    }

    // 顯示 sql 內容並中斷
    public static function showSqlContent($query, $exit = 1)
    {
        $sqlstr = str_replace('?', "'?'", $query->toSql());

        $bindings = $query->getBindings();

        if(!empty($bindings)){
            $arr['statement'] = vsprintf(str_replace('?', '%s', $sqlstr), $query->getBindings());
        }else{
            $arr['statement'] = $query->toSql();
        }

        $arr['original'] = [
            'toSql' => $query->toSql(),
            'bidings' => $query->getBindings(),
        ];

        if($exit == 1 ){
            echo "<pre>".print_r($arr , 1)."</pre>"; exit;
        }else{
            return "<pre>".print_r($arr , 1)."</pre>";
        }
    }

    // public static function arrayToStdObjects($data)
    // {
    //     if (is_array($data)) {
    //         return (object) array_map([self::class, 'arrayToStdObjects'], $data);
    //     }
    //     return $data;
    // }

    // 將資料集 rows 轉為標準物件的資料集
    // $products = Product::where()->get() 可以使用 $products->toArray()，但這樣整串都是陣列。
    // 使用本函數 $products = DataHelper::toCleanCollection($products) 每一筆資料會是標準物件。
    // 原因：
    // 1.echo "<pre>",print_r($products,true),"</pre>";exit; 如果是Eloquent Collection有很多不需要知道的東西。
    // 2.不想用陣列是因為它會有方括號跟單引號覺得麻煩。$product['price'] <=> $product->price
    public static function toCleanCollection($data)
    {
        if ($data instanceof LengthAwarePaginator) {
            return $data->setCollection(
                $data->getCollection()->map(fn($item) => self::toCleanObject($item))
            );
        }

        else if (is_object($data)){
            $object = [];

            foreach ($data as $row) {
                $object[] = self::toCleanObject($row);
            }

            return $object;
        }
    }

    // 將單一模型轉換為清潔版物件
    public static function toCleanObject($input)
    {
        if (is_string($input)) {
            return $input; // 如果是字串，直接返回
        }

        $object = new \stdClass();

        // 先將模型轉換為陣列
        $data = is_object($input) && method_exists($input, 'toArray') ? $input->toArray() : (array) $input;
        
        // 將陣列轉換為 stdClass 並過濾不必要的欄位
        foreach ($data as $key => $value) {
            // 排除不必要的欄位（例如 Eloquent 模型的元資料欄位）
            if (in_array($key, ['incrementing', 'exists', 'wasRecentlyCreated', 'timestamps', 'usesUniqueIds', 'preventsLazyLoading', 'guarded', 'fillable'])) {
                continue;
            }

            $object->{$key} = $value;

            // // 處理關聯資料（遞回處理）
            // if (is_array($value)) {
            //     $object->{$key} = self::toCleanCollection(collect($value));
            // } 
            // else if (is_object($value)) {
            //     $object->{$key} = self::toCleanObject($value);
            // } else {
            //     $object->{$key} = $value;
            // }
        }

        unset($object->translation);
        unset($object->metas);

        return $object;
    }
    public static function setTranslationToRow($row)
    {
        if ($row->relationLoaded('translation')) {
            foreach ($row->translation as $column => $value) {
                if (in_array($column, $row->translation_keys)){
                    $row->{$column} = $value;
                }
            }
        }
    }

    public static function setTranslationToRows($rows)
    {
        foreach ($rows as $row) {
            self::setTranslationToRow($row);
        }

        return $rows;
    }

    public static function setMetasToRow($row)
    {
        if ($row->relationLoaded('metas')) {
            foreach ($row->metas as $meta) {
                if (in_array($meta->meta_key, $row->meta_keys)){
                    $row->{$meta->meta_key} = $meta->meta_value;
                }
            }
        }
    }

    public static function setMetasToRows($rows)
    {
        foreach ($rows as $row) {
            self::setMetasToRow($row);
        }

        return $rows;
    }

    public static function setTranslationAndMetasToRows($rows)
    {
        self::setTranslationToRows($rows);
        self::setMetasToRows($rows);
    }

    // $rows
    public static function unsetArrayOfRowsArray($rows)
    {
        foreach ($rows as $row) {
            foreach ($row as $key => $value) {
                if(is_array($value)){
                    unset($rows);
                }
            }
        }

    }

    public static function prepareColumnWithoutGuarded($model, array $data)
    {
        $fillable = $model->getFillable();

        if (empty($fillable)) {
            $fillable = array_keys($model->getAttributes());
        }
        
        $guarded = $model->getGuarded();
        
        foreach ($data as $column => $value) {
            // 先確認欄位是 fillable（白名單允許），再確認欄位不是 guarded（黑名單禁止）
            if (in_array($column, $fillable) && !in_array($column, $guarded)) {
                $model->$column = $value;
            }
        }

        return $model;
    }

    public static function getPrefix(Model $row)
    {
        $connection = $row->getConnectionName();

        return config("database.connections.{$connection}.prefix", '');
    }

    public static function getTableColumns($table, $connection_name = null)
    {
        $cache_key = 'cache/table_columns/' . $table . '.serialized.txt';

        return DataHelper::remember($cache_key, 60*60*24*90, 'serialize', function() use ($table, $connection_name){

            if(empty($connection_name) ){
                $table_columns = DB::getSchemaBuilder()->getColumnListing($table); // use default connection
            }else{
                $table_columns = DB::connection($connection_name)->getSchemaBuilder()->getColumnListing($table);
            }

            return $table_columns;
        });  
    }

    // 綜合判斷 $guarded, $fillable 
    public static function getSavableColumns(Model $row)
    {
        $table = $row->getTable();
        $table_columns = Schema::getColumnListing($table);
        $fillable = $row->getFillable(); // getFillable(): laravel 內建

        // 不存在 $fillable，則使用資料表全部欄位, 然後排除 $guarded
        if (empty($fillable)) {
            $result = array_diff($table_columns, $row->getGuarded());
        }
        // 存在 $fillable: 使用資料表全部欄位，但是排除 $guarded
        else {
            $result = array_diff($fillable, $row->getGuarded());
        }

        return $result;
    }

    public static function getModelTableColumns(Model $model)
    {
        $table = $model->getTable();

        return self::getTableColumns($table);
    }

    public static function findIdOrFailOrNew(EloquentBuilder $query, $id = null)
    {
        // 如果有 id，就嘗試找資料
        if (!empty($id)) {
            $row = $query->findOrFail($id); // 找不到會丟出 ModelNotFoundException
        } else {
            $row = $query->getModel()->newInstance(); // 回傳一個新的 Model 實例
        }

        return $row ?? null;
    }

    // 2025-10-16 新增
    public static function save(string $model_name, array $data, $id = null, $params = [])
    {
        // 確保類別存在
        if (!class_exists($model_name)) {
            throw new \Exception("Model class {$model_name} not found");
        }

        // 動態建立或查找 Model
        if (empty($id)) {
            $row = new $model_name();
        } else {
            $row = $model_name::find($id);

            if (empty($row)) {
                throw new \Exception("{$model_name} id={$id} not found");
            }
        }

        // 修改
        if (!empty($id)) {
            unset($data['creator_id']);
            unset($data['created_by']);
            unset($data['created_by_id']); // 推薦
        }

        // 新增或修改共用
        unset($data['created_at']); // 由系統自行決定
        unset($data['updated_at']); // 由系統自行決定

        // 刪除不可使用的欄位
        $savableColumns = self::getSavableColumns($row);

        foreach ($data as $key => $value) {
            if (!in_array($key, $savableColumns)) {
                unset($data[$key]);
            }
        }

        // 🔹 取得欄位結構 & 預設值
        $table = $row->getTable();
        $connection = $row->getConnectionName();
        $tableMeta = self::getTableColumnsWithDefaults($table, $connection);

        $table_columns = array_keys($tableMeta);

        // 如果有 $params['operator_id']，再依序判斷資料表欄位是否存在
        if (!empty($params['operator_id'])) {
            $operatorId = $params['operator_id'];

            // 優先順序設定：建立者
            $creatorFields = ['created_by_id', 'created_by', 'creator_id']; // 取其一
            foreach ($creatorFields as $field) {
                if (in_array($field, $table_columns)) {
                    $row->$field = $operatorId;
                    break;
                }
            }

            // 優先順序設定：修改者
            $updaterFields = ['updated_by_id', 'updated_by', 'updater_id', 'modifier_id', 'modified_by', 'modified_by_id']; // 取其一
            foreach ($updaterFields as $field) {
                if (in_array($field, $table_columns)) {
                    $row->$field = $operatorId;
                    break;
                }
            }
        }

        // 🔹 根據更新模式處理
        $params['isFullUpdate'] = $params['isFullUpdate'] ?? false;

        if ($params['isFullUpdate']) {
            self::applyFullUpdate($row, $data, $tableMeta);
        } else {
            self::applyPartialUpdate($row, $data, $tableMeta);
        }

        $row->save();

        return $row;
    }

    // 2025-10-16 新增
    protected static function getTableColumnsWithDefaults(string $table, $connection = null)
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

    // 2025-10-16 新增
    protected static function applyFullUpdate($row, array $data, array $tableMeta)
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

    // 2025-10-16 新增
    protected static function applyPartialUpdate($row, array $data, array $tableMeta)
    {
        foreach ($data as $field => $value) {
            if (
                array_key_exists($field, $tableMeta) &&
                !in_array($field, ['id', 'created_at', 'updated_at', 'deleted_at'])
            ) {
                $row->$field = $value;
            }
        }
    }

    // 2025-10-16 新增
    // 儲存 Model 的 Meta 資料
    // 用於處理有 metas 關聯的 Model，根據 meta_keys 進行 upsert 或刪除
    public static function saveRowMetaData(Model $row, array $data)
    {
        // 檢查 Model 是否有 getMetaModel 方法
        if (!empty($row->meta_model)) {
            $meta_model_name = $row->meta_model;
        } else {
            $meta_model_name = get_class($row) . 'Meta';
        }

        if (class_exists($meta_model_name)) {
            $meta_model = new $meta_model_name();
        }

        if (empty($meta_model)) {
            return;
        }

        // 檢查 Model 是否定義了 meta_keys
        if (empty($row->meta_keys)) {
            return;
        }

        // Keys
        $master_key = $meta_model->master_key ?? $row->getForeignKey();
        $master_key_value = $row->id;

        // 取出舊資料
        $all_meta = $row->metas()->get()->keyBy('meta_key')->toArray();

        $upsert_data = [];
        $keys_to_delete = [];

        // 遍歷 meta_keys（而非 post_data）
        foreach ($row->meta_keys as $meta_key) {
            // 如果前端有傳這個 key
            if (array_key_exists($meta_key, $data)) {
                $value = $data[$meta_key];

                // 值不為空：準備 upsert
                if ($value !== '' && $value !== null) {
                    $arr = [
                        'id' => $all_meta[$meta_key]['id'] ?? null,
                        $master_key => $master_key_value,
                        'meta_key' => $meta_key,
                        'meta_value' => $value,
                    ];
                    $upsert_data[] = $arr;
                }
                // 值為空：標記刪除
                else {
                    if (isset($all_meta[$meta_key])) {
                        $keys_to_delete[] = $meta_key;
                    }
                }
            }
            // 前端沒傳這個 key：標記刪除
            else {
                if (isset($all_meta[$meta_key])) {
                    $keys_to_delete[] = $meta_key;
                }
            }
        }

        // 執行 upsert
        if (!empty($upsert_data)) {
            $meta_model->upsert($upsert_data, ['id']);
        }

        // 執行刪除
        if (!empty($keys_to_delete)) {
            $row->metas()
                ->where($master_key, $master_key_value)
                ->whereIn('meta_key', $keys_to_delete)
                ->delete();
        }
    }

    // 2025-10-16 以前，可能要廢棄
    // $operator_user_id 必須是 users.id, 即 managers.user_id 或 members.user_id 要注意！
    public static function saveRow(Model $row, $data, $operator_user_id = null)
    {
        $table_columns = self::getSavableColumns($row);

        foreach ($data as $column => $value) {
            if (in_array($column, $table_columns)) {
                $row->{$column} = $value;
            }
        }

        // 新增
        if (empty($row->id)) {
            if (in_array('creator_id', self::getModelTableColumns($row))) {
                $data['creator_id'] = $operator_user_id;
            }
        }
        // 修改
        else {
            if (in_array('modifier_id', self::getModelTableColumns($row))) {
                $data['modifier_id'] = $operator_user_id;
            }
        }

        $row->save();

        return $row;
    }


    // $query = OrmHelper::applyEloquentIncludes($query, $includes, \App\Models\Sale\Invoice::class);
    // {{BaseUrl}}/api/posv2/sales/invoices/1?include=invoiceItems:price,subtotal,customer:name,email
    public static function applyEloquentIncludes(EloquentBuilder $query, array $includes, string $baseModelClass): EloquentBuilder
    {
        foreach ($includes as $relation => $fields) {
            if (empty($fields)) {
                $query->with($relation);
            } else {
                $query->with([
                    $relation => function ($q) use ($fields, $relation, $baseModelClass) {
                        $relationInstance = (new $baseModelClass)->{$relation}();
                        $relatedModel = $relationInstance->getRelated();

                        // 主鍵
                        $primaryKey = $relatedModel->getKeyName();
                        if (!in_array($primaryKey, $fields)) {
                            $fields[] = $primaryKey;
                        }

                        // 外鍵（例如 invoice_id）
                        if (method_exists($relationInstance, 'getForeignKeyName')) {
                            $foreignKey = $relationInstance->getForeignKeyName();
                            if (!in_array($foreignKey, $fields)) {
                                $fields[] = $foreignKey;
                            }
                        }

                        $q->select($fields);
                    }
                ]);
            }
        }

        return $query;
    }
}
