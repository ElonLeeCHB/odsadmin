<?php

namespace App\Domains\Admin\Http\Controllers\Localization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Libraries\TranslationLibrary;
use App\Domains\Admin\Services\Localization\CountryService;
use App\Domains\Admin\Services\Localization\DivisionService;
use App\Domains\Admin\Services\Localization\AddressService;
use App\Services\Localization\RoadService;
use DB;

class DivisionController extends Controller
{

    public function __construct(private Request $request
        , private CountryService $CountryService
        , private DivisionService $DivisionService
        , private AddressService $AddressService
        , private RoadService $RoadService
    )
    {
        // Translations
        $groups = [
            'admin/common/common',
            'admin/common/column_left',
            'admin/localization/division',
        ];
        $this->translib = new TranslationLibrary();
        $this->lang = $this->translib->getTranslations($groups);
    }

    public function index()
    {
    }


    public function getJsonStates()
    {
        $cacheName = 'tw_division_level_1';

        $json = cache()->rememberForever($cacheName, function(){
            if(isset($this->request->equal_country_code)){
                $filter_data['equal_country_code'] = $this->request->equal_country_code;
            }else{
                return false;
            }

            $filter_data['filter_level'] = 1;
            $filter_data['pagination'] = false;
            $filter_data['limit'] = 0;
            $filter_data['order'] = 'ASC';

            $rows = $this->DivisionService->getRows($filter_data);

            return $rows;
        });

        return response(json_encode($json))->header('Content-Type','application/json');
    }


    public function getJsonCities()
    {
        if(empty($this->request->equal_parent_id)){
            return [];
        }
        $cacheName = 'tw_division_level_2';

        //cache()->forget($cacheName);
        $divisions = cache()->rememberForever($cacheName, function(){
            $filter_data = [
                'equal_country_code' => $this->request->equal_country_code ?? 'tw',
                'filter_level' => 2,
                'pagination' => false,
                'regexp' => false, // false = 完全比對
                'limit' => 500,
                'sort' => 'sort_order',
                'order' => 'ASC',
            ];

            $filter_data['regexp'] = false;
            $filter_data['pagination'] = false;
            $filter_data['limit'] = 0;
            $filter_data['order'] = 'ASC';

            $rows = $this->DivisionService->getRows($filter_data);

            foreach ($rows as $row) {
                $result[$row->parent_id][] = [
                    'city_id' => $row->id,
                    'name' => $row->name,
                    // 'parent_id' => $row->parent_id,
                    // 'parent_name' => $row->parentDivision->name,
                ];
            }

            return $result;
        });

        $cities = $divisions[$this->request->equal_parent_id];

        if(!empty($this->request->filter_name)){
            foreach ($cities as $key => $city) {
                if (strpos($city['name'], $this->request->filter_name) === false) {
                    unset($cities[$key]);
                }
            }
        }

        return response(json_encode($cities))->header('Content-Type','application/json');
    }
}
