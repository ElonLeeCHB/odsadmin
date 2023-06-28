<?php

namespace App\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Repository
{
    public $modelName;
    public $model;
    public $table;
    public $table_columns;
    public $connection;

    private $zh_check = false;

    public function __construct()
    {
        $this->model = new $this->modelName;
        $this->table = $this->model->getTable();
    }

    private function initial($data)
    {
        // connection
        $this->connection = null;
        if(!empty($data['connection'])){
            $this->connection = $data['connection'];
        }

        // table columns
        if(empty($this->table_columns)){
            $this->table_columns = $this->getTableColumns($this->connection);
        }
    }


    //newModelInstance
    public function newModel()
    {
        return new $this->modelName;
    }


    // Use only id to search
    public function findIdOrNew($id)
    {
        $record = $this->newModel()->findOrNew($id);

        return $record;
    }

    /**
     * 用在 service 層的 updateOrCreate()
     */
    public function findIdOrFailOrNew($id)
    {
        //find
        if(!empty($id)){
            $row = $this->newModel()->where('id', $id)->firstOrFail();
        }
        //new
        else{
            $row = $this->newModel();
        }

        return $row;
    }


    // Use regexp to search
    public function firstOrNew($data)
    {
        $record = $this->getRow($data);

        if(empty($record)){
            $record = $this->newModel();
        }

        return $record;
    }


    public function getRow($data, $debug=0)
    {
        $query = $this->newModel()->query();

        $this->setFiltersQuery(query:$query,data:$data);

        // Sort
        if(empty($data['sort']) || $data['sort'] == 'id'){
            $sort = $this->model->getTable() . '.id';
        }else{
            $sort = $data['sort'];
        }

        // Order
        if (isset($data['order']) && ($data['order'] == 'ASC')) {
            $order = 'ASC';
        }
        else{
            $order = 'DESC';
        }

        $query->orderBy($sort, $order);

        // see the sql statement
        if(!empty($debug)){
            $this->getQueries($query);
        }

        return $query->first();
    }


    /**
     * $data['filter_foo']
     * $data['pagination']
     * $data['sort']
     * $data['order']
     * $data['limit']
     * $data['no_default_translation']  true,false
     */
    public function getRows($data=[], $debug=0)
    {
        $this->initial($data);

        $query = $this->newModel()->query();

        // With relations
        if(!empty($data['with'])){
            $this->setWith($query, $data['with']);
        }

        // With translation relation
        if(!empty($this->model->translatedAttributes)){
            $query->with('translation');
        }

        /*
        //本段要搭配套件 Astrotomic/laravel-translatable
        //暫時不用套件，自己處理
        if(!empty($data['whereTranslation'])){
            foreach ($data['whereTranslation'] as $key => $value) {
                $query->whereTranslation($key, $value);
            }
        }

        if(!empty($data['whereTranslation'])){
            $this->setTranslationFilter($data['whereTranslation'], $query);
        }
        */
        //End

        // Equal
        $this->setEqualsQuery($query, $data);

        // Like %some_value%
        $this->setFiltersQuery($query, $data);

        // WhereRawSqls
        if(!empty($data['WhereRawSqls']) && is_array($data['WhereRawSqls'])){
            foreach($data['WhereRawSqls'] as $rawsql){
                $query->whereRaw($rawsql);
            }
        }
        
        // Sort
        if(empty($data['sort']) || $data['sort'] == 'id'){
            $sort = $this->model->getTable() . '.id';
        }else{
            $sort = $data['sort'];
        }

        // Order
        if (isset($data['order']) && ($data['order'] == 'ASC')) {
            $order = 'ASC';
        }
        else{
            $order = 'DESC';
        }

        $query->orderBy($sort, $order);

        // Select
        $select = '';

        if(isset($data['select']) && $data['select'] !== '*'){
            $select = $data['select'];
        }else{
            $this->table = $this->model->getTable();
            $select = $this->table . '.*';
        }
        $query->select(DB::raw($select));

        // see the sql statement
        if(!empty($debug)){
            $this->getQueries($query);
        }

        // Limit
        if(!empty($data['limit'])){
            $limit = (int)$data['limit'];
        }else if(!empty(config('setting.config_admin_pagination_limit'))){
            $limit = (int)config('setting.config_admin_pagination_limit');
        }else{
            $limit = 10;
        }

        // Pagination
        $pagination = true;

        if(isset($data['pagination']) ){
            $pagination = (boolean)$data['pagination'];
        }

        if($pagination === true && $limit !== 0){
            $rows = $query->paginate($limit); // Get some rows per page
        }
        else if($pagination === false && $limit !== 0){
            $rows = $query->limit($limit)->get(); // Get some rows from beginning without pagination
        }
        else if($pagination === false && $limit == 0){
            $rows = $query->get(); // Get all
        }else{
            return false;
        }

        return $rows;
    }


