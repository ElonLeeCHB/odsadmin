<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use App\Helpers\Classes\DataHelper;
use App\Helpers\Classes\CacheJsonHelper;
use App\Helpers\Classes\CacheSerializeHelper;

use App\Helpers\Classes\ChineseCharacterHelper;
use Illuminate\Support\Facades\Schema;

trait EloquentTrait
{
    private $initialized = false;
    public $zh_hant_hans_transform;
    public $meta_model_name;
    public $table_columns;
    public $translation_keys;

    public function initialize($data = null)
    {
        if($this->initialized){
            return true;
        }

        $this->model = new $this->modelName;
        $this->meta_model_name = $this->getMetaModelName();
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

        return new $this->modelName;
    }

    public function findIdFirst($id, $params = null)
    {
        $builder = $this->newModel()->query();

        if(!empty($params['with'])){
            $builder->with($params['with']);
        }

        $row = $builder->where('id', $id)->first();

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

        $this->initialize($data);
        
        $query = $this->newModel()->query();

        $query = $this->setQuery($query, $data, $debug);

        $rows = DataHelper::getResult($query, $data);

        return $rows;
    }

    public function getMetaValue($master_instance, $meta_key, $locale='')
    {
        $foreign_key = $master_instance->getForeignKey();

        $meta = $master_instance->meta_model_name::where($foreign_key, $master_instance->id)->where('meta_key', $meta_key)->where('locale', $locale)->first();


        if ($meta !== null) {
            return $meta->meta_value;
        }
        
        return null; // 或者根據需要返回任何適當的值

        //return $master_instance->meta_model_name::where($foreign_key, $master_instance->id)->where('meta_key', $meta_key)->where('locale', $locale)->first()->meta_value;
    }

