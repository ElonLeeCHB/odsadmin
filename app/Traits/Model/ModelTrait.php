<?php

namespace App\Traits\Model;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\Classes\DataHelper;
use Illuminate\Support\Facades\Schema;
use App\Libraries\EloquentLibrary;
use App\Helpers\Classes\EloquentHelper;

trait ModelTrait
{
    public function __get($key)
    {
        // 查詢 metas 關聯中是否有對應的 meta_key
        if (!empty($this->meta_keys) && array_key_exists($key, $this->meta_keys)) {
            if (!$this->relationLoaded('metas')) {
                $this->load('metas');
            }

            if($this->metas){
                $meta = $this->metas->firstWhere('meta_key', $key);
            }

            if ($meta) {
                return $meta->meta_value; // 如果找到，返回對應的 meta_value
            }
        }

        // 返回原本應有的內容
        return parent::__get($key);
    }
    
    // Attribute

    public function createdYmd(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($this->created_at)->format('Y-m-d') ?? '',
        );
    }

    public function updatedYmd(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($this->updated_at)->format('Y-m-d') ?? '',
        );
    }

    public function createdYmdhi(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($this->created_at)->format('Y-m-d H:i') ?? '',
        );
    }

    public function updatedAtYmdhi(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($this->updated_at)->format('Y-m-d H:i') ?? '',
        );
    }


    // Relations

    public function metas()
    {
        $meta_model_name = get_class($this) . 'Meta';
        return $this->hasMany($meta_model_name);
    }

    public function translation()
    {
        // Using SomeTranslation
        if(!isset($this->translation_model_name) || str_ends_with($this->translation_model_name, 'Translation')){
            $translation_model_name = get_class($this) . 'Translation';
            $translation_model = new $translation_model_name();
            $key_name = $translation_model->foreign_key;

            return $this->hasOne($translation_model::class, $key_name)->where('locale', app()->getLocale());
        }

        // Using SomeMeta
        else if (isset($this->translation_model_name) && substr($this->translation_model_name, -4) === 'Meta') {
            return $this->metas()->where('locale', app()->getLocale());
        }
    }

    public function translations()
    {
        if(empty($this->translation_keys)){
            return false;
        }

        // Using SomeTranslation
        if(!isset($this->translation_model_name) || str_ends_with($this->translation_model_name, 'Translation')){
            $translation_model_name = get_class($this) . 'Translation';
            $translation_model = new $translation_model_name();

            return $this->hasMany($translation_model::class);
        }
        // Using SomeMeta
        else if (isset($this->translation_model_name) && substr($this->translation_model_name, -4) === 'Meta') {
            return $this->metas()->whereNotNull('locale')->where('locale', '<>', '');
        }
    }

    public function getPrefix()
    {
        $connection = $this->getConnectionName();

        return config("database.connections.{$connection}.prefix", '');
    }

    public function getMetaModel()
    {
        if(!empty($this->meta_model)){
            $meta_model = $this->meta_model;
        }else{
            $meta_model = get_class($this) . 'Meta';
        }

        if (class_exists($meta_model)) {
            return new $meta_model();
        }

        return false;
    }

    public function getTranslationModel()
    {
        if(!empty($this->translation_model_name)){
            $translation_model_name = $this->getModelNamespace() . '\\'.$this->translation_model_name;
        }else{
            $translation_model_name = get_class($this) . 'Translation';
        }

        if (class_exists($translation_model_name)) {
            return new $translation_model_name;
        }

        return null;
    }

    public function getTranslationTable()
    {
        return optional($this->getTranslationModel())->getTable();
    }

    public function getTranslationMasterKey()
    {
        $translation_model = $this->getTranslationModel();

        if(!empty($translation_model->master_key)){
            return $translation_model->master_key;
        }else if(!empty($this->translation_master_key)){
            return $this->translation_master_key;
        }else{
            return $this->getForeignKey();
        }
    }

    public function getTranslationKeys()
    {
        return $this->translation_keys ?? [];
    }

    // 目前用在自定義的 App\Providers\SettingServiceProvider，為了在沒有 settings 表存在的時候系統也能運行;
    public function tableExists()
    {
        $connection = $this->getConnection();
        $tableName = $this->getTable();
        $schemaBuilder = $connection->getSchemaBuilder();

        return $schemaBuilder->hasTable($tableName);
    }



    //public function setNumberAttribute($value, $to_fixed = 0, $keep_zero = 0)
    public function setNumberAttribute($value, $to_fixed = null, $keep_zero = null)
    {
        // 取出時不可在此加上千分位符號。若被用來計算會出錯。

        return Attribute::make(
            get: function ($value) use ($keep_zero, $to_fixed){
                if(is_numeric($to_fixed)){
                    $value = round($value, 4);
                }

                if($keep_zero === false){ //remove zero after the decimal point
                    $value = preg_replace('/\.0+$/', '', $value);
                }
                return $value;
            },
            set: function ($value) use ($to_fixed){
                $value = empty($value) ? 0 : $value; // if empty, set to 0
                $value = str_replace(',', '', $value); // remove comma. only work for string, not for number

                if(is_numeric($to_fixed)){
                    $value = round($value, 4);
                }
                return $value;
            }
        );
    }


    // Custom Functions

    public function getTableColumns($connection = null)
    {
        $table = $this->getTable();

        $cache_name = 'cache/table_columns/' . $table . '.json';

        $table_columns = DataHelper::getJsonFromStoragNew($cache_name);

        if(!empty($table_columns)){
            return $table_columns;
        }


        /* If no cache */

        if(empty($this->connection) ){
            $table_columns = DB::getSchemaBuilder()->getColumnListing($table); // use default connection
        }else{
            $table_columns = DB::connection($this->connection)->getSchemaBuilder()->getColumnListing($table);
        }
        DataHelper::setJsonToStorage($cache_name, $table_columns);

        return DataHelper::getJsonFromStoragNew($cache_name);
    }

    /**
     * $this->toArray();            // Original attributes, relationships. Contain accessor if defined in $append.
     * $this->getAttributes();      // Original attributes, no relationships. No accessor !
     * $this->attributesToArray();  // Current attributes, no relationships. Contain accessor if defined in $append.
     */
    public function toCleanObject()
    {
        // get all keys
        $table = $this->getTable();
        $table_columns = $this->getTableColumns();
        $attributes = $this->attributesToArray();
        $attribute_keys = array_keys($attributes);
        $all_keys = array_unique(array_merge($table_columns, $attribute_keys, $this->meta_keys ?? []));
        $casts = $this->casts;

        $result = [];

        foreach ($all_keys as $key) {
            $value = $this->{$key} ?? '';

            if(!is_array($key)){

                // Apply cast type if needed
                if (isset($casts[$key])) {
                    $castType = $casts[$key];
                    
                    // datetime no format
                    if ($castType === 'datetime') {
                        $value = $value instanceof \Carbon\Carbon ? $value->format('Y-m-d H:i:s') : $value;
                    } 
                    // datetime with format
                    else if (strpos($castType, 'datetime:') === 0) {
                        // Handle custom datetime format
                        $format = substr($castType, 9);
                        $result[$key] = $value instanceof \Carbon\Carbon ? $value->format($format) : $value;
                    }
                }else{
                    $result[$key] = $value;
                }
            }
        }

        return (object) $result;
    }
    
    public function toCleanObjectRecursively()
    {
        // Get all keys
        $table = $this->getTable();
        $table_columns = $this->getTableColumns();
        $attributes = $this->attributesToArray();
        $attribute_keys = array_keys($attributes);
    
        // Merge all keys and remove duplicates
        $all_keys = array_unique(array_merge($table_columns, $attribute_keys, $this->meta_keys ?? []));
        
        // Get the cast settings
        $casts = $this->casts;
        
        $result = [];
    
        // Process regular attributes
        foreach ($all_keys as $key) {
            $value = $this->{$key} ?? '';
    
            // Handle relationships (e.g., orderProducts)
            if ($value instanceof \Illuminate\Database\Eloquent\Collection) {
                // If it's a collection, convert it into an array of objects
                $result[$key] = $value->map(function ($item) {
                    return $item->toCleanObject();
                })->toArray(); // Convert to array
            } elseif ($value instanceof \Illuminate\Database\Eloquent\Model) {
                // If it's a related model (not a collection), convert it directly
                $result[$key] = $value->toCleanObject();
            } else {
                // Apply cast type if needed
                if (isset($casts[$key])) {
                    $castType = $casts[$key];
                    
                    // Handle datetime casting
                    if ($castType === 'datetime') {
                        $value = $value instanceof \Carbon\Carbon ? $value->format('Y-m-d H:i:s') : $value;
                    } elseif (strpos($castType, 'datetime:') === 0) {
                        // Handle custom datetime format
                        $format = substr($castType, 9);
                        $value = $value instanceof \Carbon\Carbon ? $value->format($format) : $value;
                    }
                }
    
                $result[$key] = $value;
            }
        }
    
        // Handle relationships that are not part of the $all_keys
        foreach ($this->getRelations() as $relation => $relationModel) {
            if ($relationModel instanceof \Illuminate\Database\Eloquent\Collection) {
                // If it's a collection, convert it into an array of objects
                $result[$relation] = $relationModel->map(function ($item) {
                    return $item->toCleanObject();
                })->toArray(); // Convert to array
            } elseif ($relationModel instanceof \Illuminate\Database\Eloquent\Model) {
                // If it's a related model (not a collection), convert it directly
                $result[$relation] = $relationModel->toCleanObject();
            }
        }
    
        return (object) $result;
    }

    //處理 guarded()。原本 model 內建的 create() 會受 fillable() 限制。但是若使用 guarded() 不會包含在 fillable() 裡面。因此新增判斷
    public function getFillable()
    {
        $fillable = parent::getFillable();

        if (empty($fillable)) {
            $table_columns = $this->getTableColumns();

            $fillable = array_diff($table_columns, $this->getGuarded());
        }

        if(empty($fillable)){
            $fillable = $table_columns;
        }

        // 排除 $guarded 中列出的欄位
        return $fillable;
    }


    // scope
        public function scopeActive($query)
        {
            return $query->where('is_active', 1);
        }

        public function scopeApplyFilters($builder, $params = [], $debug = 0)
        {
            if(empty($params)){
                $params = request()->all();
            }

            $table_columns = $this->getTableColumns();
            $this->table = $this->getTable();

            // select
                if(empty($params['select'])){
                    $select = $table_columns;
                }else{
                    $select = array_intersect($params['select'], $table_columns);
                }
                // 刪除多語欄位，稍後排序會join，但是跟多語欄位會有歧義 ambiguous 問題，id, name
                $select = array_diff($select, $this->translation_keys ?? []);

                foreach ($select ?? [] as $key => $column) {
                    $select[$key] = $this->table . '.' . $column;
                }
                
                $builder->select($select);
            //

            $eloquentLibrary = new EloquentLibrary($this);

            // translation 要重新改寫
            $eloquentLibrary->setTranslationsQuery($builder, $params, $debug);

            // IS ACTIVE
                // 沒設定 equal_is_active 的時候，預設=1
                if(!isset($params['equal_is_active'])){
                    $params['equal_is_active'] = 1;
                }
                // 存在 equal_is_active, 但值 = '*'
                else if($params['equal_is_active'] == '*'){
                    unset($params['equal_is_active']);
                }
            //

            // 過濾值
                foreach ($params as $key => $value) {

                    if (str_starts_with($key, 'filter_')) {
                        $column = str_replace('filter_', '', $key);
                    }else if (str_starts_with($key, 'equal_')) {
                        $column = str_replace('equal_', '', $key);
                    }else{
                        $column = $key;
                    }

                    if(!empty($this->translation_keys) && in_array($column, $this->translation_keys)){
                        continue;
                    }

                    // 處理 filter_ 開頭的參數
                    if (str_starts_with($key, 'filter_')) {
                        $column = substr($key, 7); // 去掉 'filter_'

                        if(!in_array($column, $this->getTableColumns())){
                            continue;
                        }
                        
                        // 檢查是否包含範圍操作符 > 或 <
                        if (str_starts_with($key, '>')) {
                            $val = trim(substr($value, 1));
                            $builder->where($column, '>', $val);
                        } else if (str_starts_with($key, '<')) {
                            $val = trim(substr($value, 1));
                            $builder->where($column, '<', $val);
                        } elseif (strpos($value, '*') !== false) {
                            // 如果有 '*'，則使用模糊匹配處理
                            if (str_starts_with($value, '*')) {
                                $pattern = substr($value, 1);
                                $builder->whereRaw("{$column} REGEXP ?", ['.*' . preg_quote($pattern, '/') . '$']);
                            } elseif (str_ends_with($value, '*')) {
                                $pattern = substr($value, 0, -1);
                                $builder->whereRaw("{$column} REGEXP ?", ['^' . preg_quote($pattern, '/') . '.*']);
                            } else {
                                $pattern = str_replace('*', '.*', $value);
                                $builder->whereRaw("{$column} REGEXP ?", [$pattern]);
                            }
                        } else {
                            // 沒有 '*' 或範圍符號時，執行模糊匹配
                            $builder->where($column, 'like', '%' . $value . '%');
                        }
                    }
                    // 處理 equal_ 開頭的參數
                    elseif (str_starts_with($key, 'equal_')) {
                        $column = substr($key, 6); // 去掉 'equal_'

                        if(!in_array($column, $this->getTableColumns())){
                            continue;
                        }

                        $builder->where($column, '=', $value); // 精確匹配
                    }
                }
            //

            // Sort & Order
                //  指定排序字串
                if(!empty($params['orderByRaw'])){
                    $builder->orderByRaw($params['orderByRaw']);
                }
                // 指定排序欄位
                else if(!empty($params['sort'])){

                    //  設定排序順序。預設 DESC
                    if (isset($params['order']) && $params['order'] == 'ASC') {
                        $order = 'ASC';
                    }
                    else{
                        $order = 'DESC';
                    }

                    // 本表欄位做排序
                    if(in_array($params['sort'], $table_columns) && !in_array($params['sort'], $this->translation_keys)){
                        $builder->orderBy($this->getTable() . '.' . $params['sort'], $order);
                    }
                    // 多語表欄位
                    // 如果有 select() 欄位，會有 id ambibuous 的問題。改去 Datahelper::getResult()
                    else if(!empty($this->translation_keys) && in_array($params['sort'], $this->translation_keys)){
                        $translation_table = $this->getTranslationTable();
                        $master_key = $this->getTranslationMasterKey();
                        $sort = $params['sort'];

                        if (str_ends_with($this->translation_model_name, 'Meta')) {

                            $builder->join($translation_table, function ($join) use ($translation_table, $master_key, $sort){
                                $join->on("{$this->table}.id", '=', "{$translation_table}.{$master_key}")
                                    ->where("{$translation_table}.locale", '=', $this->locale)
                                    ->where("{$translation_table}.meta_key", '=', $sort);
                            });
                            $builder->orderBy("{$translation_table}.meta_value", $order);

                        }else{ // 以 Translation 做結尾，例如 ProductTranslation
                            $builder->join($translation_table, function ($join) use ($translation_table, $master_key, $sort){
                                $join->on("{$this->table}.id", '=', "{$translation_table}.{$master_key}")
                                    ->where("{$translation_table}.locale", '=', app()->getLocale());
                            });
                            $builder->orderBy("{$translation_table}.{$sort}", $order);
                        }
                    }
                }
                
                // 未指定排序欄位
                else if(empty($params['sort'])){
                    if(in_array('sort_order', $this->getTableColumns())){
                        $builder->orderBy($this->getTable() . '.sort_order', 'ASC');
                    }else if(in_array('id', $this->getTableColumns())){
                        $builder->orderBy($this->getTable() . '.id', 'DESC');
                    }
                }
            //

            // $debug = 1;
            if($debug == 1){
                DataHelper::showSqlContent($builder, 1);
            }

            return $builder;
        }

        /**
         * $builder: 
         *     使用 Illuminate\Database\Eloquent\Builder
         *     不使用 Illuminate\Database\Query\Builder。
         */
        public function scopeGetResult(Builder $builder, $data, $debug = 0)
        {
            if($debug){
                DataHelper::showSqlContent($builder, 1);
            }
    
            $rows = [];
    
            if(isset($data['first']) && $data['first'] = true){
                if(empty($data['pluck'])){
                    $rows = $builder->first();
                }else{
                    $rows = $builder->pluck($data['pluck'])->first();
                }
            }else{
    
                // Limit
                if(isset($data['limit'])){
                    $limit = (int) $data['limit'];
                }else{
                    $limit = (int) config('settings.config_admin_pagination_limit');
    
                    if(empty($limit)){
                        $limit = 10;
                    }
                }
    
                // Pagination default to true
                if(isset($data['pagination']) ){
                    $pagination = (boolean)$data['pagination'];
                }else{
                    $pagination = true;
                }
    
                // Get rows
                if($pagination === true && $limit > 0){  // Get some rows per page
                    $rows = $builder->paginate($limit);
                }
                else if($pagination === true && $limit == 0){  // get all but keep LengthAwarePaginator
                    $rows = $builder->paginate($builder->count());
                }
                else if($pagination === false && $limit != 0){  // Get some rows without pagination
                    $rows = $builder->limit($limit)->get();
                }
                else if($pagination === false && $limit == 0){  // Get all matched rows
                    $rows = $builder->get();
                }
    
                // Pluck
                if(!empty($data['pluck'])){
                    $rows = $rows->pluck($data['pluck']);
                }
    
                if(!empty($data['keyBy'])){
                    $rows = $rows->keyBy($data['keyBy']);
                }
            }
    
            if(!empty($rows) && !empty($data['toCleanCollection'])){
                $rows = DataHelper::toCleanCollection($rows);
            }

            return $rows;
        }

        public function scopeDebug($builder)
        {
            DataHelper::showSqlContent($builder, 1);
        }
    // end scope

    public function getMetaKeys()
    {
        $array = $this->meta_keys ?? [];

        return array_unique($array);
    }
    
    public function getMetaModelName(): string
    {
        $namespace = __NAMESPACE__;

        $className = class_basename(self::class); // Product

        return $namespace . '\\' . $className . 'Meta'; // 例如 App\Models\Catalog\ProductMeta
    }

    /**
     * $type = updateOnlyInput, updateAll
     *     updateOnlyInput: 不會動到輸入資料以外的資料。如果 $row 是一個已存在的記錄，包括 $row->is_admin，但是輸入的資料沒有，那就不會動到 is_admin。
     *     updateAll: 如果輸入資料沒有，就清空。
     */
    public function processPrepareData($row, $data, $type = 'updateOnlyInput')
    {
        $table_columns = $this->getTableColumns();

        $delete_columns = [];

        if ($type == 'updateOnlyInput') {
            $columns = array_keys($data);
        } else if ($type == 'updateAll') {
            $columns = array_keys($table_columns);
            $delete_columns = array_diff($table_columns, array_keys($data));
        }

        // 禁止修改的欄位
        unset($columns['created_at']);
        unset($columns['updated_at']);
        unset($columns['creator_id']);
        unset($columns['updater_id']);

        foreach ($columns as $column) {
            // 清空欄位值 
            if (in_array($column, $delete_columns)) {
                if(is_array($row)){
                    $row[$column] = null;
                }
                else if(is_object($row)){
                    $row->{$column} = null;
                }
            } 
            // 賦值
            else if(in_array($column, $table_columns)){
                if (isset($data[$column])) {
                    if(is_array($row)){
                        $row[$column] = $data[$column];
                    }
                    else if(is_object($row)){
                        $row->{$column} = $data[$column];
                    }
                }
            }
        }

        return $row;
    }
}
