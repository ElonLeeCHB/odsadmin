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

        $data['sales_chart_url'] = asset('assets2/ocadmin/test/dashboard-chart-sales.html');

        return view('admin.dashboard', $data);
    }

    public function setLanguage($new_locale)
    {
        // $locale = app()->getLocale();
        // $url = str_replace($locale, $new_locale, url()->previous());

        $supported = ['zh_Hant', 'en']; // 支援的語系清單
        $url = url()->previous();

        // 只替換開頭的語系
        $parsedUrl = parse_url($url, PHP_URL_PATH); // /zh_Hant/products/123
        $pattern = '#^/(' . implode('|', array_map('preg_quote', $supported)) . ')(?=/|$)#';
        $newPath = preg_replace($pattern, '/' . $new_locale, $parsedUrl);

        return redirect($newPath);
    }
}
