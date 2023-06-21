<?php

namespace App\Traits\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

trait EloquentTrait
{
    protected $zh_check = false; // Traditional Chinese and Simplefied Chinese

    public $modelName;
    public $model;


    public function newModel()
    {
        $model = new $this->modelName;

        if(empty($this->model)){
            $this->model = $model;
        }

        return $model;
    }


    public function find($id, $data=[]) // return Model or Collection
    {
        //$record = $this->newModel()->find($id);
        $query = $this->newModel()->query();

        if(!empty($data['columns'])){
            $query->select($data['columns']);
        }

        if(!empty($data['with'])){
            $query->with($data['with']);
        }

        if(!empty($data['appends'])){
            $query->appends($data['appends']);
        }

        $record = $query->find($id);

        return $record;
    }


    public function findOrNew($id, $data=[])
    {
        $record = $this->find($id, $data);

        return $record ?? $this->newModel();
    }


    public function first($data): Model
    {
        $record = $this->getModelInstance($data);

        return $record;
    }


    public function firstOrNew($data): Model
    {
        $record = $this->newModel()->getModelInstance($data);

        return $record ?? $this->newModel();
    }


    public function getModelInstance($data, $debug=0): Model|null
    {
        $model = $this->newModel();
        $query = $model->query();

        $this->setFiltersQuery(query:$query,data:$data);

        // Sort
        if(empty($data['sort']) || $data['sort'] == 'id'){
            $sort = $model->getTable() . '.id';
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


    public function getModelCollection($data, $debug=0)
    {
        $model = $this->newModel();
        $query = $model->query();

        // Filters - model's table
        $this->setFiltersQuery($query, $data);

        // WhereRawSqls
        if(!empty($data['WhereRawSqls']) && is_array($data['WhereRawSqls'])){
            foreach($data['WhereRawSqls'] as $rawsql){
                $query->whereRaw($rawsql);
            }
        }

        // Sort
        if(empty($data['sort']) || $data['sort'] == 'id'){
            $sort = $model->getTable() . '.id';
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
            $select = $model->getTable() . '.*';
        }
        $query->select(DB::raw($select));

        // see the sql statement
        if(!empty($debug)){
            $this->getQueries($query);
        }

        // Limit
        if(isset($data['limit'])){
            $limit = (int)$data['limit'];
        }else if(config('settings.paginate_limit')){
            $limit = config('settings.paginate_limit');
        }else{
            $limit = 10;
        }

        if(empty($limit) || $limit > 1000){
            $limit = 1000;
        }

        if(!empty($data['real_limit'])){ // $data['real_limit'] don't open to public
            $limit = $data['real_limit'];
        }

        // Pagination
        $pagination = true;
        
        if(isset($data['pagination']) ){
            $pagination = $data['pagination'];
        }

        if($pagination == true){
            $records = $query->paginate($limit);
        }
        else if(!$pagination){
            $records = $query->limit($limit)->get();
        }

        return $records;
    }
    

    private function setFiltersQuery($query, $data, $debug=0)
    {
        // With relations
        if(!empty($data['with'])){
            $this->setWith($query, $data['with']);
        }

        // With translation relation
        if(!empty($this->model->translatedAttributes)){
            $query->with('translation');
        }

        // whereIn
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

        if(empty($this->table)){
            $this->table = $this->model->getTable();
        }
        
        $table_columns = $this->getColumns($this->table, $connection);

        $translatedAttributes = $this->model->translatedAttributes ?? [];

        //is_active
        if(!isset($data['filter_is_active']) && !isset($data['regexp'])){
            $data['filter_is_active'] = '=1';
        }
        else if(!isset($data['filter_is_active']) && !empty($data['regexp'])){
            $data['filter_is_active'] = '=1';
        }


        if(!isset($data['filter_is_active'])){ // If not set, default to 1
            if(!empty($data['regexp'])){
                $data['filter_is_active'] = '=1';
            }else{
                $data['filter_is_active'] = '1';
            }
        }else if($data['filter_is_active'] == -1){ // If -1, then unset()
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
            if(!in_array($column, $table_columns)){
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


    private function setWith($query, $data)
    {
        // $data['with'] = 'translation'
        if(!is_array($data)){
            $query->with($data);
        }else{
            foreach ($data as $key => $filters) {

                // Example: $data['with'] = ['products','members'];
                if(!is_array($filters)){
                    $query->with($data);
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


    private function setWhereHas($query, $data)
    {
        foreach ($data as $rel_name => $relation) {
            $query->whereHas($rel_name, function($query) use ($relation) {
                foreach ($relation as $key => $value) {
                    $this->setWhereQuery($query, $key, $value, 'where');
                }
            });
        }
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


    public function setAndSubOrWhereQuery($query, $set)
    {
        $query->where(function ($query) use($set) {
            foreach ($set as $key => $value) {
                $query = $this->setWhereQuery($query, $key, $value,'orWhere');
            }
        });
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
     * translation model should have $foreigh_key
     */
    public function saveTranslationData($record, $data, $translatedAttributes=null)
    {
        // translatedAttributes
        if(empty($translatedAttributes)){
            $translatedAttributes = $this->model->translatedAttributes;
        }

        if(empty($translatedAttributes)){
            return false;
        }

        // translationModel
        $translationModelName = get_class($record) . 'Translation';

        if(class_exists($translationModelName)){
            $translationModel = new $translationModelName;
        }else if(!empty($this->model->translationModelName)){
            $translationModel = new $this->model->translationModelName;
        }

        // foreigh_key
        $foreigh_key = $translationModel->foreign_key;
        $foreigh_key_value = $record->id;

        foreach($data as $locale => $value){
            $arr = [];
            if(!empty($value['id'])){
                $arr['id'] = $value['id'];
            }
            $arr['locale'] = $locale;
            $arr[$foreigh_key] = $foreigh_key_value;
            foreach ($translatedAttributes as $column) {
                if(!empty($value[$column])){
                    $arr[$column] = $value[$column];
                }
            }

            $arrs[] = $arr;
        }

        $this->translationModel->upsert($arrs,['id', $foreigh_key, 'locale']);
    }


	private function getColumns($table, $connection = null)
	{
        if(empty($connection)){
            return DB::getSchemaBuilder()->getColumnListing($table);
        }else{
            return DB::connection($connection)->getSchemaBuilder()->getColumnListing($table);
        }

	}


    public function getCount($data=[],$debug=0)
    {
        $query = $this->newModel()->query();

        // Filters - model's table
        $this->setFiltersQuery($query, $data, $debug);

        // see the sql statement
        if(!empty($debug)){
            $this->getQueries($query);
        }

        return $query->count();
    }

    
    public static function getQueries(Builder $builder)
    {
        $addSlashes = str_replace('?', "'?'", $builder->toSql());

        $arr['statement'] = vsprintf(str_replace('?', '%s', $addSlashes), $builder->getBindings());

        $arr['original'] = [
            'toSql' => $builder->toSql(),
            'bidings' => $builder->getBindings(),
        ];

        echo "<pre>".print_r($arr , 1)."</pre>"; exit;
    }

}