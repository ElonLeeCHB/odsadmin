<?php

namespace App\Domains\Api\Http\Controllers\Localization;

use App\Http\Controllers\Controller;
use App\Domains\Api\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use App\Services\Localization\RoadService;
use App\Domains\Api\Services\Localization\DivisionService;

class RoadController extends ApiController
{
    public function __construct(protected Request $request, protected RoadService $RoadService, protected DivisionService $DivisionService)
    {
        parent::__construct();
    }


    public function list()
    {
        $json = [];

        $equal_state_id = $this->request->equal_state_id ?? $this->request->filter_state_id ?? 0;
        $equal_city_id = $this->request->equal_city_id ?? $this->request->filter_city_id ?? 0;
        
        if(empty($equal_state_id) && empty($equal_city_id)){
            return false;
        }

        //find state
        if(!empty($equal_state_id)){
            $filter_data = [
                'equal_id' => $equal_state_id,
                'limit' => 0,
                'pagination' => false,
                'with' => ['subDivisions'],
            ];
    
            $state = $this->DivisionService->getRow($filter_data);
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

        if (!empty($equal_city_id)) {
            $filter_data['equal_city_id'] = $equal_city_id;
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
        
		$roads = $this->RoadService->getRows($filter_data);

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


    public function fword()
    {
        $json = [];

        $equal_state_id = $this->request->equal_state_id ?? $this->request->filter_state_id ?? 0;
        $equal_city_id = $this->request->equal_city_id ?? $this->request->filter_city_id ?? 0;
        
        if(empty($equal_state_id) && empty($equal_city_id)){
            return false;
        }

        //find state
        if(!empty($equal_state_id)){
            $filter_data = [
                'equal_id' => $equal_state_id,
                'limit' => 0,
                'pagination' => false,
                'with' => ['subDivisions'],
            ];
    
            $state = $this->DivisionService->getRow($filter_data);
        }

        //find cities within state
        if(!empty($state->subDivisions)){
            $cities = $state->subDivisions;
            $city_ids = $cities->pluck('id')->toArray();
        }

        //find first word of each roads
        $filter_data = [
            'limit' => 0,
            'pagination' => false,
            'sort' => 'strokes',
            'order' => 'ASC',
            'distinct' => true,
            'orderByRaw' => 'CONVERT(`word` using big5)',
        ];

        // if(!empty($equal_city_id)){
        //     $filter_data['select'] = 'word, city_id';
        // }else{
        //     $filter_data['select'] = 'word';
        //     $filter_data['pluck'] = 'word';
        // }
        $filter_data['select'] = 'word';
        $filter_data['pluck'] = 'word';

        if (!empty($equal_city_id)) {
            $filter_data['equal_city_id'] = $equal_city_id;
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
        
		$roads = $this->RoadService->getFirstWords($filter_data);

        return response(json_encode($roads->toArray()))->header('Content-Type','application/json');
    }
}