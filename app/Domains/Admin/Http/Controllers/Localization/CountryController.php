<?php

namespace App\Domains\Admin\Http\Controllers\Localization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Libraries\TranslationLibrary;
use App\Models\Localization\Division;
use App\Models\Localization\Country;
use DB;

class CountryController extends Controller
{

    public function __construct(Request $request)
    {
        $this->request = $request;

        // Translations
        $groups = [
            'admin/common/common',
            'admin/common/column_left',
            'admin/localization/country',
        ];
        $this->lang = (new TranslationLibrary())->getTranslations($groups);
    }

    public function index()
    {
        $countries = Country::all();
        $country = Country::where('code','TW')->first();
        echo "<pre>", print_r($country->cities->toArray(), 1), "</pre>"; exit;
    }

}