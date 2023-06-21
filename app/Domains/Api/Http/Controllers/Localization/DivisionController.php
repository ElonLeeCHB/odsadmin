<?php

namespace App\Domains\Api\Http\Controllers\Localization;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\TranslationLibrary;
use App\Domains\Api\Services\Localization\CountryService;
use App\Domains\Api\Services\Localization\DivisionService;
use App\Domains\Api\Services\Localization\RoadService;
use DB;

class DivisionController extends Controller
{

    public function __construct(private Request $request
        , private CountryService $CountryService
        , private DivisionService $DivisionService
        , private RoadService $RoadService
    )
    {
        $this->lang = (new TranslationLibrary())->getTranslations(['admin/common/common','admin/localization/division']);
    }

    public function stateList()
    {
        $divisions = cache()->rememberForever('tw_division_level_1', function(){
            $filter_data = [
                'filter_level' => '1',
                'filter_country_code' => $this->request->filter_country_code ?? 'tw',
                'pagination' => false,
                'regexp' => false, // false = 完全比對
                'limit' => 100,
                'sort' => 'sort_order',
                'order' => 'ASC',
            ];
    
            $records = $this->DivisionService->getRecords($filter_data);
    
            foreach ($records as $record) {
                $result[] = [
                    'id' => $record->id,
                    'name' => $record->name,
                    'english_name' => $record->english_name,
                    'code' => $record->code,
                ];
            }

            return $result;
        });
        
        if(!empty($this->request->filter_name)){
            foreach ($divisions as $key => $division) {
                if (strpos($division['name'], $this->request->filter_name) === false) {
                    unset($divisions[$key]);
                }
            }
        }

        return response(json_encode($divisions))->header('Content-Type','application/json');
    }


    public function cityList()
    {
        if(empty($this->request->filter_parent_id)){
            return [];
        }
        $cacheName = 'tw_division_level_2';

        $divisions = cache()->rememberForever($cacheName, function(){
            $filter_data = [
                'filter_country_code' => $this->request->filter_country_code ?? 'tw',
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
    
            $rows = $this->DivisionService->getRecords($filter_data);
    
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

        $cities = $divisions[$this->request->filter_parent_id];
        
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