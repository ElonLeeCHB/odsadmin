<?php

// 原本是 EloquentTrait
/**
 * created at: 2025-02-10 13:50
 */
namespace App\Libraries;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Helpers\Classes\DataHelper;
use App\Models\Localization\Translation;
use App\Helpers\Classes\ChineseCharacterHelper;

class EloquentLibrary
{
    protected  $zh_hant_hans_transform;
    protected  $model;
    protected  $modelName;
    public $table;
    public $table_columns;
    public $translation_keys;

    public function __construct(Model $model)
    {
        $this->model = $model;
        // $this->meta_model_name = $this->model->getMetaModelName();
        $this->table = $this->model->getTable();
        $this->table_columns = $this->getTableColumns();
        $this->translation_keys = $this->model->translation_keys ?? [];
        $this->zh_hant_hans_transform = false;
    }

    public function newModel()
    {
        $modelName = get_class($this->model);

        return new $modelName;
    }

    public function findIdOrFailOrNew($id, $params = null, $debug = 0)
    {
        $row = [];

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

            return $row;
            
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * $table should be full name
     */
    public function getTableColumns($connection = null, $table = null)
    {
        if(empty($table) && !empty($this->table)){
            $table = $this->table;
        }else if(empty($table) && empty($this->table)){
            $table = $this->model->getTable();
        }

        $cache_name = 'cache/table_columns/' . $table . '.serialized.txt';

        return DataHelper::remember($cache_name, 60*60*24*90, 'json', function() use($connection, $table){
            if(!empty($connection)){
                $table_columns = DB::connection($connection)->getSchemaBuilder()->getColumnListing($table);
            }
            else if(!empty($this->model->connection) ){
                $table_columns = DB::connection($this->model->connection)->getSchemaBuilder()->getColumnListing($table);
            }
            else{
                $table_columns = DB::getSchemaBuilder()->getColumnListing($table);
            }

            return $table_columns;
        });
    }

    public function getRow($data, $debug = 0)
    {
        $data['first'] = true;
        $row = $this->getRows($data, $debug);
        return $row;
    }

    public function getRows($data = [], $debug = 0)
    {
        // if(!empty($data['caller']) && $data['caller'] == 'OrganizationRepository'){
        //     echo '<pre>'.print_r($data, true).'</pre>';exit;
        // }
        
        $query = $this->newModel()->query();

        $query = $this->setQuery($query, $data, $debug);

        $rows = DataHelper::getResult($query, $data);

        return $rows;
    }

    public function setQuery($query, $data, $debug = 0)
    {
        $data = $this->setIsActiveForData($data);
        
        $this->select($query, $data);
        // $this->setSelectRaw($query, $data);
        $this->setWith($query, $data);
        $this->setWhereIn($query, $data);
        $this->setWhereNotIn($query, $data);
        $this->setWhereDoesntHave($query, $data);
        $this->setWhereRawSqls($query, $data);
        $this->setEqualsQuery($query, $data);
        $this->setFiltersQuery($query, $data);
        $this->setWhereBetween($query, $data);
        $this->setWhereHas($query, $data);// 必須在後面 setEqualsQuery(), setFiltersQuery() 後面
        $this->setOrWhere($query, $data);
        $this->setDistinct($query, $data);
        $this->setSortOrder($query, $data);
        $this->setGroupBy($query, $data);
        $this->setTranslationsQuery($query, $data);
        
        $this->showSqlQuery($query, $debug);

        return $query;
    }

    /**
     * $data['select] = ['col1', 'col2'];
     */
    public function select($query, $data)
    {
        if(!empty($data['select'])){

            if(is_array($data['select'])){
                $query->select($data['select']);
            }else{
                $query->select(DB::raw($data['select']));
            }
        }

        return $query;
    }

    public function setIsActiveForData($data)
    {

        if(in_array('is_active', $this->table_columns)){

            // 相容 filter_is_active
            if(isset($data['filter_is_active'])){
                $data['equal_is_active'] = $data['filter_is_active'];
                unset($data['filter_is_active']);
            }

            if(isset($data['equal_is_active'])) {
                if($data['equal_is_active'] == '*' || $data['equal_is_active'] < 0 ){
                    unset($data['equal_is_active']);
                }
            }
        }
        
        return $data;
    }

    public function setWith(&$query, $data)
    {
        if(empty($data['with'])){
            return;
        }

        //width_arr has to be array
        if(is_string($data['with'])){
            // $width_arr[] = $data['with'];
            $width_arr = explode(',',$data['with']);
        }else if(is_array($data['with'])){
            $width_arr = $data['with'];
        }

        // check translation
        $has_translation = false;
        $appends = $this->model->getAppends() ?? [];
        $translation_keys = $this->model->translation_keys ?? [];
        foreach ($appends as $append) {
            if(in_array($append, $translation_keys)){
                $has_translation = true;
                break;
            }
        }

        if($has_translation){
            $width_arr[] = 'translation';
        }
        
        //unique
        $width_arr = array_unique($width_arr);


        foreach ($width_arr as $key => $with) {
            // Example: $data['with'] = ['products','members'];
            if(!is_array($with)){
                $query->with($with);
            }
        }
    }

    /*
    $data['whereIn'] = ['code' => $arr];
    */
    public function setWhereIn(&$query, $data)
    {
        if(!empty($data['whereIn'])){
            foreach ($data['whereIn'] as $column => $arr) {
                if(in_array($column, $this->table_columns) && is_array($arr) && !empty($arr)){
                    $query->whereIn($this->table . '.' . $column, $arr);
                }
            }
        }
    }

    public function setWhereNotIn(&$query, $data)
    {
        if(!empty($data['whereNotIn'])){
            foreach ($data['whereNotIn'] as $column => $arr) {
                $query->whereNotIn($this->table . '.' . $column, $arr);
            }
        }
    }

    public function setWhereHas(&$query, $data)
    {
        if(empty($data['whereHas'])){
            return $query;
        }

        foreach ($data['whereHas'] as $relation_name => $whereHasData) {
            
            // $relation_name 是數字表示為索引，只需要判斷 $whereHasData 關聯名稱
            if(is_numeric($relation_name)){
                $query->whereHas($whereHasData); 
                break;
            }

            $caller = $data['caller'] ?? ''; //除錯用

            $query->whereHas($relation_name, function($subQuery) use ($whereHasData, $caller) {

                $relatedModel = $subQuery->getModel();
                $relatedTable = $relatedModel->getTable();
                $relatedColumns = $this->getTableColumns(null, $relatedTable);

                foreach($whereHasData as $key => $value){

                    if($key == 'whereIn'){
                        foreach ($value as $column => $arr) {
                            $subQuery->whereIn($column, $arr);
                        }
                    }
                    else if($key == 'andOrWhere'){
                        // 這時候 $value 應該是陣列 
                        // [
                        //     'filter_abc' => '...',
                        //     'filter_def' => '...',
                        // ]
                        $subQuery->where(function($subsubQuery) use($value, $relatedColumns){
                            foreach($value as $filter_column => $new_value){
                                $column = $this->extractColumnName($filter_column);
                                // $subsubQuery->orWhere($column, 'like', "%{$new_value}%");
                                $this->setWhereQuery($subsubQuery, $column, $new_value, type:'orWhere', table_columns:$relatedColumns);
                                
                            }
                        });
                    }else{
                        // 這時候 $value 應該是字串
                        // $subQuery->where($key, 'like', "%{$value}%");
                        $this->setWhereQuery($subQuery, $key, $value, type:'where', table_columns:$relatedColumns, caller: $caller);
                    }
                }
            });
        }
    }

    /**
     * filter_somecolumn 如果在翻譯表，則去翻譯表查詢。
     * $basic_translation_filter_data: 查找 $data 第一層欄位
     * $advanced_translation_filter_data: 查找 $data['translation]
     */
    public function setTranslationsQuery(&$query, $data, $flag = 1)
    {
        if(empty($this->model->translation_keys)){
            return;
        }

        //判斷第一層 filter_column 是否存在
        $basic_translation_filter_data = [];

        foreach ($data ?? [] as $key => $value) {

            if (str_starts_with($key, 'filter_')) {
                $column = str_replace('filter_', '', $key);
            }else if (str_starts_with($key, 'equal_')) {
                $column = str_replace('equal_', '', $key);
            }else{
                $column = $key;
            }

            if(in_array($column, $this->model->translation_keys)){
                $basic_translation_filter_data[$key] = $value;
            }
        }

        //判斷進階查詢是否存在
        $advanced_translation_filter_data = [];

        if(!empty($data['translation'])){
            $advanced_translation_filter_data = $data['translation'];
        }

        //既無基本查詢，也無進階查詢
        if(empty($basic_translation_filter_data) && empty($advanced_translation_filter_data)){
            return;
        }

        //開始構建查詢
        $query->whereHas('translation', function($qry) use ($basic_translation_filter_data, $advanced_translation_filter_data) {
            $qry->where('locale', app()->getLocale());

            //基本查詢
            if(!empty($basic_translation_filter_data)){
                foreach($basic_translation_filter_data as $column => $value){
                    $this->setWhereQuery($qry, $column, $value, 'where');
                }
            }

            //進階查詢 例如: $data['translation'] = ['name' => 'value1', 'short_name' => 'value2'];
            if(!empty($advanced_translation_filter_data)){
                foreach($advanced_translation_filter_data as $column => $value){
                    $qry->where(function($qry) use ($column, $value){
                        $qry->orWhere(function($qry) use ($column, $value){
                            $this->setWhereQuery($qry, $column, $value, 'where');
                        });
                    });
                }
            }
        });
        
        return $query;
    }

    public function setWhereDoesntHave($query, $data)
    {
        if(empty($data['whereDoesntHave'])){
            return $query;
        }

        foreach ($data['whereDoesntHave'] as $relation_name => $relation) {
            $query->whereDoesntHave($relation_name, function($query) use ($relation) {
                foreach ($relation as $column => $value) {
                    $query->where($column, 'like', "%{$value}%");
                }
            });
        }
    }

    public function setWhereRawSqls(&$query, $data)
    {
        if(empty($data['whereRawSqls'])){
            return $query;
        }

        if(is_string($data['whereRawSqls'])){
            $data['whereRawSqls'][] = $data['whereRawSqls'];
        }

        foreach($data['whereRawSqls'] as $rawsql){
            $query->whereRaw('(' . $rawsql . ')');
        }

        return $query;
    }

    public function setDistinct(&$query, $data)
    {
        if(!empty($data['distinct'])){
            $query->distinct();
        }

        return $query;
    }

    public function setSortOrder(&$query, $data)
    {
        //  - Order (default DESC)
        if (isset($data['order']) && (strtolower($data['order']) == 'asc')) {
            $order = 'asc';
        }
        else{
            $order = 'desc';
        }

        //  - Sort
        if(!empty($data['sort']) && $data['sort'] === 'sort_order' && !in_array('sort_order', $this->table_columns)){
            unset($data['sort']);
        }

        //  -- 指定排序字串
        if(!empty($data['orderByRaw'])){
            unset($data['sort']);
            unset($data['order']);
            $query->orderByRaw($data['orderByRaw']);
        }
        else if(!empty($data['orderByRaws'])){
            foreach($data['orderByRaws'] as $orderByRaw){
                $query->orderByRaw($orderByRaw);
            }
        }
        // -- 指定排序欄位
        else if(!empty($data['sort'])){
            // 非多語欄位
            if(!in_array($data['sort'], $this->translation_keys)){
                $query->orderBy($data['sort'], $order);
            }
            // 多語欄位
            else{
                $translation_table = $this->model->getTranslationTable();
                $master_key = $this->model->getTranslationMasterKey();
                $sort = $data['sort'];
    
                $query->join($translation_table, function ($join) use ($translation_table, $master_key, $sort){
                    $join->on("{$this->table}.id", '=', "{$translation_table}.{$master_key}")
                         ->where("{$translation_table}.locale", '=', app()->getLocale());
                });
                $query->orderBy("{$translation_table}.{$sort}", $order);

                $query->select($this->table.'.*');
            }

        }

        // 未指定排序欄位，但資料表欄位有 sort_order
        else if(empty($data['sort']) && in_array('sort_order', $this->getTableColumns())){
            $query->orderBy('sort_order', 'ASC');
        }
        //  -- 其它情況
        else if(in_array('id', $this->table_columns)){
            if(empty($data['sort']) || $data['sort'] == 'id'){
                $sort = $this->model->getTable() . '.id';
            }
            else{
                $sort = $data['sort'];
            }
            $query->orderBy($sort, $order);
        }
    }

    /**
     * 處理單一欄位
     * 'foo woo'    where($column, 'REGEXP', 'foo(.*)woo')
     * 'foo*woo'    where($column, 'REGEXP', 'foo(.*)woo')
     * '=foo woo'   where($column, '=', 'foo woo')
     * 'foo woo*'   where($column, 'like', 'foo woo%')
     * '*foo woo'   where($column, 'like', '%foo woo')
     * '>123'       where column >123
     * '<123'       where column <123
     * '<>123'      where column <>123
     * $type = 'where' or 'orWhere'
     */
    public function setWhereQuery(&$query, $filter_key, $value, $type='where', $table_columns = [], $caller = '')
    {
        $value = trim($value);

        if(strlen($value) == 0){
            return;
        }

        if(empty($table_columns)){
            $table_columns = $this->getTableColumns();
        }
        
        $column = '';

        if(str_starts_with($filter_key, 'filter_')){
            $column = str_replace('filter_', '', $filter_key);
        }else if(str_starts_with($filter_key, 'equal_')){
            $column = str_replace('equal_', '', $filter_key);
        }

        if(str_starts_with($filter_key, 'filter_')){

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

                if($this->zh_hant_hans_transform === true){
                    $query->$type(function ($query) use($column, $value) {
                        //先用原本字串查一次
                        $query->orWhere($column, 'REGEXP', $value);

                        $zhtw = ChineseCharacterHelper::zhChsToCht($value);
                        if(!empty($zhtw)){
                            $query->orWhere($column, 'REGEXP', $zhtw);
                        }
                        
                        $zhcn = ChineseCharacterHelper::zhChtToChs($value);
                        if(!empty($zhcn)){
                            $query->orWhere($column, 'REGEXP', $zhcn);
                        }
                    });
                }else{
                    $query->$type(function ($query) use($column, $value) {
                        $query->orWhere($column, 'REGEXP', $value);
                    });
                }
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
                if($this->zh_hant_hans_transform === true){
                    $query->$type(function ($query) use($column, $value) {
                        //先用原本字串查一次
                        $query->orWhere($column, '=', $value);

                        $zhtw = ChineseCharacterHelper::zhChsToCht($value);
                        if(!empty($zhtw)){
                            $query->orWhere($column, '=', $zhtw);
                        }
                        
                        $zhcn = ChineseCharacterHelper::zhChtToChs($value);
                        if(!empty($zhcn)){
                            $query->orWhere($column, '=', $zhcn);
                        }
                    });
                }else{
                    $query->$type(function ($query) use($column, $value) {
                        $query->orWhere($column, '=', $value);
                    });
                }
            }
            // '<>' Not empty or not null
            else if($value === '<>'){
                $query->$type(function ($query) use($column) {
                    $query->orWhereNotNull($column);
                    $query->orWhere($column, '<>', '');
                });
            }
            // '<>foo woo' Not equal 'foo woo'
            else if(str_starts_with($value, '<>') && strlen($value) > 2){
                $value = substr($value,2); // 'foo woo'
                if($this->zh_hant_hans_transform === true){
                    $query->$type(function ($query) use($column, $value) {
                        //先用原本字串查一次
                        $query->orWhere($column, '<>', $value);

                        $zhtw = ChineseCharacterHelper::zhChsToCht($value);
                        if(!empty($zhtw)){
                            $query->orWhere($column, '<>', $zhtw);
                        }
                        
                        $zhcn = ChineseCharacterHelper::zhChtToChs($value);
                        if(!empty($zhcn)){
                            $query->orWhere($column, '<>', $zhcn);
                        }
                    });
                }else{
                    $query->$type(function ($query) use($column, $value) {
                        $query->orWhere($column, '<>', $value);
                    });
                }
            }
            // '<123' Smaller than 123
            else if(str_starts_with($value, '<') && strlen($value) > 1){
                $value = substr($value,1); // '123'
                $query->$type($column, '<', $value);
            }
            // '>123' bigger than 123
            else if(str_starts_with($value, '>') && strlen($value) > 1){
                $value = substr($value,1);
                $query->$type($column, '>', $value);
            }
            // '*foo woo'
            else if(substr($value,0, 1) == '*' && substr($value,-1) != '*'){
                $value = str_replace(' ', '(.*)', $value);
                $value = "(.*)".substr($value,1).'$';
                if($this->zh_hant_hans_transform === true){
                    $query->$type(function ($query) use($column, $value) {
                        //先用原本字串查一次
                        $query->orWhere($column, 'REGEXP', "$value");

                        $zhtw = ChineseCharacterHelper::zhChsToCht($value);
                        if(!empty($zhtw)){
                            $query->orWhere($column, 'REGEXP', "$zhtw");
                        }
                        
                        $zhcn = ChineseCharacterHelper::zhChtToChs($value);
                        if(!empty($zhcn)){
                            $query->orWhere($column, 'REGEXP', "$zhcn");
                        }
                    });
                }else{
                    $query->$type(function ($query) use($column, $value) {
                        $query->orWhere($column, 'REGEXP', "$value");
                    });
                }
            }
            // 'foo woo*'
            else if(substr($value,0, 1) != '*' && substr($value,-1) == '*'){
                $value = substr($value,0,-1); // foo woo
                $value = str_replace(' ', '(.*)', $value); //foo(.*)woo
                $value = '^' . $value . '(.*)';

                if($this->zh_hant_hans_transform === true){
                    $query->$type(function ($query) use($column, $value) {
                        //先用原本字串查一次
                        $query->orWhere($column, 'REGEXP', "$value");

                        $zhtw = ChineseCharacterHelper::zhChsToCht($value);
                        if(!empty($zhtw)){
                            $query->orWhere($column, 'REGEXP', "$zhtw");
                        }
                        
                        $zhcn = ChineseCharacterHelper::zhChtToChs($value);
                        if(!empty($zhcn)){
                            $query->orWhere($column, 'REGEXP', "$zhcn");
                        }
                    });
                }else{
                    $query->$type(function ($query) use($column, $value) {
                        $query->orWhere($column, 'REGEXP', "$value");
                    });
                }
            }
        }

        if(str_starts_with($filter_key, 'equal_')){
            $query->{$type}($column, $value);
        }
    }

