<?php

namespace App\Traits\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use PDO;
use App\Helpers\Classes\DataHelper;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
 * getDebugQueryContent()
 * getTranslationModel()
 * saveRow(), saveRowBasicData(), saveTranslationData(), saveRowMetaData()
 *
 * regexp
 * pagination
 * limit
 * optimize
 * sanitize
 */
trait EloquentTrait
{
    private $initialized = false;
    public $connection;
    public $table;
    public $table_columns;
    public $translation_keys;
    public $model;
    public $zh_hant_hans_transform;

    public function initialize($data = null)
    {
        if($this->initialized){
            return true;
        }

        $this->model = new $this->modelName;
        $this->table = $this->model->getTable();

        $this->table_columns = $this->getTableColumns();
        $this->translation_keys = $this->model->translation_keys ?? [];
        $this->zh_hant_hans_transform = false;
        $this->initialized = true;
    }

    public function newModel()
    {
        $model = new $this->modelName;

        if(empty($this->model)){
            $this->model = $model;
        }

        return $model;
    }


    public function findIdFirst($id, $data = null)
    {
        $row = $this->newModel()->where('id', $id)->first();

        return $row;
    }

    public function findIdOrNew($id, $params = null, $debug = 0)
    {
        //find
        if(!empty(trim($id))){
            $params['equal_id'] = $id;
            $row = $this->getRow($params);
        }

        //new
        if(empty($row) || empty($id)){
            $row = $this->newModel();
        }

        return $row;
    }

