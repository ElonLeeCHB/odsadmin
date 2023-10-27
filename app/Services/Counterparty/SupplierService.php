<?php

namespace App\Services\Counterparty;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Repositories\Eloquent\Counterparty\SupplierRepository;
use App\Repositories\Eloquent\Counterparty\OrganizationRepository;
use App\Repositories\Eloquent\Common\TermRepository;

class SupplierService extends Service
{
    protected $modelName = "\App\Models\Counterparty\Organization";

	public function __construct(SupplierRepository $repository, protected OrganizationRepository $OrganizationRepository, protected TermRepository $TermRepository)
	{
        $this->repository = $repository;
    }

	public function getSuppliers($data = [], $debug = 0)
	{
		if(!empty($data['filter_keyword'])){
            $data['andOrWhere'][] = [
                'filter_name' => $data['filter_keyword'],
                'filter_short_name' => $data['filter_keyword'],
            ];
			unset($data['filter_keyword']);
		}
		$rows = $this->getRows($data, $debug);

        if(!empty($rows)){
            foreach ($rows as $row) {
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
            $supplier->telephone = $data['telephone'] ?? '';
            $supplier->fax = $data['fax'] ?? '';
            $supplier->comment = $data['comment'] ?? null;
            $supplier->is_active = $data['is_active'] ?? 1;
            $supplier->is_supplier = 1;
            $supplier->is_customer = $data['is_customer'] ?? 0;
            
            $supplier->shipping_state_id = $data['shipping_state_id'];
            $supplier->shipping_city_id = $data['shipping_city_id'];
            $supplier->shipping_address1 = $data['shipping_address1'];

            $supplier->save();

            $this->saveRowMetaData($supplier, $data);

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


    public function getActiveTaxTypesIndexByCode()
    {
        $filter_data = [
            'equal_taxonomy_code' => 'tax_type',
            'pagination' => false,
            'limit' => 0,
        ];
        
        $tax_types = $this->TermRepository->getTerms($filter_data)->toArray();

        foreach ($tax_types as $key => $tax_type) {
            unset($tax_type['translation']);
            unset($tax_type['taxonomy']);
            $tax_type_code = $tax_type['code'];
            $new_tax_types[$tax_type_code] = $tax_type;
        }

        return $new_tax_types;
    }

}