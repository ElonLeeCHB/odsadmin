<?php

namespace App\Domains\ApiWwwV2\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Domains\ApiWwwV2\Http\Controllers\ApiWwwV2Controller;
use App\Models\Setting\Setting;
use App\Services\Sales\QuantityControlService;

class QuantityControlController extends ApiWwwV2Controller
{
    public function __construct(private Request $request,private QuantityControlService $QuantityControlService)
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
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
}