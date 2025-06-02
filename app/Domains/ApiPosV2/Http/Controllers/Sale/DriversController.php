<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use Illuminate\Support\Facades\Validator;
use App\Models\Setting\Setting;
use App\Models\Sale\Datelimit;
use App\Models\Sale\TimeSlotLimit;
use App\Domains\ApiPosV2\Services\Sale\DriverService;

class DriversController extends ApiPosController
{
    public function __construct(private Request $request,private DriverService $DriverService)
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }
    }
}