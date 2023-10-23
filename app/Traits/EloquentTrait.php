<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use PDO;

/**
 * initialize()
 * newModel()
 * findFirst()
 * findIdOrFailOrNew()
 * findIdOrFailOrNew()
 * setFiltersQuery()
 * setEqualsQuery()
 * setWhereQuery
 * setWith()
 * getTableColumns()
 * getQueryContent()
 * getTranslationModel()
 * saveTranslationData()
 * setMetaDataset()
 * saveMetaDataset()
 * 
 * regexp
 * pagination
 * limit
 * optimize
 * sanitize
 */
trait EloquentTrait
{
    public function initialize($data = null)
    {
        $this->model = new $this->modelName;
        $this->table = $this->model->getTable();

        if(!empty($data['connection'])){
            $this->connection = $data['connection'];
        }else{
            $this->connection = DB::connection()->getName();
        }

        
        $this->table_columns = $this->getTableColumns($this->connection);

        $this->zh_hant_hans_transform = false;

    }

    public function newModel()
    {
        $model = new $this->modelName;

        if(empty($this->model)){
            $this->model = $model;
        }

        return $model;
    }


    public function refineRows($rows, $data)
    {
        $new_rows = [];
        
        foreach ($rows as $key => $row) {
            $new_rows[$key] = $this->refineRow($row, $data);
        }

        return $new_rows;
    }

    // optimizeRow and sanitizeRow should be defined in FooRepository
    public function refineRow($row, $data)
    {
        if (method_exists($this, 'optimizeRow') && !empty($data['optimize'])) {
            $row = $this->optimizeRow($row);
        }

        if (method_exists($this, 'sanitizeRow') && !empty($data['sanitize'])) {
            $row = $this->sanitizeRow($row);
        }

        return $row;
    }


    public function sanitizeRows($rows)
    {
        $new_rows = [];
        foreach ($rows as $key => $row) {
            $new_rows[$key] = $this->sanitizeRow($row);
        }
        
        return $new_rows;
    }


    /**
     * LengthAwarePaginator
     */
    public function optimizeRows($rows): LengthAwarePaginator | Collection
    {
        foreach ($rows as $key => $row) {
            $rows[$key] = $this->optimizeRow($row);
        }

        return $rows;
    }


    public function findIdFirst($id, $data = null)
    {
        $row = $this->newModel()->where('id', $id)->first();

        return $row;
    }

    public function findIdOrFailOrNew($id, $data = null, $debug = 0)
    {
        //find
        if(!empty(trim($id))){
            $query = $this->newModel();

            if(!empty($data['with'])){
                $query->with($data['with']);
            }

            $row = $query->findOrFail($id);
        }
        //new
        else{
            $row = $this->newModel();
        }

        return $row;
    }


    public function getRow($data, $debug=0)
    {
        $data['first'] = true;
        $row = $this->getRows($data, $debug);
        return $row;
    }

    /**
     * $data['filter_foo']
     * $data['pagination']
     * $data['sort']
     * $data['order']
     * $data['limit']
     * $data['no_default_translation']  true,false
     */
    public function getRows($data = [], $debug = 0)
    {
        $this->initialize($data);

        $query = $this->getQueryDebug($data, $debug);

        // get result
        $result = [];

        if(isset($data['first']) && $data['first'] = true){
            if(empty($data['pluck'])){
                $result = $query->first();
            }else{
                $result = $query->pluck($data['pluck'])->first();
            }
        }else{
            // Limit
            if(isset($data['limit'])){
                $limit = (int)$data['limit'];
            }else{
                $limit = 10;
            }

            if(!empty($data['_real_limit'])){ // $data['real_limit'] don't open to public
                $limit = $data['_real_limit'];
            }


            // Pagination
            if(isset($data['pagination']) ){
                $pagination = (boolean)$data['pagination'];
            }else{
                $pagination = true;
            }
    
            if($pagination == true && $limit != 0){  // Get some rows per page
                if(empty($data['pluck'])){
                    $result = $query->paginate($limit);
                }else{
                    $result = $query->paginate($limit)->pluck($data['pluck']);
                }
            }
            else if($pagination == false && $limit != 0){ // Get some rows without pagination
                if(empty($data['pluck'])){
                    $result = $query->limit($limit)->get();
                }else{
                    $result = $query->limit($limit)->pluck($data['pluck']);
                }
            }
            else if($limit == 0){
                if(empty($data['pluck'])){
                    $result = $query->get(); // Get all
                }else{
                    $result = $query->pluck($data['pluck']);
                }
            }

            if(!empty($data['keyBy'])){
                $result = $result->keyBy($data['keyBy']);
            }
        }

        return $result;
    }


