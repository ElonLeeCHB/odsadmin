<?php

namespace App\Domains\Api\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Api\Http\Controllers\ApiController;
use App\Domains\Api\Services\Inventory\UnitService;

class UnitController extends ApiController
{
    protected $lang;

    public function __construct(
        private Request $request
        , private UnitService $UnitService
    )
    {
        parent::__construct();
    }

    public function listAll()
    {
        $rows = $this->UnitService->getAllUnits();

        return response(json_encode($rows))->header('Content-Type','application/json');
    }
}
