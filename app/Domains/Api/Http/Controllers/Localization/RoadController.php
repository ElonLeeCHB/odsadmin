<?php

namespace App\Domains\Api\Http\Controllers\Localization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Libraries\TranslationLibrary;
use App\Domains\Api\Services\Localization\RoadService;
use App\Domains\Api\Services\Localization\DivisionService;
use DB;

class RoadController extends Controller
{
    private $request;
    private $DivisionService;
    private $RoadService;

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


    public function list()
    {
        $json = [];
        
        if(empty($this->request->filter_state_id) && empty($this->request->filter_city_id)){
            return false;
        }
        // Has state, no city
        if(!empty($this->request->filter_state_id) && empty($this->request->filter_city_id)){
            $filter_data = [
                'filter_id' => $this->request->filter_state_id,
                'with' => 'subDivisions',
                'regexp' => false,
            ];
            $state = $this->DivisionService->getRecord($filter_data);
            $cities = $state->subDivisions;
            $city_ids = $cities->pluck('id')->toArray();
        }
		
		// Find Roads
        $filter_data = [
            'regexp' => true,
            'limit' => 20,
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
			$filter_data['whereIn'] = [
				'city_id' => $city_ids,
			];
		} 
        
		$roads = $this->RoadService->getRecords($filter_data);

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