    public function findIdOrFailOrNew($id, $params = null, $debug = 0)
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
            return ['error' => 'findIdOrFailOrNew: Please check for more details'];
        }
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

        $query = $this->setQuery($data, $debug);

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
                $limit = config('settings.config_admin_pagination_limit');
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


    public function setQuery($data=[], $debug=0)
    {
        if(empty($this->table_columns)){
            $this->table_columns = $this->getTableColumns();
        }

        $query = $this->newModel()->query();

        // With relations
        // if(!empty($data['with'])){
        //     $this->setWith($query, $data['with']);
        // }
        $with = $data['with'] ?? [];
        $this->setWith($query, $with);

        $withCount = $data['withCount'] ?? '';
        $this->setWithCount($query, $withCount);
        //$query->withCount([$withCount]);

        // Has Relations
        $this->setHas($query, $data['has'] ?? []);
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
        if(!empty($this->model->translation_keys)){
            $query->with('translation');
        }


        // whereIn
        if(!empty($data['whereIn'])){
            foreach ($data['whereIn'] as $key => $arr) {
                $column = $this->table . '.' . $key;
                $query->whereIn($column, $arr);
            }
        }

        // whereNotIn
        if(!empty($data['whereNotIn'])){
            foreach ($data['whereNotIn'] as $key => $arr) {
                $column = $this->table . '.' . $key;
                $query->whereNotIn($column, $arr);
            }
        }


        // is_active can only be: 1, 0, -1, *
        // if(!is_array($this->table_columns)){
        //     echo '<pre>', print_r($this->table_columns, 1), "</pre>"; exit;
        // }
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

        // whereRawSqls
        if(!empty($data['whereRawSqls']) && is_array($data['whereRawSqls'])){
            foreach($data['whereRawSqls'] as $rawsql){
                $query->whereRaw('(' . $rawsql . ')');
            }
        }


        // Sort & Order
        if(!empty($data['sort']) && $data['sort'] == 'id' && !in_array($data['sort'], $this->table_columns)){
            unset($data['sort']);
        }

        //  - Order (default DESC)
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
        }
        // 未指定排序欄位，但資料表欄位有 sort_order
        else if(empty($data['sort']) && in_array('sort_order', $this->table_columns)){
            $query->orderBy('sort_order', 'ASC');
        }
        //  -- 其它情況
        else{
            if(empty($data['sort']) && in_array('id', $this->table_columns)){
                $sort = $this->model->getTable() . '.id';
            }
            else if(!empty($data['sort'])){
                $sort = $data['sort'];
            }

            if(!empty($sort) && !empty($order)){
                $query->orderBy($sort, $order);

            }
        }

        // Select
        if(isset($data['select'])){
            if(is_array($data['select'])){
                $query->select($data['select']);
            }else if($data['select'] !== '*'){
                $query->select(DB::raw($data['select']));
            }
        }else{
            //$query->select("{$this->table}.*");
            //dd($query->toSql());
        }

        // see the sql statement
        if(!empty($debug)){
            $this->getDebugQueryContent($query);
        }

        return $query;
    }


    private function setFiltersQuery($query, $data, $debug=0)
    {
        $translation_keys = $this->model->translation_keys ?? [];
        $table_columns = $this->getTableColumns($this->connection);

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

        // Filters - relations
        if(!empty($data['whereHas'])){
            $this->setWhereHas($query, $data['whereHas']);
        }

        // Filters - relations
        if(!empty($data['whereDoesntHave'])){
            $this->setWhereDoesntHave($query, $data['whereDoesntHave']);
        }

        // Display sql statement
        if(!empty($debug)){
            $this->getDebugQueryContent($query);
        }
    }


    private function setEqualsQuery($query, $data)
    {
        $table_columns = $this->getTableColumns($this->connection);
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

            if(is_array($value) || empty($value)){ // value can not be empty or array
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
        foreach ($data ?? []  as $key => $value) {
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

    private function setWhereDoesntHave($query, $data)
    {
        foreach ($data as $relation_name => $relation) {
            $query->whereDoesntHave($relation_name, function($query) use ($relation) {
                foreach ($relation as $column => $value) {
                    $query->where('meta_key', $column)->where('meta_value', $value);
                }
            });
        }
    }

    private function setWith($query, $input)
    {
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

        if(is_string($input)){
            $width_arr[] = $input;
        }else{
            $width_arr = $input;
        }

        // if($has_translation){
        //     $width_arr[] = 'translation';
        // }
        //
        $width_arr = array_unique($width_arr);

        if(!is_array($width_arr)){
            $query->with($width_arr);
        }else{
            foreach ($width_arr as $key => $with) {

                // Example: $data['with'] = ['products','members'];
                if(!is_array($with)){
                    $query->with($with);
                }

                /* Example:
                $data['with'] = [
                    'products' => ['slug' => 'someCategory', 'is_active' => 1],
                    'orders' => ['amount' => '>1000']
                ];
                */
                else{
                    echo '<pre>', print_r('這裡是 setWith, 將要廢棄', 1), "</pre>"; exit;
                    // // 注意：with 裡面使用Closure函數，只是過濾 with 表，然後附加過來。不會過濾主表
                    // $query->with([$key => function($query) use ($key, $filters) {
                    //     foreach ($filters as $column => $value) {
                    //         //$query = $this->setWhereQuery($query, $column, $value, 'where');
                    //         $query->where("$key.$column", '=', $value);
                    //     }
                    // }]);
                }
            }
        }

        return $query;
    }

    private function setWithCount($query, $input)
    {
        if(!empty($input)){
            $query->withCount($input);
        }

        return $query;
    }

    private function setHas($query, $input)
    {
        $hasArray = DataHelper::addToArray([], $input);

        foreach ($hasArray as $value) {
            $query->has($value);
        }

        return $query;
    }

    public function getTableColumns($connection = null)
    {
        // already exist
        if(!empty($this->table_columns) && is_array($this->table_columns)){
            return $this->table_columns;
        }

        // get from cache
        if(empty($this->table)){
            $this->table = $this->model->getTable();
        }

        $cache_name = 'cache/table_columns/' . $this->table . '.json';

        $this->table_columns = DataHelper::getJsonFromStoragNew($cache_name);

        if(!empty($this->table_columns)){
            return $this->table_columns;
        }

        // get from database
        if(empty($this->model->connection) ){
            $this->table_columns = DB::getSchemaBuilder()->getColumnListing($this->table);
        }else{
            $this->table_columns = DB::connection($this->model->connection)->getSchemaBuilder()->getColumnListing($this->table);
        }
        DataHelper::setJsonToStorage($cache_name, $this->table_columns);

        return DataHelper::getJsonFromStoragNew($cache_name);
    }

    // For debug
    public static function getDebugQueryContent(Builder $builder)
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

    /**
     * Save
     */

    // must be public
    /**
     * saveRowBasicData()
     * saveRowTranslationData()
     * saveRowMetaData()
     */
    public function saveRow($id, $post_data)
    {
        $this->initialize();

        try{

            $modelInstance = $this->findIdOrFailOrNew($id)['data'] ?? '';

            // save basic data
            $result = $this->saveRowBasicData($modelInstance, $post_data);

            if(!empty($result['error'])){
                throw new \Exception($result['error']);
            }else{
                $id = $result;
            }

            //$modelInstance->refresh();
            $result = null;

            // save translation data
            if(!empty($post_data['translations'])){
                $result = $this->saveRowTranslationData($modelInstance, $post_data['translations']);

                if(!empty($result['error'])){
                    throw new \Exception($result['error']);
                }
            }

            // save meta data
            $result = $this->saveRowMetaData($modelInstance, $post_data);

            if(!empty($result['error'])){
                throw new \Exception($result['error']);
            }


            return ['id' => $id];

        } catch (\Exception $ex) {
            return ['error' => $ex->getMessage()];
        }

    }

    // must be public
    public function saveRowBasicData($modelInstance, $post_data)
    {
        $this->initialize();

        try{
            DB::beginTransaction();

            // If $model->fillable exists, save() then return
            if(!empty($modelInstance->getFillable())){
                $modelInstance->fill($post_data);
                $modelInstance->save();
                return $modelInstance->id;
            }

            // Save matched columns
            $table_columns = $this->table_columns;
            $form_columns = array_keys($post_data);

            foreach ($form_columns as $key => $column) {
                if(!in_array($column, $table_columns)){
                    continue;
                }

                $modelInstance->$column = $post_data[$column];
            }

            $modelInstance->save();

            DB::commit();

            return $modelInstance->id;

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => 'Error code: ' . $ex->getCode() . ', Message: ' . $ex->getMessage()];
        }
    }

    // must be public
    public function saveRowTranslationData($masterModelInstance, $translation_data)
    {
        $this->initialize();

        try{
            $translation_model = $this->model->getTranslationModel();

            // master
            $master_key = $translation_model->master_key ?? $masterModelInstance->getForeignKey();
            $master_key_value = $masterModelInstance->id;

            foreach($translation_data as $locale => $value){
                $arr = [];
                if(!empty($value['id'])){
                    $arr['id'] = $value['id'];
                }
                $arr['locale'] = $locale;
                $arr[$master_key] = $master_key_value;
                foreach ($this->model->translation_keys as $column) {
                    if(!empty($value[$column])){
                        $arr[$column] = $value[$column];
                    }else{
                        $arr[$column] = '';
                    }
                }

                $arrs[] = $arr;
            }

            DB::beginTransaction();
            $translation_model->upsert($arrs,['id', $master_key, 'locale']);
            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }

    // 這是比較舊的寫法
    public function saveTranslationData($master_model, $translation_data)
    {
        try{
            if(empty($master_model->translation_keys)){
                return false;
            }else{
                $translation_keys = $master_model->translation_keys;
            }

            // translationModel
            $translationModelName = get_class($master_model) . 'Translation';
            if(class_exists($translationModelName)){
                $translationModel = new $translationModelName;
            }else{
                return false;
            }

            // foreigh_key
            $foreigh_key = $translationModel->foreign_key;
            $foreigh_key_value = $master_model->id;

            foreach($translation_data as $locale => $value){
                $arr = [];
                if(!empty($value['id'])){
                    $arr['id'] = $value['id'];
                }
                $arr['locale'] = $locale;
                $arr[$foreigh_key] = $foreigh_key_value;
                foreach ($translation_keys as $column) {
                    if(!empty($value[$column])){
                        $arr[$column] = $value[$column];
                    }
                }

                $arrs[] = $arr;
            }

            DB::beginTransaction();
            $translationModel->upsert($arrs,['id', $foreigh_key, 'locale']);
            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }

    // must be public
    public function saveRowMetaData($masterModelInstance, $post_data)
    {
        $this->initialize();

        try {
            $meta_model = $masterModelInstance->getMetaModel();

            if(empty($meta_model)){
                return ;
            }

            // Keys
            $master_key = $meta_model->master_key ?? $masterModelInstance->getForeignKey();

            $master_key_value = $masterModelInstance->id;

            //先取出舊資料
            $all_meta = $masterModelInstance->metas()->get()->keyBy('meta_key')->toArray();

            //全刪
            $masterModelInstance->metas()->where($master_key, $master_key_value)->delete();
            $upsert_data = [];

            foreach($post_data as $column => $value){
                if(!in_array($column, $this->model->meta_keys) || empty($value)){
                    continue;
                }

                $arr['id'] = $all_meta[$column]['id'] ?? null; // 將原本的 id 值塞回去。
                $arr[$master_key] = $master_key_value;
                $arr['meta_key'] = $column;
                $arr['meta_value'] = $value;
                $upsert_data[] = $arr;
            }

            if(!empty($upsert_data)){
                DB::beginTransaction();
                $result = $meta_model->upsert($upsert_data,['id']);
                DB::commit();
            }

            return true;

        } catch (\Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }

    // public function saveTranslationData($masterModel, $data, $translation_keys=null)
    // {
    //     if(empty($translation_keys)){
    //         $translation_keys = $this->model->translation_keys;
    //     }

    //     if(empty($translation_keys)){
    //         return false;
    //     }

    //     $translationModel = $this->model->getTranslationModel();

    //     // foreign key
    //     $foreign_key = $translationModel->foreign_key ?? $masterModel->getForeignKey();

    //     $foreign_key_value = $masterModel->id;

    //     foreach($data as $locale => $value){
    //         $arr = [];
    //         if(!empty($value['id'])){
    //             $arr['id'] = $value['id'];
    //         }
    //         $arr['locale'] = $locale;
    //         $arr[$foreign_key] = $foreign_key_value;
    //         foreach ($translation_keys as $column) {
    //             if(!empty($value[$column])){
    //                 $arr[$column] = $value[$column];
    //             }
    //         }

    //         $arrs[] = $arr;
    //     }

    //     $translationModel->upsert($arrs,['id', $foreign_key, 'locale']);
    // }

    /**
     * 獲取 meta_data，並根據 meta_keys ，若 meta_key 不存在，設為空值 ''
     */
    public function setMetasToRow($row)
    {
        $metas = $row->metas;

        foreach ($metas as $meta_data) {
            $row->{$meta_data->meta_key} = $meta_data->meta_value;
        }

        return $row;
    }

    public function setMetasToRows($rows)
    {
        foreach ($rows as $row) {
            $this->setMetasToRow($row);
        }

        return $rows;
    }

    //
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

    public function destroyRows($data, $debug = 1)
    {
        try {
            DB::beginTransaction();

            $query = $this->setQuery($data, $debug);

            if(!empty($debug)){
                $this->getDebugQueryContent($query);
            }

            $result = $query->delete();

            DB::commit();

            return $result;

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
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

        // 只允許數字或-或/或:
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




    public function saveStatusCode($data)
    {
        DB::beginTransaction();

        try {
            $params = [
                'equal_id' => $data['id'],
                'select' => ['id', 'status_code'],
            ];
            $row = $this->getRow($params);

            $row->status_code = $data['status_code'];
            $row->save();

            DB::commit();

            $result['data'] = [
                'id' => $row->id,
                'code' => $row->code,
                'status_code' => $row->status_code,
                'status_name' => $row->status_name
            ];
            return $result;

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }
}