    public function getQueryDebug($data=[], $debug=0)
    {
        if(empty($this->table_columns)){
            $this->table_columns = $this->getTableColumns();
        }
        
        $query = $this->newModel()->query();

        // With relations
        if(!empty($data['with'])){
            $this->setWith($query, $data['with']);
        }


        // whereRelations
        // if(!empty($data['whereRelations'])){
        //     foreach ($data['whereRelations'] as $relation_name => $relation) {
        //         $query->whereRelation($relation_name, function($tmpQuery) use ($relation) {
        //             foreach ($relation as $column => $value) {
        //                 $this->setWhereQuery($tmpQuery, $column, $value, 'where');
        //             }
        //         });
        //     }
        // }


        // With translation relation
        if(!empty($this->model->translation_attributes)){
            $query->with('translation');
        }
        
        
        // whereIn
        if(!empty($data['whereIn'])){
            foreach ($data['whereIn'] as $key => $arr) {
                $column = $this->table . '.' . $key;
                $query->whereIn($column, $arr);
            }
        }


        // is_active can only be: 1, 0, -1, *
        if(in_array('is_active', $this->table_columns)){
            
            // - 相容以前的舊寫法
            if(isset($data['filter_is_active'])){
                $data['equal_is_active'] = $data['filter_is_active'];
                unset($data['filter_is_active']);
            }

            // - 如果 equal_is_active 是 *, 或長度是 0 ，或值小於0，表示不做 is_active 判斷。
            if(isset($data['equal_is_active']) && ($data['equal_is_active'] == '*' || strlen($data['equal_is_active']) === 0 || $data['equal_is_active'] < 0)){
                unset($data['equal_is_active']);
            }

            // - 開始判斷
            if(isset($data['equal_is_active'])){
                $equal_is_active = $data['equal_is_active'];

                // -- 變數為值=0，表示不啟用。除了真的是0，把null也算在內。
                if($equal_is_active == 0){
                    $query->where(function ($query) use($equal_is_active) {
                        $query->orWhere('is_active', 0);
                        $query->orWhereNull('is_active');
                    });
                }else if($equal_is_active == 1){
                    $query->where('is_active', 1);
                }

                unset($data['equal_is_active']);
            }
        }


        // Equal
        $this->setEqualsQuery($query, $data);

        // Like %some_value%
        $this->setFiltersQuery($query, $data);

        if(!empty($data['distinct'])){
            $query->distinct();
        }

        // whereHas
        if(!empty($data['whereHas'])){
            foreach ($data['whereHas'] as $relation_name => $relation) {
                $query->whereHas($relation_name, function($query) use ($relation) {
                    foreach ($relation as $key => $value) {
                        $this->setWhereQuery($query, $key, $value, 'where');
                    }
                });
            }
        }

        // whereRawSqls
        if(!empty($data['whereRawSqls']) && is_array($data['whereRawSqls'])){
            foreach($data['whereRawSqls'] as $rawsql){
                $query->whereRaw($rawsql);
            }
        }

        // Sort & Order
        // 舊寫法
        // if(!empty($data['orderByRaw'])){
        //     $query->orderByRaw($data['orderByRaw']);
        // }else{
        //     if(empty($data['sort']) || $data['sort'] == 'id'){
        //         $sort = $this->model->getTable() . '.id';
        //     }else{
        //         $sort = $data['sort'];
        //     }
    
        //     // Order
        //     if (isset($data['order']) && ($data['order'] == 'ASC')) {
        //         $order = 'ASC';
        //     }
        //     else{
        //         $order = 'DESC';
        //     }
    
        //     $query->orderBy($sort, $order);
        // }

        
        
        /*
        //  - Sort
        if(!empty($data['sort']) && !empty($this->model->translation_attributes) && in_array($data['sort'], $this->model->translation_attributes)){
            $translation_table = $this->getTranslationTable();
            $master_key = $this->getTranslationMasterKey();
            $sort = $data['sort'];

            $query->join($translation_table, function ($join) use ($translation_table, $master_key, $sort){
                $join->on("{$this->table}.id", '=', "{$translation_table}.{$master_key}")
                     ->where("{$translation_table}.locale", '=', $this->locale)
                     ->where("{$translation_table}.meta_key", '=', $sort);
            });

            $query->orderBy("{$translation_table}.meta_value", $order);

            $query->select("{$this->table}.*");
        }else{
            if(empty($data['sort']) || $data['sort'] == 'id'){
                $sort = $this->model->getTable() . '.id';
            }
            else{
                $sort = $data['sort'];
            }
            $query->orderBy($sort, $order);
        }
        */


        // Sort & Order
        //  - Order
        if (isset($data['order']) && ($data['order'] == 'ASC')) {
            $order = 'ASC';
        }
        else{
            $order = 'DESC';
        }
        
        //  - Sort
        //  -- 指定排序字串
        if(!empty($data['orderByRaw'])){
            $query->orderByRaw($data['orderByRaw']);
        }
        //  -- 用多語欄位排序
        else if(!empty($data['sort']) && !empty($this->model->translation_attributes) && in_array($data['sort'], $this->model->translation_attributes)){
            $translation_table = $this->model->getTranslationTable();
            $master_key = $this->model->getTranslationMasterKey();
            $sort = $data['sort'];

            if (str_ends_with($this->model->translation_model_name, 'Meta')) {

                $query->join($translation_table, function ($join) use ($translation_table, $master_key, $sort){
                    $join->on("{$this->table}.id", '=', "{$translation_table}.{$master_key}")
                         ->where("{$translation_table}.locale", '=', $this->locale)
                         ->where("{$translation_table}.meta_key", '=', $sort);
                });
                $query->orderBy("{$translation_table}.meta_value", $order);

            }else{ // 一般用 Translation 做結尾，例如 ProductTranslation
                $query->join($translation_table, function ($join) use ($translation_table, $master_key, $sort){
                    $join->on("{$this->table}.id", '=', "{$translation_table}.{$master_key}")
                         ->where("{$translation_table}.locale", '=', app()->getLocale());
                });
                $query->orderBy("{$translation_table}.{$sort}", $order);
            }
        }
        //  -- 非多語欄位排序
        else{
            if(empty($data['sort']) || $data['sort'] == 'id'){
                $sort = $this->model->getTable() . '.id';
            }
            else{
                $sort = $data['sort'];
            }
            $query->orderBy($sort, $order);
        }

        // Select
        if(isset($data['select'])){
            if(is_array($data['select'])){
                $query->select($data['select']);
            }else if($data['select'] !== '*'){
                $query->select(DB::raw($data['select']));
            }
        }else{
            $query->select("{$this->table}.*");
        }

        // see the sql statement
        if(!empty($debug)){
            $this->getQueryContent($query);
        }

        return $query;
    }


