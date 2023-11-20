<?php

namespace App\Domains\Admin\Services\Counterparty;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Repositories\Eloquent\Counterparty\OrganizationRepository;
use App\Repositories\Eloquent\Inventory\SupplierRepository;
use App\Repositories\Eloquent\Common\TermRepository;

class SupplierService extends Service
{
    protected $modelName = "\App\Models\Counterparty\Organization";

    public function __construct(protected SupplierRepository $SupplierRepository,protected TermRepository $TermRepository)
    {
        $this->repository = $SupplierRepository;
    }

    public function getSuppliers($data = [], $debug = 0)
    {
        $suppliers = $this->repository->getSuppliers($data, $debug);

        if(!empty($suppliers)){
            foreach ($suppliers as $row) {
                $row->edit_url = route('lang.admin.counterparty.suppliers.form', array_merge([$row->id], $data));
            }
        }

        return $suppliers;
    }

}