<?php

namespace App\Domains\Admin\Http\Controllers\Localization;

use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use Illuminate\Http\Request;
use App\Domains\Admin\Services\Localization\RoadService;
use App\Domains\Admin\Services\Localization\DivisionService;
use DB;

class RoadController extends BackendController
{

    public function __construct(protected Request $request, private RoadService $RoadService, private DivisionService $DivisionService)
    {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/localization/country']);
    }


    public function autocomplete()
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

        if (!empty($this->request->filter_city_id)) {
            $filter_data['equal_city_id'] = $this->request->filter_city_id;
        }

        if(!empty($city_ids)){
            $filter_data['whereIn'] = ['city_id' => $city_ids,];
        } 

        if(isset($this->request->filter_name)){
            $filter_data['filter_name'] = $this->request->filter_name;
        }

        $roads = $this->RoadService->getRows($filter_data);

        foreach ($roads as $key => $row) {
            if(!empty($this->request->filter_city_id)){
                $road_name = $row->name;
            }else{
                $road_name = $row->city->name . '_' . $row->name;
            }

            //if(!empty($row->city->short_name) && $row->city->parent_id != 2 ){
            if(!empty($row->city->short_name) ){
                $short_name = $row->city->path_name;
            }else{
                $short_name = $row->short_name;
            }

            $json[] = [
                'value' => $row->id,
                'label' => $road_name,
                'road_id' => $row->id,
                'name' => $row->name,
                'city_id' => $row->city_id,
                'short_name' => $short_name,
            ];
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }
}