    private function setFiltersQuery($query, $data, $debug=0)
    {
        $translation_attributes = $this->model->translation_attributes ?? [];
        $table_columns = $this->getTableColumns($this->connection);
        
        $meta_keys = $this->model->meta_keys;
        if(empty($meta_keys)){
            $meta_keys = [];
        }

        foreach ($data as $key => $value) {
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
            if(in_array($column, $translation_attributes)){
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
                $value = str_replace(' ', '*', trim($value));
            }

            $query = $this->setWhereQuery($query, $column, $value, 'where');
        }

        // Filters - data table - andSubOrWhere
        if(!empty($data['andOrWhere'])){
            foreach ($data['andOrWhere'] as $set) {
                $this->setAndSubOrWhereQuery($query, $set);
            }
        }

        // set translated whereHas
        foreach ($data as $key => $value) {
            if(!str_starts_with($key, 'filter_')){
                continue;
            }else{
                $column = str_replace('filter_', '', $key);
            }

            if(in_array($column, $translation_attributes) && !empty($data[$key])){
                $data['whereHas']['translation'][$key] = $data[$key];
                unset($data[$key]);
            }
        }

        // set meta whereHas
        foreach ($data as $key => $value) {
            if(!str_starts_with($key, 'filter_')){
                continue;
            }else{
                $column = str_replace('filter_', '', $key);
            }

            if(in_array($column, $meta_keys)){
                $data['whereHas']['meta_dataset'] = ['meta_key' => $column, 'meta_value' => $value];
                unset($data[$key]);
            }
        }

        // Filters - relations
        if(!empty($data['whereHas'])){
            $this->setWhereHas($query, $data['whereHas']);
        }

        // Display sql statement
        if(!empty($debug)){
            $this->getQueryContent($query);
        }
    }


