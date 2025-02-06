<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use Illuminate\Support\Facades\Validator;
use App\Models\Setting\Setting;
use App\Models\Sale\Datelimit;
use App\Models\Sale\TimeSlotLimit;

class QuantityControlController extends ApiPosController
{
    public function updateTimeslot()
    {
        try {
            $content = request()->post();
            
            
            $row = Setting::where('group','pos')->where('setting_key', 'pos_timeslotlimits')->first();

            if ($row) {
                $row->setting_value = json_encode($content);
                $row->save();
            }
            
            return response()->json(['status' => 'ok']);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 400);
        }
    }

    public function getTimeslot()
    {
        try {            
            $row = Setting::where('group','pos')->where('setting_key', 'pos_timeslotlimits')->first();
            return $this->sendResponse(['data' => $row->setting_value]);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 400);
        }
    }

    public function addSpecial()
    {

        try {
            $content = request()->post();

            Datelimit::where('Date', $content['Date'])->delete();

            foreach ($content['TimeSlots'] as $key => $limit) {
                $insert_data[] = [
                    'Date' => $content['Date'],
                    'TimeSlot' => $key,
                    'LimitCount' => $limit,
                ];
            }

            if(!empty($insert_data)){
                Datelimit::insert($insert_data);
            }
            
            return response()->json(['status' => 'ok']);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 400);
        }

    }
}