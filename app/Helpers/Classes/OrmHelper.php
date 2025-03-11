<?php
namespace App\Helpers\Classes;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class OrmHelper
{
    public static function findIdOrFailOrNew($id, $params = null, $debug = 0)
    {
        try{
            //find
            if(!empty(trim($id))){
                $params['equal_id'] = $id;
                $row = $this->getRow($params, $debug);
                if(empty($row)){
                    throw new \Exception ('Record not found!');
                }
            }
            //new
            else{
                $row = $this->newModel();
            }

            return ['data' => $row]; // To make difference with 'error', 'data' is needed.

        } catch (\Exception $e) {
            return ['error' => 'findIdOrFailOrNew: ' . $e->getMessage()];
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

    // 選擇本表欄位。不包括關聯欄位。
    public static function select(EloquentBuilder $query, $select = [], $table = '')
    {
        if (!empty($select)) {
            $model = $query->getModel();
            $table = $model->getPrefix() . $model->getTable();

            // 取交集
            $select = array_intersect($select, $model->getTableColumns());

            $query = $query->select(array_map(function($field) use ($table) {
                return "{$table}.{$field}";
            }, $select));
        }
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
    
    // 排序。可以使用本表欄位、關聯欄位
    public static function sortOrder($query, $sort = '', $order = '')
    {
        if(!empty($sort)){
            if(!empty($order)){
                $order = 'ASC';
            }

            if($query instanceof EloquentBuilder){
                $masterModel = $query->getModel();
                $mainTable = $masterModel->getPrefix() . $masterModel->getTable();

                if(in_array($sort, $masterModel->getMetaKeys())){
                    $metaModelName = get_class($masterModel) . 'Meta';
                    $metaModel = new $metaModelName;
                    $metaTable = $metaModel->getPrefix() . $metaModel->getTable();

                    $locale = request()->query('locale') ?? config('app.locale');
        
                    $query->leftJoin("{$metaTable} as sort_meta", function ($join) use ($locale, $sort, $order, $mainTable) {
                        $join->on("{$mainTable}.id", '=', 'sort_meta.product_id')
                            ->where('sort_meta.meta_key', $sort)
                            ->where('sort_meta.locale', $locale);
                    })->orderBy('sort_meta.meta_value', $order);
                } else {
                    $mainTable = $masterModel->getPrefix() . $masterModel->getTable();

                    $query->orderBy("{$mainTable}.{$sort}", $order);
                }
            }
        }
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
        return $data->map(function ($item) {
            return self::toCleanObject($item);
        });
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