    public function setWhereBetween(&$query, $data)
    {
        if(!empty($data['whereBetween']) && count($data['whereBetween']) == 3){
            $query->whereBetween($data['whereBetween'][0], [$data['whereBetween'][1], $data['whereBetween'][2]]);
        }
    }

    public function setAndSubOrWhereQuery(&$query, $set)
    {
        $query->where(function ($qry) use(&$query, $set) {
            foreach ($set as $key => $value) {
                $query = $this->setWhereQuery($qry, $key, $value,'orWhere');
            }
        });
    }

    public function setOrWhere(&$query, $data)
    {
        foreach ($data['orWhere'] ?? [] as $key => $value) {
            // $this->setEqualQuery($query, $key, $value, 'orWhere');
            // $this->setEqualQuery($query, $key, $value, 'orWhere');
            $this->setWhereQuery($query, $key, $value, 'orWhere');
        }
    }

    public function setEqualsQuery($query, &$data, $debug=0)
    {
        $table_columns = $this->getTableColumns();
        $translation_keys = $this->model->translation_keys ?? [];

        $meta_keys = $this->model->meta_keys;
        if(empty($meta_keys)){
            $meta_keys = [];
        }

        foreach ($data ?? [] as $key => $value) {

            $column = null;
            
            if(str_starts_with($key, 'equal_')){ // Key must start with equal_
                $column = str_replace('equal_', '', $key);
            }else{
                continue;
            }

            if(is_array($value)){ // value can not be array
                continue;
            }

            // Translated column is not processed here
            if(in_array($column, $translation_keys)){
                continue;
            }

            // meta_keys is not processed here
            if(in_array($column, $meta_keys)){
                continue;
            }

            // Has to be the table's columns
            if(!in_array($column, $table_columns)){
                continue;
            }

            $value_array = explode('__or__', $value);
            if(count($value_array) > 1){
                $column = $this->table . '.' . $column;
                $query->whereIn($column, $value_array);
            }else{
                $column = $this->table . '.' . $column;
                $query->where($column, $value);
            }
        }

        // set translated whereHas
        foreach ($data ?? [] as $key => $value) {
            if(!str_starts_with($key, 'equal_')){
                continue;
            }else{
                $column = str_replace('equal_', '', $key);
            }

            if(in_array($column, $translation_keys)){
                $query->whereHas('translation', function ($query) use ($column, $value) {
                    $query->where('meta_key', $column);
                    $query->where('meta_value', $value);
                });
                unset($data[$key]);
            }
        }

        // set meta whereHas
        foreach ($data ?? [] as $key => $value) {
            if(!str_starts_with($key, 'equal_') || $value == '*'){
                continue;
            }else{
                $column = str_replace('equal_', '', $key);
            }

            if(in_array($column, $meta_keys)){
                $query->whereHas('metas', function ($query) use ($column, $value) {
                    $query->where('meta_key', $column);
                    $query->where('meta_value', $value);
                });
                unset($data[$key]);
            }
        }

        return $query;
    }

