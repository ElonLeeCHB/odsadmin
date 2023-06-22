<?php

namespace App\Domains\Admin\Http\Controllers\Localization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Libraries\TranslationLibrary;
use App\Domains\Admin\Services\Localization\RoadService;
use App\Domains\Admin\Services\Localization\DivisionService;
use DB;

class RoadController extends Controller
{

    public function __construct(Request $request, RoadService $RoadService, DivisionService $DivisionService)
    {
        $this->RoadService = $RoadService;
        $this->DivisionService = $DivisionService;
        $this->request = $request;

        // Translations
        $groups = [
            'admin/common/common',
            'admin/common/column_left',
            'admin/localization/country',
        ];
        $this->lang = new TranslationLibrary($groups);
    }


    //public function getJsonRoads()
    public function autocomplete()
    {
        $json = [];
        
        if(empty($this->request->filter_state_id) && empty($this->request->filter_city_id)){
            return false;
        }
        
        // Find cities
        if(empty($this->request->filter_city_id) && !empty($this->request->filter_state_id)){
            $filter_data['filter_id'] = $this->request->filter_state_id;
            $state = $this->DivisionService->getRow($filter_data);
            if(!empty($state)){
                $filter_data = [
                    'filter_parent_id' => $state->id,
                    'select' => 'id,name',
                    'limit' => 0,
                    'pagination' => false,
                    'regexp' => false,
                ];
                $cities = $this->DivisionService->getRows($filter_data);
                $city_ids = $cities->pluck('id');
            }
        }
		
		// Find Roads
        $filter_data = [
            'regexp' => true,
            'limit' => 0,
            'pagination' => false,
            'relations' => ['city'],
        ];

        if (!empty($this->request->filter_city_id)) {
            $filter_data['filter_city_id'] = '='.$this->request->filter_city_id;
        }

        if(isset($this->request->filter_name))
        {
            $filter_data['filter_name'] = $this->request->filter_name;
        }

		if(!empty($city_ids)){
			$filter_data['whereIn'] = ['city_id' => $city_ids,];
		} 
        
		$roads = $this->RoadService->getRows($filter_data);

        foreach ($roads as $key => $row) {
            $combo_name = '';
            if(!empty($state->name)){
                $combo_name = $state->name . '_';
            }
            $combo_name .= $row->city->name . '_' . $row->name;
            $json[] = [
                'value' => $row->id,
                'label' => $combo_name,
                'road_id' => $row->id,
                'name' => $row->name,
                'city_id' => $row->city_id,
            ];
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }
}