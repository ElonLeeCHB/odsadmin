<?php

namespace App\Domains\Admin\Services\Counterparty;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Repositories\Eloquent\Counterparty\OrganizationRepository;

class SupplierService extends Service
{
    protected $modelName = "\App\Models\Counterparty\Organization";

	public function __construct(protected OrganizationRepository $OrganizationRepository)
	{
        $this->OrganizationRepository = $OrganizationRepository;
	}

	public function getSuppliers($data=[], $debug = 0)
	{
		if(!empty($data['filter_keyword'])){
            $data['andOrWhere'][] = [
                'filter_name' => $data['filter_keyword'],
                'filter_short_name' => $data['filter_keyword'],
            ];
			unset($data['filter_keyword']);
		}

        $data['with'] = 'payment_term';
		
		$rows = $this->getRows($data, $debug);

		if(!empty($rows)){
            foreach ($rows as $row) {
                $row->edit_url = route('lang.admin.counterparty.organizations.form', array_merge([$row->id], $data));
				if(!empty($row->company)){
					$row->company_name = $row->company->name;
				}
				if(!empty($row->corporation)){
					$row->corporation_name = $row->corporation->name;
				}
            }
        }

		return $rows;
	}

	public function updateOrCreate($data)
	{
        DB::beginTransaction();

        try {

            $supplier = $this->findIdOrFailOrNew($data['organization_id']);

            $supplier->parent_id = $data['parent_id'] ?? 0;
            $supplier->code = $data['code'];
            $supplier->name = $data['name'];
            $supplier->short_name = $data['short_name'] ?? null;
            $supplier->tax_id_num = $data['tax_id_num'] ?? null;
            $supplier->payment_term_id = $data['payment_term_id'] ?? 0;
            $supplier->is_active = $data['is_active'] ?? 1;
            $supplier->is_supplier = 1;
            $supplier->is_customer = $data['is_customer'] ?? 0;

            $supplier->save();

            $this->saveMetaDataset($supplier, $data);

            DB::commit();

            $result['supplier_id'] = $supplier->id;
    
            return $result;


        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
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