    public function deleteRows($data, $debug = 0)
    {
        try {        
            $this->initialize($data);
    
            $builder = $this->newModel()->query();
    
            $builder = $this->setQuery($builder, $data, $debug);
    
            return $builder->delete();

        } catch (\Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }

    public function destroyById($id)
    {
        try {
            return $this->newModel()->destroy($id);
        } catch (\Exception $ex) {
            DB::rollback();
            throw $ex;        }
    }


    public function setQuery($query, $data, $debug = 0)
    {
        $data = $this->setIsActiveForData($data);
        
        $this->setSelect($query, $data);
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

    public function setGroupBy(&$query, $data)
    {
        if(!empty($data['groupBy'])){
            $query->groupBy($data['groupBy']);
        }
    }

    public function setSelectRaw(&$query, $data)
    {
        if(!empty($data['selectRaw'])){
            $query->selectRaw($data['selectRaw']);
        }
    }

    public function setWhereBetween(&$query, $data)
    {
        if(!empty($data['whereBetween']) && count($data['whereBetween']) == 3){
            $query->whereBetween($data['whereBetween'][0], [$data['whereBetween'][1], $data['whereBetween'][2]]);
        }
    }

    private function setIsActiveForData($data)
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

    private function setWith(&$query, $data)
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
    private function setWhereIn(&$query, $data)
    {
        // if(!empty($data['caller']) && $data['caller'] == 'MemberService'){
        //     echo "<pre>setWhereIn ".print_r($data, true)."</pre>";exit;
        // }
        
        if(!empty($data['whereIn'])){
            foreach ($data['whereIn'] as $column => $arr) {
                if(in_array($column, $this->table_columns) && is_array($arr) && !empty($arr)){
                    $query->whereIn($this->table . '.' . $column, $arr);
                }
            }
        }
    }

    private function setWhereNotIn(&$query, $data)
    {
        if(!empty($data['whereNotIn'])){
            foreach ($data['whereNotIn'] as $column => $arr) {
                $query->whereNotIn($this->table . '.' . $column, $arr);
            }
        }
    }

    /**
     * If $order_product: 
     * $data['whereHas] = ['product' => ['name' => 'something']];
     */
    private function setWhereHas(&$query, $data)
    {
        if(empty($data['whereHas'])){
            return $query;
        }

        foreach ($data['whereHas'] as $relation_name => $whereHasData) {
            
            // 只需要判斷是否有關聯記錄
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
                        $query = $this->setWhereQuery($subQuery, $key, $value, type:'where', table_columns:$relatedColumns, caller: $caller);
                    }
                }
            });
        }
    }

    private function setWhereDoesntHave($query, $data)
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

    private function setAndSubOrWhereQuery(&$query, $set)
    {
        $query->where(function ($qry) use(&$query, $set) {
            foreach ($set as $key => $value) {
                $query = $this->setWhereQuery($qry, $key, $value,'orWhere');
            }
        });
    }

    private function setOrWhere(&$query, $data)
    {
        foreach ($data['orWhere'] ?? [] as $key => $value) {
            // $this->setEqualQuery($query, $key, $value, 'orWhere');
            // $this->setEqualQuery($query, $key, $value, 'orWhere');
            $this->setWhereQuery($query, $key, $value, 'orWhere');
        }
    }

    private function setEqualsQuery($query, &$data, $debug=0)
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

    private function setFiltersQuery($query, &$data, $debug=0)
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
    private function setWhereQuery($query, $filter_key, $value, $type='where', $table_columns = [], $caller = '')
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

        if(!in_array($column , $table_columns)){
            return false;
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

    private function setWhereRawSqls(&$query, $data)
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

    private function setDistinct(&$query, $data)
    {
        if(!empty($data['distinct'])){
            $query->distinct();
        }

        return $query;
    }

    private function setSortOrder(&$query, $data)
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
     * $data['select] = ['col1', 'col2'];
     * $data['select] = 'col1, col2';
     */
    private function setSelect($query, $data)
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
    

    /**
     * 獲取 meta_data，並根據 meta_keys ，若 meta_key 不存在，設為空值 ''
     */
    public function setMetasToRow($row)
    {
        foreach ($row->metas ?? [] as $meta) {
            foreach($this->model->meta_keys ?? [] as $meta_key){
                $row->{$meta_key} = $meta->meta_value ?? '';
            }
        }
        
        return $row;
    }

    public function setTranslationMetasToRow($row)
    {
        $translation = $row->translation;

        foreach ($translation as $meta) {
            $row->{$meta->meta_key} = $meta->meta_value;
            unset($row->translation);
        }

        return $row;
    }


    public function saveRow($data, $id = null, $module = null)
    {
        try{
            DB::beginTransaction();

            $data['id'] = !empty($id) ? $id : null; // if 0, make it null

            $row = $this->findIdOrNew(id:$id);
    
            $table_columns = $this->getTableColumns();

            foreach($data as $column => $value){
                if(in_array($column, $table_columns)){
                    $row->{$column} = $value;
                }
            }
            
            if($row->isDirty()){
                $row->save();
            }
            
            DB::commit();

            return $row; // 必須回傳 model 物件，不能只回傳 id。因為可能會有後續動作，例如以此 model 實例做其它關聯更新，例如多語。

        } catch (\Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }


    /**
     * 比對表單欄位是否存在資料表欄位
     * 
     * last modified: 2024-01-23
     */
    public function setSaveDataByTableColumn($id, $data)
    {
        $result = [];

        $table_columns = $this->getTableColumns();

        foreach($data as $column => $value){
            if(in_array($column, $table_columns)){
                $result[$column] = $value;
            }
        }
        
        return $result;
    }

    /**
     * 儲存 Meta資料
     */
    public function saveMetas($metas, $modelInstance)
    {
        try{
            if(empty($modelInstance->meta_keys)){
                return null;
            }
    
            $update_data = [];
    
            $master_key = $modelInstance->getForeignKey();
    
            foreach($metas as $column => $value){
                if(in_array($column, $modelInstance->meta_keys ?? [])){
                    $update_data[] = [
                        $master_key => $modelInstance->id,
                        'locale' => '',
                        'meta_key' => $column,
                        'meta_value' => $value,
                    ];
                }
            }
            
            $result = '';

            if(!empty($update_data)){
                $modelInstance->meta_model_name::where($master_key, $modelInstance->id)->delete();
                $result = $modelInstance->meta_model_name::upsert($update_data, [$master_key,'locale','meta_key']);
            }

            return $result;

        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    public function saveTranslations($masterlInstance, $translations)
    {
        try{
            if(empty($masterlInstance->translation_keys)){
                return false;
            } 

            $translation_model_name = $masterlInstance->translation_model_name;
            $master_key = $masterlInstance->getForeignKey();
            $master_id  = $masterlInstance->id;
    
            // delete all translations
            $translation_model_name::where($master_key, $master_id)->forceDelete();
    
            // upsert
            $update_date = [];
    
            foreach($translations as $locale => $rows){
                $update_date[$locale][$master_key] = $masterlInstance->id;
                $update_date[$locale]['locale'] = $locale;
    
                foreach($rows as $column => $value){
                    if(in_array($column, $masterlInstance->translation_keys ?? [])){
                        $update_date[$locale][$column] = $value;
                    }
                }
            }

            if(!empty($update_date)){
                $result = $masterlInstance->translation_model_name::upsert($update_date, [$master_key,'locale']);
            }
    
            return $result;
            
        } catch (\Exception $ex) {
            throw $ex;
        } 
    }

    /**
     * 根據主模型 id 及 meta_key 強制刪除 meta 資料。不處理多語。
     * 
     * last modified: 2024-01-23
     */
    public function forceDeleteMeta($masterModel, $meta_keys)
    {
        try{
            DB::beginTransaction();

            $master_key = $masterModel->getForeignKey();
    
            $builder = $masterModel->meta_model_name::query();
            $builder->where($master_key, $masterModel->id);


            //不處理多語。
            $builder->where(function ($query) {
                $query->whereNull('locale')
                      ->orWhere('locale', '=', '');
            });

    
            foreach($meta_keys as $meta_key){
                $builder->where('meta_key', $meta_key);
            }

            
            // $result = $builder->get();
            $result = $builder->forceDelete();

            DB::commit();
            return $result;

        } catch (\Exception $e) {
            DB::rollback();
            return ['error' => $e->getMessage()];
        }
    }



    /**
     * filter_somecolumn 如果在翻譯表，則去翻譯表查詢。
     * 可能沒用
     */
    private function setTranslationsQuery($query, $data, $flag = 1)
    {

        if(empty($this->model->translation_keys)){
            return;
        }

        //判斷第一層 filter_column 是否存在
        $basic_translation_filter_data = [];

        foreach ($data ?? [] as $key => $value) {
            if(!str_starts_with($key, 'filter_')){
                continue;
            }

            $column = str_replace('filter_', '', $key);

            if(!in_array($column, $this->model->translation_keys)){
                continue;
            }

            $basic_translation_filter_data[$column] = $value;
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

            //進階查詢
            if(!empty($advanced_translation_filter_data)){
                if(!empty($advanced_translation_filter_data['andOrWhere'])){
                    foreach($advanced_translation_filter_data['andOrWhere'] as $set){
                        $qry->where(function($qry) use ($set){
                            foreach($set as $column => $value){
                                $qry->orWhere(function($qry) use ($column, $value){
                                    $this->setWhereQuery($qry, $column, $value, 'where');
                                });
                            }
                        });
                    }
                }
            }

        });


        // //順便查詢主表是否有同名欄位
        // 有問題，沒辦法
        // foreach ($data ?? [] as $key => $value) {
        //     if(str_starts_with($key, 'filter_') && in_array($column, $this->table_columns)){
        //         $column = str_replace('filter_', '', $key);
                
        //         $query = $this->setWhereQuery($query, $key, $value, 'orWhere');
        //     }
        // }
        
        return $query;
    }

    /**
     * 
     */
    private function getMetaModelName()
    {
        $this->meta_model_name = '';

        if (method_exists($this->model, 'getMetaModelName')) {
            $this->meta_model_name = $this->model->getMetaModelName();
        }

        return $this->meta_model_name;
    }

    private function extractColumnName(string $key): string {
        if (strpos($key, 'filter_') === 0) {
            $columnName = substr($key, strlen('filter_'));
        } elseif (strpos($key, 'equal_') === 0) {
            $columnName = substr($key, strlen('equal_'));
        } else {
            $columnName = '';
        }
    
        return $columnName;
    }


    /*
    controller的用法備份
    
        if(isset($query_data['equal_is_admin']) && $query_data['equal_is_admin'] == 0){
            $query_data['whereDoesntHave']['metas'] = ['is_admin' => 1];
            unset($query_data['equal_is_admin']);
        }
    */
}