<?php

namespace App\Domains\Admin\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Libraries\TranslationLibrary;
use Lang;

class DashboardController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {

        $groups = [
            'admin/common/common',
        ];
        $data['lang'] = (new TranslationLibrary())->getTranslations($groups);

        //Language
        // $lang = (object)[];
        // $obj = Lang::get('admin/common/common');

        // if(!empty($obj) && is_array($obj)){
        //     foreach ($obj as $key => $value) {
        //         $lang->$key = $value;
        //     }
        //     $data['lang'] = $lang;
        // }

        $data['sales_chart_url'] = asset('assets-admin/test/dashboard-chart-sales.html');

        return view('admin.dashboard', $data);
    }

    public function setLanguage($lang_code)
    {
        $locale = \App::getLocale();
        $url = str_replace($locale, $lang_code, \URL::previous());
        //echo $url;
        return redirect($url);
    }
}
