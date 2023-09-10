<?php

namespace App\Domains\Admin\Services\Localization;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Repositories\Eloquent\Localization\UnitRepository;

class UnitService extends Service
{
    protected $modelName = "\App\Models\Localization\Unit";


    public function __construct(private UnitRepository $UnitRepository)
    {}


    public function getActiveUnits($data = [], $debug = 0)
    {
        return $this->UnitRepository->getActiveUnits($data, $debug);
    }


    public function deleteUnit($unit_id)
    {
        try {

            DB::beginTransaction();

            $this->UnitRepository->delete($unit_id);

            DB::commit();

            $result['success'] = true;

            return $result;

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }
}