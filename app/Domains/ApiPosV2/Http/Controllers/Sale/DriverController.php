<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use Illuminate\Support\Facades\Validator;
use App\Models\Setting\Setting;
use App\Models\Sale\Datelimit;
use App\Models\Sale\TimeSlotLimit;
use App\Domains\ApiPosV2\Services\Sale\DriverService;

class DriverController extends ApiPosController
{
    public function __construct(private Request $request,private DriverService $DriverService)
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }
    }

    public function index()
    {
        $result = $this->DriverService->getList($this->all_data);

        return $this->sendJsonResponse($result);
    }

    public function show($driver_id = null)
    {
        $filter_data = $this->all_data;

        if (!empty($driver_id)){
            $filter_data['equal_id'] = $driver_id;
        }
        $result = $this->DriverService->getInfo($filter_data);

        return $this->sendJsonResponse($result);
    }

    public function save($driver_id = null)
    {
        $result = $this->DriverService->save($this->post_data, $driver_id);

        $json = [
            'id' => $result->id,
        ];

        return $this->sendJsonResponse($json);
    }

    public function destroy($driver_id)
    {
        try {
            $this->DriverService->destroy($driver_id);

            return response()->json(['success' => true]);
        } catch (\Throwable $th) {
            $json = [
                'error' => $th->getMessage(),
            ];
            return response()->json($json);
        }
    }
}