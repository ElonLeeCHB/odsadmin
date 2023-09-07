<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\TranslationLibrary;

class GlobalController extends Controller
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


    /**
     *  2023-09-07 目前還沒用過。
     */
    // public function initController()
    // {
    //     // Acting user
    //     if(empty($this->acting_user)){
    //         $this->acting_user = app('acting_user');
    //     }

    //     $this->acting_username = $this->acting_user->username ?? '';

    // }


    
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


    public function getQueries($data)
    {
        $new_data = [];

        if(!empty($data['sort'])){
            $new_data['sort'] = $data['sort'];
        }else{
            $new_data['sort'] = 'id';
        }

        if(!empty($data['order'])){
            $new_data['order'] = $data['order'];
        }else{
            $new_data['order'] = 'DESC';
        }

        if(!empty($data['page'])){
            $new_data['page'] = $data['page'];
        }

        if(!empty($data['limit'])){
            $new_data['limit'] = $data['limit'];
        }

        // filter_
        foreach($data as $key => $value){
            if(strpos($key, 'filter_') !== false){
                $new_data[$key] = $value;
            }
        }

        // equals_
        foreach($data as $key => $value){
            if(strpos($key, 'equal_') !== false){
                $new_data[$key] = $value;
            }
        }

        // Extra
        if(!isset($new_data['equal_is_active'])){
            $new_data['equal_is_active'] = 1;
        }

        return $new_data;
    }


    public function unsetUrlQueryData($data, $allowed = [])
    {
        if(empty($allowed)){
            foreach ($data as $key => $value) {
                if(!str_starts_with($key, 'filter_') && !str_starts_with($key, 'equal_') && $key == 'page'){
                    unset($data[$key]);
                }
            }

            return $data;
        }

        foreach ($data as $key => $value) {
            if(!in_array($key, $allowed)){
                unset($data[$key]);
            }
        }
        
        return $data;
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