    public function setFiltersQuery($query, &$data, $debug=0)
    {
        $translation_keys = $this->model->translation_keys ?? [];
        $table_columns = $this->getTableColumns();

        $meta_keys = $this->model->meta_keys;
        if(empty($meta_keys)){
            $meta_keys = [];
        }

        foreach ($data ?? [] as $key => $value) {
            // $key has prifix 'filter_'
            // $column is the name of database table's column

            $column = null;

            // Must Start with filter_
            if(str_starts_with($key, 'filter_')){
                $column = str_replace('filter_', '', $key);
            }else{
                continue;
            }

            // Skip emtpy value
            if($value == ''){
                continue;
            }

            // Translated column is not processed here
            if(in_array($column, $translation_keys)){
                continue;
            }

            // meta_keys is not processed here
            if(in_array($column, $meta_keys)){
                continue;
            }

            // Has to be the table's columns
            if(!in_array($column, $table_columns)){
                continue;
            }

            if(is_array($value)){ // Filter value can not be array
                continue;
            }

            if(isset($data['regexp']) && $data['regexp'] == false){
                $value = "=$value";
            }else{
                $first = substr($value, 0, 1);
                if(!in_array($first, ['<', '>'])){
                    $value = str_replace(' ', '*', trim($value));
                }
            }

            $query = $this->setWhereQuery($query, $key, $value, 'where');
        }
        
        // Filters - data table - andSubOrWhere
        if(!empty($data['andOrWhere'])){
            foreach ($data['andOrWhere'] as $set) {
                $this->setAndSubOrWhereQuery($query, $set);
            }
        }

        // set translated whereHas
        foreach ($data ?? [] as $key => $value) {
            if(!str_starts_with($key, 'filter_')){
                continue;
            }else{
                $column = str_replace('filter_', '', $key);
            }

            if(in_array($column, $translation_keys) && !empty($data[$key])){
                $data['whereHas']['translation'][$key] = $data[$key];
                unset($data[$key]);
            }
        }

        // set meta whereHas
        foreach ($data ?? [] as $key => $value) {
            if(!str_starts_with($key, 'filter_') || $value == '*'){
                continue;
            }else{
                $column = str_replace('filter_', '', $key);
            }

            if(in_array($column, $meta_keys)){
                $data['whereHas']['metas'] = ['meta_key' => $column, 'meta_value' => $value];
                unset($data[$key]);
            }
        }
        
        // Display sql statement
        if(!empty($debug)){
            $this->getDebugQueryContent($query);
        }
    }

    public function setGroupBy(&$query, $data)
    {
        if(!empty($data['groupBy'])){
            $query->groupBy($data['groupBy']);
        }
    }

    public function showSqlQuery(Builder $builder, $debug = 0, $params = [])
    {
        if($debug == 0 ){
            return true;
        }

        $sqlstr = str_replace('?', "'?'", $builder->toSql());

        $bindings = $builder->getBindings();

        if(!empty($bindings)){
            $arr['statement'] = vsprintf(str_replace('?', '%s', $sqlstr), $builder->getBindings());
        }else{
            $arr['statement'] = $builder->toSql();
        }

        $arr['original'] = [
            'toSql' => $builder->toSql(),
            'bidings' => $builder->getBindings(),
        ];

        echo "<pre>".print_r($arr , 1)."</pre>"; exit;
    }
}
