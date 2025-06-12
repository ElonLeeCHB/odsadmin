<?php

namespace App\Domains\ApiPosV2\Services\Sale;

use App\Services\Service;
use Illuminate\Support\Facades\DB;
use App\Models\Sale\Driver;
use App\Helpers\Classes\OrmHelper;

class DriverService extends Service
{
    public $modelName;
    public $model;
    public $table;
    public $lang;

	public function __construct()
	{
        $this->modelName = "\App\Models\Sale\Driver";
        $this->model = new $this->modelName;
    }

    public function getList($filter_data)
    {
        $query = Driver::query();
        OrmHelper::prepare($query, $filter_data);
        $drivers = $query->get();

        return $drivers;
    }

    public function getInfo($filter_data)
    {
        $query = Driver::query();
        OrmHelper::prepare($query, $filter_data);

        $drivers = $query->get();

        return $drivers;
    }


    public function save($data, $driver_id = null)
    {
        try {
            DB::beginTransaction();

            $driver = Driver::findOrNew($driver_id);

            unset($data['id']);
            unset($data['driver_id']);

            OrmHelper::saveRow($driver, $data);

            DB::commit();

            return $driver;

        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    public function destroy($driver_id)
    {
        try {
            DB::beginTransaction();

            $driver = Driver::destroy($driver_id);

            DB::commit();

            return $driver;

        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }



}

