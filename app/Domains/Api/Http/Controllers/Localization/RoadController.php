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

        //find state
        if(!empty($this->request->filter_state_id)){
            $filter_data = [
                'equal_id' => $this->request->filter_state_id,
                'limit' => 0,
                'pagination' => false,
                'with' => ['subDivisions'],
            ];
    
            $state = $this->DivisionService->getRecord($filter_data);
        }

        //find cities within state
        if(!empty($state->subDivisions)){
            $cities = $state->subDivisions;
            $city_ids = $cities->pluck('id');
        }
		
        //find roads
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
			$filter_data['whereIn'] = [
				'city_id' => $city_ids,
			];
		} 
        
		$roads = $this->RoadService->getRecords($filter_data);

        foreach ($roads as $key => $row) {

            $json[] = [
                'value' => $row->id,
                'label' => $row->city->name . '_' . $row->name,
                'road_id' => $row->id,
                'name' => $row->name,
                'city_id' => $row->city_id,
            ];
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }
}