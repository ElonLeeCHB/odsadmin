<?php

namespace App\Domains\Admin\Services\Inventory;

use Illuminate\Support\Facades\DB;
use App\Domains\Admin\Services\Service;
use App\Repositories\Eloquent\Organization\OrganizationRepository;

class SupplierService extends Service
{
    private $modelName = "\App\Models\Organization\Organization";
	protected $repository;

	public function __construct()
	{
        $this->repository = new OrganizationRepository;
	}


	public function updateOrCreate($data)
	{
        DB::beginTransaction();

        try {
            $supplier = $this->repository->findIdOrFailOrNew($data['supplier_id']);

			$supplier->code = $data['code'];
			$supplier->name = $data['name'];
			$supplier->short_name = $data['short_name'] ?? '';

			$supplier->save();


            DB::commit();

            $result['supplier_id'] = $supplier->id;
    
            return $result;


        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
	}

}