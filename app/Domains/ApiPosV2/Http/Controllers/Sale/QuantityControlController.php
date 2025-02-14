<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use Illuminate\Support\Facades\Validator;
use App\Models\Setting\Setting;
use App\Models\Sale\Datelimit;
use App\Models\Sale\TimeSlotLimit;
use App\Services\Sales\QuantityControlService;

class QuantityControlController extends ApiPosController
{
    public function __construct(private Request $request,private QuantityControlService $QuantityControlService)
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }
    }
    
    public function updateTimeslot()
    {
        try {
            $content = request()->post();

            $this->QuantityControlService->updateTimeslot($content);
            
            return response()->json(['status' => 'ok']);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 400);
        }
    }

    public function getTimeslot()
    {
        try {
            $content = $this->QuantityControlService->getTimeslot();        

            return $this->sendResponse(['data' => $content]);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 400);
        }
    }

    public function updateDatelimits()
    {
        try {
            $this->QuantityControlService->updateDatelimits(request()->post());
            
            return response()->json(['status' => 'ok']);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 400);
        }
    }

    public function getDatelimits($date)
    {
        try {
            $data = $this->QuantityControlService->getDatelimits($date);
            
            return $this->sendResponse(['data' => $data]);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 400);
        }
    }


    
}