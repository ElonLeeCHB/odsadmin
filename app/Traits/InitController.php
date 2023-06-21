<?php

namespace App\Traits;

/**
 * 在 Repository.php 判斷 model 若有 translatedAttributes，則 令 model實例使用 with('translation')。
 * 然後該實例的 model 檔 必須 use 本類別，就會存在 $modelInstance->translation 關聯可供呼叫。
 */

trait InitController
{
    public function getQueries($data)
    {
        $queries = [];

        if(!empty($data['sort'])){
            $queries['sort'] = $data['sort'];
        }else{
            $queries['sort'] = 'id';
        }

        if(!empty($data['order'])){
            $queries['order'] = $data['order'];
        }else{
            $queries['order'] = 'DESC';
        }

        if(!empty($data['page'])){
            $queries['page'] = $data['page'];
        }

        if(!empty($data['limit'])){
            $queries['limit'] = $data['limit'];
        }

        // filter_
        foreach($data as $key => $value){
            if(strpos($key, 'filter_') !== false){
                $queries[$key] = $value;
            }
        }

        // equals_
        foreach($data as $key => $value){
            if(strpos($key, 'equals_') !== false){
                $queries[$key] = $value;
            }
        }

        return $queries;
    }
}