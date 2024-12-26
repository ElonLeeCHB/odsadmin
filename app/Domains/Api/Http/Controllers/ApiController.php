<?php

namespace App\Domains\Api\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Libraries\TranslationLibrary;

class ApiController extends Controller
{
    public function __construct()
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }
    }

    public function resetUrlData($data)
    {
        $query_data = [];
        
        if(!empty($data['sort'])){
            $query_data['sort'] = $data['sort'];
        }else{
            $query_data['sort'] = 'id';
        }

        if(!empty($data['source'])){
            $query_data['source'] = $data['source'];
        }else{
            $query_data['source'] = '';
        }

        if(!empty($data['order'])){
            $query_data['order'] = $data['order'];
        }else{
            $query_data['order'] = 'DESC';
        }

        if(isset($data['page'])){
            $query_data['page'] = $data['page'];
        }

        if(isset($data['limit'])){
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

        if(isset($data['equal_is_active'])){
            if($data['equal_is_active'] == '*'){
                unset($data['equal_is_active']);
            }else{
                $query_data['equal_is_active'] = $data['equal_is_active'];
            }
        }

        if(isset($data['with'])){
            $query_data['with'] = $data['with'];
        }

        if(!empty($data['extra_columns'])){
            $query_data['extra_columns'] = $data['extra_columns'];
        }

        if(!empty($data['simplelist'])){
            $query_data['simplelist'] = $data['simplelist'];
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
}
