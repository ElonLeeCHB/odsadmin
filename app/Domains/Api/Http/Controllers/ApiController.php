<?php

namespace App\Domains\Api\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Libraries\TranslationLibrary;
use App\Helpers\Classes\DataHelper;

class ApiController extends Controller
{
    public function __construct()
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }
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
