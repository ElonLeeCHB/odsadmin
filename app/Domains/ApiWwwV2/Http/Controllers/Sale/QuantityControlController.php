<?php

namespace App\Domains\ApiWwwV2\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Domains\ApiWwwV2\Http\Controllers\ApiWwwV2Controller;
use App\Models\Setting\Setting;

class QuantityControlController extends ApiWwwV2Controller
{
    public function getTimeslot()
    {
        try {            
            $row = Setting::where('group','pos')->where('setting_key', 'pos_timeslotlimits')->first();
            return $this->sendResponse(['data' => $row->setting_value]);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 400);
        }
    }
}