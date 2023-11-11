<?php

namespace App\Domains\Admin\Services\Counterparty;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Services\Counterparty\SupplierService as GlobalSupplierService;
use App\Repositories\Eloquent\Counterparty\OrganizationRepository;
use App\Repositories\Eloquent\Common\TermRepository;

class SupplierService extends GlobalSupplierService
{
    protected $modelName = "\App\Models\Counterparty\Organization";

	public function __construct(protected OrganizationRepository $OrganizationRepository,protected TermRepository $TermRepository)
	{}

	public function getSuppliers($data = [], $debug = 0)
	{
        $suppliers = parent::getSuppliers($data, $debug);

		if(!empty($suppliers)){
            foreach ($suppliers as $row) {
                $row->edit_url = route('lang.admin.counterparty.organizations.form', array_merge([$row->id], $data));
            }
        }

		return $suppliers;
	}

    public function deleteSupplier($supplier_id)
    {
        try {

            $this->OrganizationRepository->delete($supplier_id);

            return ['success' => true];

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }

}