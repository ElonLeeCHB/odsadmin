<?php

namespace App\Domains\Api\Http\Controllers\Localization;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Api\Http\Controllers\ApiController;
use App\Domains\Api\Services\Localization\CountryService;
use App\Domains\Api\Services\Localization\DivisionService;

class DivisionController extends ApiController
{

    public function __construct(protected Request $request
        , private CountryService $CountryService
        , private DivisionService $DivisionService
    )
    {
        parent::__construct();

        $this->getLang(['admin/common/common', 'admin/localization/division']);
    }

    public function stateList()
    {
        $divisions = cache()->rememberForever('tw_division_level_1', function(){
            $filter_data = [
                'equal_level' => '1',
                'equal_country_code' => $this->request->equal_country_code ?? 'tw',
                'pagination' => false,
                'limit' => 100,
                'sort' => 'sort_order',
                'order' => 'ASC',
            ];
    
            $records = $this->DivisionService->getRows($filter_data);
    
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
        if( empty($this->request->equal_parent_id) ){
            return [];
        }
        $cacheName = 'tw_division_level_2';

        $divisions = cache()->rememberForever($cacheName, function(){
            $filter_data = [
                'equal_country_code' => $this->request->equal_country_code ?? 'tw',
                'equal_level' => 2,
                'pagination' => false,
                'limit' => 0,
                'sort' => 'sort_order',
                'order' => 'ASC',
            ];
               
            $rows = $this->DivisionService->getRows($filter_data);
    
            foreach ($rows as $row) {
                $result[$row->parent_id][] = [
                    'city_id' => $row->id,
                    'name' => $row->name,
                ];
            }

            return $result;
        });

        $cities = [];

        if(!empty($this->request->equal_parent_id)){
            $parent_id = $this->request->equal_parent_id;
        }
        
        if(!empty($parent_id) && is_numeric($parent_id)){
            $cities = $divisions[$parent_id];
        }
        
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