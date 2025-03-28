<?php

namespace App\Helpers\Classes;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

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
        $table = $model->getPrefix() . $model->getTable();
        $table_columns = $model->getTableColumns();

        // is_active
            // 沒設定 equal_is_active 的時候，預設=1
            if(!isset($params['equal_is_active'])){
                $params['equal_is_active'] = 1;
            } else {
                // 存在 equal_is_active, 但值 = '*', 則取消檢查
                if($params['equal_is_active'] == '*'){
                    unset($params['equal_is_active']);
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
            $table = $model->getPrefix() . $model->getTable();

            // 取交集
            $select = array_intersect($select, $model->getTableColumns());

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
            $mainTable = $masterModel->getPrefix() . $masterModel->getTable();
            $foreign_key = $masterModel->getForeignKey();

            $translation_table = $masterModel->getTranslationTable() ?? '';
            $translation_keys = $masterModel->getTranslationKeys() ?? [];

            if(empty($sort) && in_array('id', $masterModel->getTableColumns())){
                $sort = 'id';
            }

            if(empty($order)){
                $order = 'DESC';
            }

            // 本表欄位
            if (in_array($sort, $masterModel->getTableColumns())) {
                $query->orderBy("{$mainTable}.{$sort}", $order);
            }
            // 翻譯欄位
            else if (!empty($translation_table && !empty($translation_keys) )){
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

    // 自訂轉換資料的方法
    public static function toCleanCollection($data)
    {
        // 如果資料是 LengthAwarePaginator 實例，處理分頁資料
        if ($data instanceof LengthAwarePaginator) {

            // 先取得分頁資料集合並過濾不必要的欄位
            $rows = $data->getCollection();
            
            // 清理資料集合
            $cleanData = $rows->map(function ($item) {
                return self::toCleanObject($item);
            });

            // 返回包含分頁資訊的結果
            return collect([
                'current_page' => $data->currentPage(),
                'first_page_url' => $data->url(1),  // 第一頁 URL
                'from' => $data->firstItem(),  // 目前顯示資料的起始項目
                'last_page' => $data->lastPage(),  // 最後一頁頁碼
                'last_page_url' => $data->url($data->lastPage()),  // 最後一頁 URL
                // 'links' => $data->links(),  // 分頁的超連結 html 內容，不必要，而且無法用 toArray()展開，會有記憶體耗盡的問題
                'next_page_url' => $data->nextPageUrl(),  // 下一頁 URL
                'path' => $data->path(),  // 基礎 URL
                'per_page' => $data->perPage(),  // 每頁顯示資料數量
                'prev_page_url' => $data->previousPageUrl(),  // 上一頁 URL
                'to' => $data->lastItem(),  // 目前顯示資料的結束項目
                'total' => $data->total(),  // 總資料數量
                'data' => $cleanData  // 返回清理後的資料
            ]);
        }

        // 如果資料是集合類型（Collection），則逐一處理
        else if (is_object($data) && method_exists($data, 'map')){
            return $data->map(function ($item) {
                return self::toCleanObject($item);
            });
        }
    }

    // 將單一模型轉換為清潔版物件
    public static function toCleanObject($input)
    {
        if (is_string($input)) {
            return $input; // 如果是字串，直接返回
        }

        // 先將模型轉換為陣列
        $data = is_object($input) && method_exists($input, 'toArray') ? $input->toArray() : (array) $input;

        // 使用 stdClass 來保存每一筆資料
        $object = new \stdClass();

        // 將陣列轉換為 stdClass 並過濾不必要的欄位
        foreach ($data as $key => $value) {
            // 排除不必要的欄位（例如 Eloquent 模型的元資料欄位）
            if (in_array($key, ['incrementing', 'exists', 'wasRecentlyCreated', 'timestamps', 'usesUniqueIds', 'preventsLazyLoading', 'guarded', 'fillable'])) {
                continue;
            }

            if ($key === 'translation') {
                continue;  // 排除 translation 欄位
            }

            // 處理關聯資料（遞回處理）
            if (is_array($value) || is_object($value)) {
                $object->{$key} = is_array($value)
                    ? self::toCleanCollection(collect($value))  // 如果是陣列，遞回清理
                    : self::toCleanObject($value);  // 如果是物件，遞回清理
            } else {
                // 其他資料，直接賦值
                $object->{$key} = $value;
            }
        }

        return $object;
    }


}