    private function setEqualsQuery($query, $data)
    {
        $table_columns = $this->getTableColumns($this->connection);
        $translation_attributes = $this->model->translation_attributes ?? [];

        $meta_keys = $this->model->meta_keys;
        if(empty($meta_keys)){
            $meta_keys = [];
        }

        foreach ($data as $key => $value) {

            $column = null;
            
            if(str_starts_with($key, 'equal_')){ // Key must start with equal_
                $column = str_replace('equal_', '', $key);
            }else{
                continue;
            }

            if(is_array($value) || empty($value)){ // value can not be empty or array
                continue;
            }

            // Translated column is not processed here
            if(in_array($column, $translation_attributes)){
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
        foreach ($data as $key => $value) {
            if(!str_starts_with($key, 'equal_')){
                continue;
            }else{
                $column = str_replace('equal_', '', $key);
            }

            if(in_array($column, $translation_attributes)){
                $query->whereHas('translation', function ($query) use ($column, $value) {
                    $query->where('meta_key', $column);
                    $query->where('meta_value', $value);
                });
                unset($data[$key]);
            }
        }

        // set meta whereHas
        foreach ($data as $key => $value) {
            if(!str_starts_with($key, 'equal_')){
                continue;
            }else{
                $column = str_replace('equal_', '', $key);
            }

            if(in_array($column, $meta_keys)){
                $query->whereHas('meta_dataset', function ($query) use ($column, $value) {
                    $query->where('meta_key', $column);
                    $query->where('meta_value', $value);
                });
                unset($data[$key]);
            }
        }

        return $query;
    }

    public function setAndSubOrWhereQuery($query, $set)
    {
        $query->where(function ($query) use($set) {
            foreach ($set as $key => $value) {
                $query = $this->setWhereQuery($query, $key, $value,'orWhere');
            }
        });
    }

    /**
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
    private function setWhereQuery($query, $column, $value, $type='where')
    {
        if(str_starts_with($column, 'filter_')){
            $column = str_replace('filter_', '', $column);
        }

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
            if(isset($this->zh_hant_hans_transform) && $this->zh_hant_hans_transform === true){
                $zhtw = zhChsToCht($value);
                $zhcn = zhChtToChs($value);
                $query->$type(function ($query) use($column, $zhtw, $zhcn) {
                    $query->orWhere($column, 'REGEXP', $zhtw);
                    $query->orWhere($column, 'REGEXP', $zhcn);
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
                $zhtw = zhChsToCht($value);
                $zhcn = zhChtToChs($value);
                $query->$type(function ($query) use($column, $zhtw, $zhcn) {
                    $query->orWhere($column, '=', $zhtw);
                    $query->orWhere($column, '=', $zhcn);
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
                $zhtw = zhChsToCht($value);
                $zhcn = zhChtToChs($value);
                $query->$type(function ($query) use($column, $zhtw, $zhcn) {
                    $query->orWhere($column, '<>', $zhtw);
                    $query->orWhere($column, '<>', $zhcn);
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
                $query->$type(function ($query) use($column, $zhtw, $zhcn) {
                    $query->orWhere($column, 'REGEXP', "$zhtw");
                    $query->orWhere($column, 'REGEXP', "$zhcn");
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
                $query->$type(function ($query) use($column, $zhtw, $zhcn) {
                    $query->orWhere($column, 'REGEXP', "$zhtw");
                    $query->orWhere($column, 'REGEXP', "$zhcn");
                });
            }else{
                $query->$type(function ($query) use($column, $value) {
                    $query->orWhere($column, 'REGEXP', "$value");
                });
            }
        }

        return $query;
    }

    private function setWhereHas($query, $data)
    {
        foreach ($data as $relation_name => $relation) {
            $query->whereHas($relation_name, function($query) use ($relation) {
                foreach ($relation as $column => $value) {
                    $this->setWhereQuery($query, $column, $value, 'where');
                }
            });
        }
    }

    private function setWith($query, $funcData)
    {
        // $data['with'] = 'translation'
        if(!is_array($funcData)){
            $query->with($funcData);
        }else{
            foreach ($funcData as $key => $filters) {

                // Example: $data['with'] = ['products','members'];
                if(!is_array($filters)){
                    $query->with($funcData);
                }

                /* Example:
                $data['with'] = [
                    'products' => ['slug' => 'someCategory', 'is_active' => 1],
                    'orders' => ['amount' => '>1000']
                ];
                */
                else{
                    // 注意：with 裡面使用Closure函數，只是過濾 with 表，然後附加過來。不會過濾主表
                    $query->with([$key => function($query) use ($key, $filters) {
                        foreach ($filters as $column => $value) {
                            //$query = $this->setWhereQuery($query, $column, $value, 'where');
                            $query->where("$key.$column", '=', $value);
                        }
                    }]);
                }
            }
        }
    }

    public function getTableColumns($connection = null)
    {
        if(empty($this->table)){
            $this->table = $this->model->getTable();
        }

        if(!empty($this->table_columns)){
            return $this->table_columns;
        }

        if(empty($connection) ){
            $this->table_columns = DB::getSchemaBuilder()->getColumnListing($this->table);
        }else{
            $this->table_columns = DB::connection($connection)->getSchemaBuilder()->getColumnListing($this->table);
        }

        return $this->table_columns;
    }

    // For debug
    public static function getQueryContent(Builder $builder)
    {
        $addSlashes = str_replace('?', "'?'", $builder->toSql());

        $bindings = $builder->getBindings();

        if(!empty($bindings)){
            $arr['statement'] = vsprintf(str_replace('?', '%s', $addSlashes), $builder->getBindings());
        }else{
            $arr['statement'] = $builder->toSql();
        }


        $arr['original'] = [
            'toSql' => $builder->toSql(),
            'bidings' => $builder->getBindings(),
        ];

        echo "<pre>".print_r($arr , 1)."</pre>"; exit;
    }


    public function saveRow($row, $data, $debug = 0)
    {
        $this->initialize();

        try{
        
            if(!empty($row->getFillable())){
                $row->fill($data);
                $row->save();
                return $row->id;
            }
            
            $table_columns = $this->table_columns;
            $form_columns = array_keys($data);
            
            foreach ($table_columns as $column) {
                if(!in_array($column, $form_columns)){
                    continue;
                }
    
                $row->$column = $data[$column];
            }
            
            DB::beginTransaction();

            $row->save();

            DB::commit();

            return ['id' => $row->id];

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }

    public function saveTranslationData($masterModel, $data, $translation_attributes=null)
    {
        if(empty($translation_attributes)){
            $translation_attributes = $this->model->translation_attributes;
        }

        if(empty($translation_attributes)){
            return false;
        }

        $translationModel = $this->model->getTranslationModel();

        // foreign key
        $foreign_key = $translationModel->foreign_key ?? $masterModel->getForeignKey();

        $foreign_key_value = $masterModel->id;

        foreach($data as $locale => $value){
            $arr = [];
            if(!empty($value['id'])){
                $arr['id'] = $value['id'];
            }
            $arr['locale'] = $locale;
            $arr[$foreign_key] = $foreign_key_value;
            foreach ($translation_attributes as $column) {
                if(!empty($value[$column])){
                    $arr[$column] = $value[$column];
                }
            }

            $arrs[] = $arr;
        }

        $translationModel->upsert($arrs,['id', $foreign_key, 'locale']);
    }

    /**
     * 獲取 meta_data，並根據 meta_keys ，若 meta_key 不存在，設為空值 ''
     */
     public function getMetaDataset($row)
    {
        $meta_dataset = $row->meta_dataset;

        foreach ($meta_dataset as $meta_data) {
            $row->{$meta_data->meta_key} = $meta_data->meta_value;
        }

        return $row;
    }

    /**
     * saveMetaDataset
     */
    public function saveMetaDataset($masterModel, $data)
    {
        //$organization_id = $masterModel->id;
        $master_id = $masterModel->id;
        $master_key = $masterModel->getForeignKey();

        $existed_meta_data = $masterModel->meta_dataset()->select('id','meta_key')->get();

        $existed_meta_keys = [];
        $new_meta_keys = [];

        foreach ($existed_meta_data as $row) {
            $existed_meta_keys[] = $row->meta_key;
            $existed_meta_key_ids[$row->meta_key] = $row->id;
        }

        $meta_keys = $masterModel->meta_keys;

        foreach ($meta_keys as $meta_key) {
            $key = 'meta_data_' . $meta_key;

            if(!empty($data[$key])){
                $value = $data[$key];
            }else if(!empty($data[$meta_key])){
                $value = $data[$meta_key];
            }else{
                continue;
            }
            
            $new_meta_data[] = [$master_key => $master_id, 'meta_key' => $meta_key, 'meta_value' => $value];

            $new_meta_keys[] = $meta_key;
            
            
        }

        // delete
        $delete_meta_keys = array_diff($existed_meta_keys, $new_meta_keys);

        $delete_ids = [];

        foreach ($delete_meta_keys as $serialNumber => $metaKey) {
            if (isset($existed_meta_key_ids[$metaKey])) {
                $delete_ids[$serialNumber] = $existed_meta_key_ids[$metaKey];
            }
        }

        // upsert
        $masterModel->meta_dataset()->whereIn('id',$delete_ids)->delete();

        if(!empty($new_meta_data)){
            $masterModel->meta_dataset()->upsert($new_meta_data, [$master_key,'meta_key']);
        }
    }

/*
    public function rowsToStdObj($rows)
    {
        foreach ($rows as $key => $row) {
            $rows[$key] = (object) $row->toArray();
        }

        return $rows;
    }
    */

    public function rowsToStdObj($rows, $data = [])
    {
        if(!is_array($rows) && method_exists($rows, 'toArray')) {
            $rows = $rows->toArray();
        }

        foreach ($rows as $key => $row) {

            if(!is_array($row) && method_exists($row, 'toArray')) {
                $row = $row->toArray();
            }
            
            if(!empty($data['unset'])){
                foreach ($data['unset'] as $key2) {
                    unset($row[$key2]);
                }
            }

            $rows[$key] = (object) $row;
        }

        return $rows;
    }


    public function rowToStdObj($row)
    {
        return (object) $row->toArray();
    }


    public function deleteRow($data)
    {
        $row = $this->getRow($data);

        if(!empty($row)){
            $row->delete();
        }
    }



    public function upsert($allData, $whereColumns)
    {
        return $this->newModel()->upsert($allData, $whereColumns);
    }


    /**
     * 2023-05-01
     * 23-05-01
     * 20230501
     * 230501
     * 20230501-20230531
     * 230501-230531
     * 2023-05-01-2023-05-31
     * 23-05-01-23-05-31
     */
    public function parseDateToSqlWhere($column, $dateString)
    {
        $dateString = trim($dateString);

        // 只允許數字或-或:
        if(!preg_match('/^[0-9\-\/:]+$/', $dateString, $matches)){
            return false;
        }

        $date1 = null;
        $date2 = null;

        // 日期區間
        if(strlen($dateString) > 12){
            $dateString = str_replace(':','-',$dateString); //"2023-05-01:2023-05-31" change to "2023-05-01-2023-05-31"
            $count = substr_count($dateString, '-');

            $arr = explode('-', $dateString);

            // 整串只有1個橫線作為兩個日期的分隔
            if($count == 1){
                $date1_year = substr($arr[0], 0, -4);
                if($date1_year < 2000){
                    $date1_year += 2000;
                }

                $date2_year = substr($arr[1], 0, -4);
                if($date2_year < 2000){
                    $date2_year += 2000;
                }      

                $date1 = $date1_year . '-' . substr($arr[0], -4, -2) . '-' . substr($arr[0], -2);
                $date2 = $date2_year . '-' . substr($arr[1], -4, -2) . '-' . substr($arr[1], -2);

            }else{
                $date1_year = $arr[0] < 2000 ? $arr[0]+2000 : $arr[0];
                $date1 = $date1_year . '-' . $arr[1] . '-' . $arr[2];

                $date2_year = $arr[0] < 2000 ? $arr[0]+2000 : $arr[0];
                $date2= $date2_year . '-' . $arr[4] . '-' . $arr[5];
            }

            $sql = "DATE($column) BETWEEN '$date1' AND '$date2'";
        }
        //單一日期
        else{
            //開頭字元是比較符號 (不是數字開頭)
            if(preg_match('/^([^\d]+)\d+.*/', $dateString, $matches)){
                $operator = $matches[1];
                $dateString = str_replace($operator, '', $dateString); //remove operator
                //$symbles = ['>','<','=','>=', '<='];
            }else if(preg_match('/(^\d+.*)/', $dateString, $matches)){
                $operator = '=';
            }            
    
            if(preg_match('/(^\d{2,4}-\d{2}-\d{2}$)/', $dateString, $matches)){ //2023-05-01
                $arr = explode('-', $dateString);
                $date1_year = $arr[0] < 2000 ? $arr[0]+2000 : $arr[0];
                $date1String = $date1_year . '-' . $arr[1] . '-' . $arr[2];
            }else if(preg_match('/(^\d{6,8}$)/', $dateString, $matches)){ //230501, 0230501, 20230501
                $date1_year = substr($dateString, 0, -4);
                $date1_year = $date1_year < 2000 ? $date1_year+2000 : $date1_year;
                $date1String = $date1_year . '-' . substr($dateString, -4, -2) . '-' . substr($dateString, -2);
            }

            $validDateString = date('Y-m-d', strtotime($date1String));

            if($validDateString != $date1String){
                return false;
            }

            $date1 = date_create($date1String);            
            $date2 = date_add($date1, date_interval_create_from_date_string("1 days"));
            $date2String = $date2->format('Y-m-d');

            if($operator == '='){
                $sql = "$column >= '$date1String' AND $column < '$date2String'";
            }else{
                $sql = "DATE($column) $operator '$date1'";
            }
        }

        if($sql){
            return $sql;
        }

        return false;
    }


    public function unsetRelation($row, $relations)
    {
        if ($row instanceof \Illuminate\Database\Eloquent\Model) {
            foreach ($relations as $relation) {
                $row->setRelation($relation, null);
            }
            
        }
        else if(is_array($row)){
            foreach ($relations as $relation) {
                unset($row[$relation]);
            }
            
        }

        return $row;
    }

    /**
     * 注意：原本的 appends 欄位會消失
     */
    public function unsetRelations($rows, $relations)
    {
        foreach ($rows as $key => $row) {
            $rows[$key] = $this->unsetRelation($row, $relations);
        }

        return $rows;
    }


    public function unsetArrayRelations($rows, $relations)
    {
        foreach ($rows as $key => $row) {
            foreach ($relations as $relation) {
                unset($row[$relation]);
                $rows[$key] = $row;
            } 
        }

        return $rows;
        
    }

    public function getYmSnCode($modelName)
    {
        //  年份 2023年 取 23, 2123 取 123
        $year = (int)substr(date('Y'),1,3);
        $monty = sprintf("%02d",date('m'));
        $code_prefix = $year . $monty;

        $modelInstance = new $modelName;
        $current_max_code = $modelInstance->where('code', 'like', $code_prefix.'%')->max('code');

        $current_sn = (int)substr($current_max_code,-4);
        $new_sn = empty($current_sn) ? 1 : ($current_sn+1);
        $new_code = $code_prefix . sprintf("%04d",$new_sn) ;
        
        return $new_code;
    }



}