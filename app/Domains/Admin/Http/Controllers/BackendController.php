<?php

namespace App\Domains\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Libraries\TranslationLibrary;
use App\Helpers\Classes\DataHelper;
use App\Helpers\Classes\IpHelper;

class BackendController extends Controller
{
    public function __construct()
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }
    }


    /**
     * 這個之後應該棄用。
     */
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


    public function getErrorResponse($sys_error, $general_error, $status_code = 500)
    {
        $json = [];

        if(config('app.debug')){
            //可再增加判斷角色，例如系統管理者
            
            $json['error'] = $sys_error;
        }else{
            $json['error'] = $general_error;
        }

        return response()->json($json, $status_code);
    }
}
