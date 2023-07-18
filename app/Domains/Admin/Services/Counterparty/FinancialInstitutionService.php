<?php

namespace App\Domains\Admin\Services\Counterparty;

use Illuminate\Support\Facades\DB;
use App\Domains\Admin\Services\Service;
use App\Repositories\Eloquent\Counterparty\FinancialInstitutionRepository;

class FinancialInstitutionService extends Service
{
    protected $modelName = "\App\Models\Counterparty\FinancialInstitution";

	public function __construct(protected FinancialInstitutionRepository $FinancialInstitutionRepository)
	{

	}

    public function updateOrCreate($data)
    {
        DB::beginTransaction();

        try {
            $row = $this->findIdOrFailOrNew($data['institution_id']);

            $row->code = $data['code'];
            $row->name = $data['name'];
            $row->short_name = $data['short_name'] ?? null;
            $row->is_active = $data['is_active'] ?? 1;

            $row->save();

            DB::commit();

            $result['row_id'] = $row->id;
            
            return $result;
        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }

    public function deleteFinancialInstitution($id)
    {
        try {

            $this->FinancialInstitutionRepository->delete($id);

            return ['success' => true];

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }

}