    private function setEqualsQuery($query, $data)
    {
        // $data['whereEquals'] is multi dimension array
        if(!empty($data['whereEquals'])){
            foreach ($data['whereEquals'] as $column => $tmpdata) {
                if(is_array($tmpdata)){
                    $query->where(function ($query) use($column, $tmpdata) {
                        foreach ($tmpdata as $value) {
                            $query->orWhere($column, $value);
                        }
                    });

                }else{
                    $query->where($column, $tmpdata);
                }
            }
        }

        // $data['equals_column1']=123, $data['equals_column2']=456 ...
        foreach ($data as $key => $value) {

            $column = null;

            if(empty($value)){
                continue;
            }

            // Must Start with equals_
            if(str_starts_with($key, 'equals_')){
                $column = str_replace('equals_', '', $key);
            }else{
                continue;
            }

            if(!in_array($column, $this->table_columns)){ // Has to be the table's columns
                continue;
            }

            if(is_array($value)){ // value can not be array
                continue;
            }

            $value_array = explode(',', $value);
            if(count($value_array) > 1){
                $column = $this->table . '.' . $column;
                $query->whereIn($column, $value_array);
            }else{
                $column = $this->table . '.' . $column;
                $query->where($column, $value);
            }
        }

        return $query;
    }


    private function setFiltersQuery($query, $data, $debug=0)
    {

        // Filters - IDs
        if(!empty($data['filter_ids'])){
            if(!is_array($data['filter_ids'])){
                $data['filter_ids'] = [$data['filter_ids']];
            }
            $query->whereIn($this->table . '.id', $data['filter_ids']);
            unset($data['filter_ids']);
        }

        if(!empty($data['whereIn'])){
            foreach ($data['whereIn'] as $key => $arr) {
                $column = $this->table . '.' . $key;
                $query->whereIn($column, $arr);
            }
        }

        $connection = null;
        if(!empty($data['connection'])){
            $connection = $data['connection'];
        }

        if(empty($this->table_columns)){
            $this->table_columns = $this->getTableColumns($connection);
        }

        $translatedAttributes = $this->model->translatedAttributes ?? [];

        // Ignore is_active if -1
        if(isset($data['filter_is_active'] ) && $data['filter_is_active'] == -1){
            unset($data['filter_is_active']);
        }
        // End is_active

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

            // Has to be the table's columns
            if(!in_array($column, $this->table_columns)){
                continue;
            }

            // Translated column is not processed here
            if(in_array($column, $translatedAttributes)){
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

        // set translated whereHas then return data
        $data = $this->getDataWithTranslated($data);

        // Filters - relations
        if(!empty($data['whereHas'])){
            $this->setWhereHas($query, $data['whereHas']);
        }

        // Display sql statement
        if(!empty($debug)){
            $this->getQueries($query);
        }
    }


    public function setAndSubOrWhereQuery($query, $set)
    {
        $query->where(function ($query) use($set) {
            foreach ($set as $key => $value) {
                $query = $this->setWhereQuery($query, $key, $value,'orWhere');
            }
        });
    }


    public function update($data)
    {
        $model = $this->find($data['id']);

        foreach ($data as $key => $value) {
            if(in_array($key, $this->getTableColumns())){
                $model->$key = $value;
            }
        }

        if($model->save()){
            return $model->id;
        }
    }

    public function updateRows($where, $data)
    {
        $query = $this->newModel()->query();

        foreach ($where as $key => $value) {
            $query->where($key, $value);
        }

        foreach ($data as $key => $value) {
            if(!in_array($key, $this->getTableColumns())){
                unset($data[$key]);
            }
        }

        $result = $query->update($data);

        return $result;
    }

    public function saveRow($data)
    {
        $model = $this->find($data['id']);

        foreach ($data as $key => $value) {
            if(in_array($key, $this->getTableColumns())){
                $model->$key = $value;
            }
        }

        return $model->save();
    }


    public function updateOrCreate($wheres, $data)
    {
        $query = $this->newModel()->query();

        if(!is_array($data)){
            $data = (array)$data;
        }

        foreach ($data as $key => $value) {
            if(!in_array($key, $this->getTableColumns())){
                unset($data[$key]);
            }
        }

        $result = $query->updateOrCreate($wheres, $data);

        return $result;
    }


    public function upsert($allData, $whereColumns, $update)
    {
        $query = $this->newModel()->query();

        $query->upsert($allData, $whereColumns, $update); //updateOrCreate
    }


    public function create($data)
    {
        $model = $this->newModel();

        foreach ($data as $key => $value) {
            if(in_array($key, $this->getTableColumns())){
                $model->$key = $value;
            }
        }

        if($model->save()){
            return $model->id;
        }
    }

	public function getTableColumns($connection = null)
	{
        $table = $this->model->getTable();

        if(empty($connection)){
            return DB::getSchemaBuilder()->getColumnListing($table);
        }else{
            return DB::connection($connection)->getSchemaBuilder()->getColumnListing($table);
        }
	}


    public function isDebug($query, $debug = 0)
    {
        if(!empty($debug)){
            $debugData['sql'] = $query->toSql();
            $debugData ['bidings'] = $query->getBindings();
            echo "<pre>".print_r($debugData , 1)."</pre>"; exit;
        }
    }

    // Set translated attributes to main record
    public function setTranslatedAttributes($record)
    {
        if(!empty($record->translation)){
            foreach($record->translatedAttributes as $attribute){
                $record->$attribute = $record->translation->$attribute;
            }
        }

        return $record;
    }


    // Search for columns in translation table
    private function getDataWithTranslated($data)
    {
        $translatedAttributes = $this->model->translatedAttributes ?? [];

        foreach ($data as $key => $value) {
            if(!str_starts_with($key, 'filter_')){
                continue;
            }else{
                $column = str_replace('filter_', '', $key);
            }

            if(in_array($column, $translatedAttributes)){
                $data['whereHas']['translation'][$key] = $data[$key];
                unset($data[$key]);
            }
        }

        return $data;
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
            if($this->zh_check === true){
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
            if($this->zh_check === true){
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
            if($this->zh_check === true){
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
            if($this->zh_check === true){
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

            if($this->zh_check === true){
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

    // $funcData = $data['with']
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

    private function setWhereHas($query, $funcData)
    {
        foreach ($funcData as $rel_name => $relation) {
            $query->whereHas($rel_name, function($query) use ($relation) {
                foreach ($relation as $key => $value) {
                    $this->setWhereQuery($query, $key, $value, 'where');
                }
            });
        }
    }

    /*
    private function setTranslationJoin($query, $locale = null)
    {
        if(empty($locale)){
            $locale = \App::getLocale();
        }

        $translation_model = $this->getTranslationModel();

        if(!empty($translation_model)){
            $translation_table = $translation_model->getTable();
        }else{
            $translation_table = $this->table . '_translations';
        }

        // Ex. Product and ProductTranslation
        if(empty($translation_model->foreign_key)){
            $entity_singular_name = \Str::snake(class_basename($this->model));
            $foreign_key = 'trans.' . $entity_singular_name . '_id';
        }
        // Category and TermTranslation
        else{
            $foreign_key ='trans.' . $translation_model->foreign_key;
        }

        $entity_table = $this->newModel()->getTable();
        $entity_key = $entity_table . '.id';

        $query->leftJoin($translation_table .' as trans', function($join) use ($foreign_key, $entity_key, $locale){
            $join->on($foreign_key, '=', $entity_key);
            $join->where('trans.locale','=',$locale);
        });
    }
    */

    //本段要搭配套件 Astrotomic/laravel-translatable
    //$data['whereTranslation'] => $data
    public function setTranslationFilter($data, $query)
    {
        foreach ($data as $key => $value) {
            $column = str_replace('filter_', '', $key);
            $query->whereTranslationLike($column, "%".$value."%");
        }
    }

    /*
    public function delete($data)
    {
        $query = $this->newModel()->query();

        foreach($data as $key => $value){
            $query->where($key, $value);
        }

        $result = $query->delete();

        return $result;
    }
*/

    public function deleteRows($data, $debug=0)
    {
        $query = $this->newModel()->query();

        if(!empty($data['whereIn'])){
            foreach ($data['whereIn'] as $key => $arr) {
                $column = $this->table . '.' . $key;
                $query->whereIn($column, $arr);
            }
        }

        if(!empty($debug)){
            $this->getQueries($query);
        }

        $result = $query->delete();

        return $result;
    }

    public static function getQueries(Builder $builder)
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
     * Translation functions
     */


    public function translationModel($translationModelName = null)
    {
        if(empty($translationModelName)){
            $translationModelName = get_class($this->model) . 'Translation';
        }

        if(empty($translationModelName) && !empty($this->model->translationModelName)){ // Customized
            $translationModelName = $this->model->translationModelName;
        }

        return new $translationModelName();
    }

    /**
     * translation model should have $foreign_key
     */
    public function saveTranslationData($model, $data, $translatedAttributes=null)
    {
        if(empty($translatedAttributes)){
            $translatedAttributes = $this->model->translatedAttributes;
        }

        if(empty($translatedAttributes)){
            return false;
        }

        $translationModel = $this->translationModel();

        // foreign key
        $foreign_key = $translationModel->foreign_key ?? $model->getForeignKey();

        $foreign_key_value = $model->id;

        foreach($data as $locale => $value){
            $arr = [];
            if(!empty($value['id'])){
                $arr['id'] = $value['id'];
            }
            $arr['locale'] = $locale;
            $arr[$foreign_key] = $foreign_key_value;
            foreach ($translatedAttributes as $column) {
                if(!empty($value[$column])){
                    $arr[$column] = $value[$column];
                }
            }

            $arrs[] = $arr;
        }

        $this->translationModel()->upsert($arrs,['id', $foreign_key, 'locale']);
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

}
