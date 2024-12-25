<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $lang;
    protected $acting_user;
    protected $acting_username;
    protected $url_data;
    protected $post_data;

    public function __construct()
    {
        if (basename($_SERVER['SCRIPT_NAME']) == 'artisan') {
            return null;
        }

        $this->url_data = request()->query();
        $this->post_data = request()->post();
    }


    // public function initController()
    // {
    //     if(empty($this->acting_user)){
    //         $this->acting_user = app('acting_user');
    //     }

    //     $this->acting_username = $this->acting_user->username ?? '';
    // }
}
