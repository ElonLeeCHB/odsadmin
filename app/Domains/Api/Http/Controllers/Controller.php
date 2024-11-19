<?php

namespace App\Domains\Apiv2\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Libraries\TranslationLibrary;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

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

        // is_active
        // if(isset($data['equal_is_active']) && $data['equal_is_active'] == '*'){
        //     unset($data['equal_is_active']);
        // }else{
        //     $query_data['equal_is_active'] = $data['equal_is_active'];
        // }
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
        // $rows 其實是單筆
        if ($rows instanceof \Illuminate\Database\Eloquent\Model) {
            foreach ($relations as $relation) {
                $rows->setRelation($relation, null);
            }

        }

        // $rows 是多筆
        else if(count($rows) > 0){
            foreach ($rows as $row) {
                foreach ($relations as $relation) {
                    $row->setRelation($relation, null);
                }
            }
        }

        return $rows;
    }








}
