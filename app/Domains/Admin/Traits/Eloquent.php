<?php

namespace App\Domains\Admin\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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
 * getQueries()
 * getTranslationModel()
 * saveTranslationData()
 * setMetaDataset()
 * saveMetaDataset()
 * toStdObj()
 */
trait Eloquent
{
    public function initialize($data)
    {
        $this->model = new $this->modelName;
        $this->table = $this->model->getTable();

        if(!empty($data['connection'])){
            $this->connection = $data['connection'];
        }else{
            $this->connection = DB::connection()->getName();
        }

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

    public function findIdFirst($id, $data = null)
    {
        $record = $this->newModel()->where('id', $id)->first();

        return $record;
    }

    public function findIdOrFailOrNew($id, $data = null)
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


    public function getRow($data, $debug=0): Model|null
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
    public function getRows($data=[], $debug=0)
    {
        $this->initialize($data);

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
        
        if(!empty($data['whereIn'])){
            foreach ($data['whereIn'] as $key => $arr) {
                $column = $this->table . '.' . $key;
                $query->whereIn($column, $arr);
            }
        }

        // Equal
        $this->setEqualsQuery($query, $data);

        // Like %some_value%
        $this->setFiltersQuery($query, $data);

        if(!empty($data['whereHas'])){
            foreach ($data['whereHas'] as $rel_name => $relation) {
                $query->whereHas($rel_name, function($query) use ($relation) {
                    foreach ($relation as $key => $value) {
                        $this->setWhereQuery($query, $key, $value, 'where');
                    }
                });
            }
        }

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

        // get result
        $result = [];

        if(isset($data['first']) && $data['first'] = true){
            $result = $query->first();
        }else{
            // Limit
            if(isset($data['limit'])){
                $limit = (int)$data['limit'];
            }else if(!empty(config('setting.config_admin_pagination_limit'))){
                $limit = (int)config('setting.config_admin_pagination_limit');
            }else{
                $limit = 10;
            }
    
            // Pagination
            if(isset($data['pagination']) ){
                $pagination = (boolean)$data['pagination'];
            }else{
                $pagination = true;
            }
    
            if($pagination == true && $limit != 0){
                $result = $query->paginate($limit); // Get some rows per page
            }
            else if($pagination == false && $limit != 0){
                $result = $query->limit($limit)->get(); // Get some rows without pagination
            }
            else if($pagination == false && $limit == 0){
                $result = $query->get(); // Get all
            }
        }

        return $result;
    }

    private function setFiltersQuery($query, $data, $debug=0)
    {
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
            if(!in_array($column, $this->getTableColumns())){
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
        if(in_array($column, $translatedAttributes)){
            $data['whereHas']['translation'][$key] = $data[$key];
            unset($data[$key]);
        }

        // Filters - relations


        // Display sql statement
        if(!empty($debug)){
            $this->getQueries($query);
        }
    }


    private function setEqualsQuery($query, $data)
    {
        foreach ($data as $key => $value) {

            $column = null;
            
            if(str_starts_with($key, 'equal_')){ // Must Start with equals_
                $column = str_replace('equal_', '', $key);
            }else{
                continue;
            }

            if(is_array($value)){ // value can not be empty or array
                continue;
            }

            $this->getTableColumns();
            if(!in_array($column, $this->table_columns)){ // Has to be the table's columns
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

        return $query;
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
            if($this->zh_hant_hans_transform === true){
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
        if(!empty($this->table_columns)){
            return $this->table_columns;
        }

        if(!empty($this->table)){
            $this->table = $this->model->getTable();
        }

        if(empty($connection) ){
            $this->table_columns = DB::getSchemaBuilder()->getColumnListing($this->table);
        }else{
            $this->table_columns = DB::connection($connection)->getSchemaBuilder()->getColumnListing($this->table);
        }

        return $this->table_columns;
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
     * Translation
     */

     public function getTranslationModel($translationModelName = null)
    {
        if(empty($translationModelName)){
            $translationModelName = get_class($this->model) . 'Translation';
        }

        if(empty($translationModelName) && !empty($this->model->translationModelName)){ // Customized
            $translationModelName = $this->model->translationModelName;
        }

        return new $translationModelName();
    }

    public function saveTranslationData($masterModel, $data, $translatedAttributes=null)
    {
        if(empty($translatedAttributes)){
            $translatedAttributes = $this->model->translatedAttributes;
        }

        if(empty($translatedAttributes)){
            return false;
        }

        $translationModel = $this->getTranslationModel();

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
            foreach ($translatedAttributes as $column) {
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
     public function setMetaDataset($row)
    {
        $indexed_meta_dataset = [];
        $meta_keys = $row->meta_keys;
        $meta_dataset = $row->meta_dataset;

        foreach ($meta_dataset as $meta_data) {
            $indexed_meta_dataset[$meta_data->meta_key] = $meta_data->meta_value;
            $existed_meta_keys[] = $meta_data->meta_key;
        }

        foreach ($meta_keys as $meta_key) {
            if(empty($indexed_meta_dataset[$meta_key] )){
                $indexed_meta_dataset[$meta_key] = '';
            }
        }

        return (object)$indexed_meta_dataset;
    }

    /**
     * saveMetaDataset
     */
    public function saveMetaDataset($masterModel, $data)
    {
        $organization_id = $masterModel->id;

        $existed_meta_data = $masterModel->meta_dataset()->select('id','meta_key')->get();

        $existed_meta_keys = [];

        foreach ($existed_meta_data as $row) {
            $existed_meta_keys[] = $row->meta_key;
            $existed_meta_key_ids[$row->meta_key] = $row->id;
        }

        foreach ($data as $key => $value) {

            if(str_starts_with($key, 'meta_data_')){
                $column = str_replace('meta_data_', '', $key);
            }else{
                continue;
            }

            if(empty($value)){
                continue;
            }

            if(!in_array($column, $masterModel->meta_keys)){
                continue;
            }

            $new_meta_data[] = ['organization_id' => $organization_id, 'meta_key' => $column, 'meta_value' => $value];

            $new_meta_keys[] = $column;
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
            $masterModel->meta_dataset()->getRelated()->upsert($new_meta_data, ['organization_id','meta_key']);
        }
    }

    public function toStdObj($row)
    {
        $arr = $row->toArray();

        if(!empty($arr['translation'])){
            unset($arr['translation']);
        }

        return (object)$arr;
    }
}