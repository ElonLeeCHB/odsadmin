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
    
    public function updateTimeslots()
    {
        try {
            $content = request()->post();

            $this->QuantityControlService->updateTimeslots($content);
            
            return response()->json(['status' => 'ok']);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 400);
        }
    }

    public function getTimeslots()
    {
        try {
            $content = $this->QuantityControlService->getTimeslots();        

            return $this->sendResponse(['data' => $content]);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 400);
        }
    }

    public function updateMaxQuantityByDate()
    {
        try {
            $this->QuantityControlService->updateMaxQuantityByDate(request()->post());

            return response()->json(['status' => 'ok']);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 400);
        }
    }

    public function getOrderlimitsByDate($date)
    {
        try {
            $result = $this->QuantityControlService->getOrderlimitsByDate($date);

            return $this->sendResponse(['data' => $result]);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 400);
        }
    }

    public function refreshOrderedQuantityByDate($date)
    {
        try {
            $result = $this->QuantityControlService->refreshOrderedQuantityByDate($date);

            return $this->sendResponse(['data' => $result]);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 400);
        }
    }

    public function resetMaxQuantityByDate($date)
    {
        try {
            $result = $this->QuantityControlService->resetMaxQuantityByDate($date);

            if(!empty($result['error'])){
                return response()->json(['error' => $result['error']], 400);
            }
            
            return $this->sendResponse(['data' => $result]);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 400);
        }
    }


    
}