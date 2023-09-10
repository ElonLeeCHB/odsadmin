<?php

namespace App\Domains\Admin\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\TranslationLibrary;

class BackendController extends Controller
{    
    protected $lang;
    protected $acting_user;
    protected $acting_username;

    public function __construct()
    {
        if (basename($_SERVER['SCRIPT_NAME']) == 'artisan') {
            return null;
        }
    }


    public function initController()
    {
        if(empty($this->acting_user)){
            $this->acting_user = app('acting_user');
        }

        $this->acting_username = $this->acting_user->username ?? '';
    }


    public function getQueries($data)
    {
        $query_data = [];

        if(!empty($data['sort'])){
            $query_data['sort'] = $data['sort'];
        }else{
            $query_data['sort'] = 'id';
        }

        if(!empty($data['order'])){
            $query_data['order'] = $data['order'];
        }else{
            $query_data['order'] = 'DESC';
        }

        if(!empty($data['page'])){
            $query_data['page'] = $data['page'];
        }

        if(!empty($data['limit'])){
            $query_data['limit'] = $data['limit'];
        }

        // filter_
        foreach($data as $key => $value){
            if(strpos($key, 'filter_') !== false){
                $query_data[$key] = $value;
            }
        }

        // equals_
        foreach($data as $key => $value){
            if(strpos($key, 'equal_') !== false){
                $query_data[$key] = $value;
            }
        }

        // is_active
        if(!isset($query_data['equal_is_active'])){
            $query_data['equal_is_active'] = 1;
        }

        return $query_data;
    }


    public function getLang($data)
    {
        if(!isset($this->lang)){
            if(!is_array($data)){
                $arr[] = $data;
                $data = $arr;
            }
            
            $this->lang = (new TranslationLibrary())->getTranslations($data);
        }

        return $this->lang;
    }


    public function unsetUrlQueryData($query_data)
    {
        if(!empty($query_data['sort'])){
            unset($query_data['sort']);
        }
        
        if(!empty($query_data['order'])){
            unset($query_data['order']);
        }
        
        if(!empty($query_data['with'])){
            unset($query_data['with']);
        }
        
        if(!empty($query_data['whereIn'])){
            unset($query_data['whereIn']);
        }
        
        if(!empty($query_data['whereRawSqls'])){
            unset($query_data['whereRawSqls']);
        }
        
        if(!empty($query_data['andOrWhere'])){
            unset($query_data['andOrWhere']);
        }
        
        return $query_data;
    }


    public function unsetRelations($rows, $relations)
    {
        if ($rows instanceof \Illuminate\Database\Eloquent\Model) {
            foreach ($relations as $relation) {
                $rows->setRelation($relation, null);
            }
            
        }
        else if(count($rows) > 1){
            foreach ($rows as $row) {
                foreach ($relations as $relation) {
                    $row->setRelation($relation, null);
                }
            }
        }

        return $rows;
    }
}
