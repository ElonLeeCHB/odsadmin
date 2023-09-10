<?php

namespace App\Domains\Admin\Http\Controllers\Localization;

use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use Illuminate\Http\Request;
use App\Domains\Admin\Services\Localization\UnitService;

class UnitController extends BackendController
{
    public function __construct(private Request $request, private UnitService $UnitService)
    {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/localization/unit']);
    }


    public function index()
    {
    }


    public function list()
    {
    }

    public function getList()
    {
    }

    
    public function form($location_id = null)
    {
    }


    public function save()
    {
    }


    public function delete()
    {
    }


    public function autocomplete()
    {
        $json = [];

        $filter_data = array(
            'filter_keyword'   => $this->request->filter_keyword,
        );

        if (!empty($this->request->sort)) {
            if($this->request->sort =='name'){
                $filter_data['sort'] = '.name';
            } else if($this->request->sort =='short_name'){
                $filter_data['sort'] = '.short_name';
            }
        }

        $rows = $this->UnitService->getActiveUnits($filter_data);

        if(empty($rows)){
            return false;
        }

        foreach ($rows as $row) {
            $json[] = array(
                'label' => $row->name,
                'value' => $row->id,
                'location_id' => $row->id,
                'location_name' => $row->name,
                'short_name' => $row->short_name,
            );
        }

        array_unshift($json,[
            'value' => 0,
            'label' => ' -- ',
            'location_id' => '',
            'location_name' => '',
            'short_name' => '',
        ]);

        return response(json_encode($json))->header('Content-Type','application/json');
    }

}
