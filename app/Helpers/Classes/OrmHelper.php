<?php

namespace App\Helpers\Classes;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class OrmHelper
{
    public static function prepare($query, &$params = [])
    {
        self::select($query, $params);
        self::applyFilters($query, $params);
        self::sortOrder($query, $params);
        // self::showSqlContent($query);
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
            // 沒設定 equal_is_active 的時候，預設=1
            if(!isset($params['equal_is_active'])){
                $params['equal_is_active'] = 1;
            } else {
                // 存在 equal_is_active, 但值 = '*', 則取消檢查
                if($params['equal_is_active'] == '*'){
                    unset($params['equal_is_active']);
                }

                // 如果存在 equal_is_active，但值不是 1 也不是 *，代表值 = 0，不啟用
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
                $query->$type(function ($query) use($column) {
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
        if(method_exists($rows, 'get') || !empty($rows->get('path'))){
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

    //處理 guarded()。原本 model 內建的 create() 會受 fillable() 限制。但是若使用 guarded() 不會包含在 fillable() 裡面。因此新增判斷
    public static function getSavable(Model $row)
    {
        $table = $row->getTable();
        $table_columns = self::getTableColumns($table);
        $fillable = $row->getFillable();

        // 排除 $guarded
        if (empty($fillable)) {
            $result = array_diff($table_columns, $row->getGuarded());
        }
        // 模型未設定 $fillable: 資料表全部欄位，但是排除$guarded
        else {
            $result = array_diff($fillable, $row->getGuarded());
        }

        return $result;
    }

    public static function findIdOrFailOrNew(EloquentBuilder $query, $id)
    {
        // 如果有 id，就嘗試找資料
        if (!empty($id)) {
            $row = $query->findOrFail($id); // 找不到會丟出 ModelNotFoundException
        } else {
            $row = $query->getModel()->newInstance(); // 回傳一個新的 Model 實例
        }

        return $row ?? null;
    }

    public static function saveRow(Model $row, $data)
    {
        $table_columns = self::getSavable($row);

        foreach ($data as $column => $value) {
            if (in_array($column, $table_columns)){
                $row->{$column} = $value;
            }
        }

        $row->save();

        return $row;
    }
}