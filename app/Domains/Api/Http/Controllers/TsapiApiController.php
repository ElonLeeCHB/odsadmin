<?php

namespace App\Domains\Api\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Api\Http\Controllers\ApiController;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Repositories\Eloquent\Sale\OrderRepository;
use App\Repositories\Eloquent\Localization\RoadRepository;
use App\Helpers\Classes\DataHelper;
use App\Models\Sale\OrderPayment;

class TsapiApiController extends ApiController
{
    public function updatePayment($order_id)
    {

        $result = ['123'];

        // $rows = $this->
        return response()->json($result, 200);
    }
}
