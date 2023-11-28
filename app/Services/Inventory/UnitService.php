<?php

namespace App\Services\Inventory;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\Service;
use App\Repositories\Eloquent\Inventory\UnitRepository;

class UnitService extends Service
{
    protected $modelName = "\App\Models\Inventory\Unit";

    public function __construct(UnitRepository $repository)
    {
        $this->repository = $repository;
    }


	public function updateOrCreateUnit($data)
	{
        return $this->repository->updateOrCreateUnit($data);
	}

    public function deleteUnitById($unit_id)
    {
        try {

            $this->repository->deleteUnitById($unit_id);

            return ['success' => true];

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }

}