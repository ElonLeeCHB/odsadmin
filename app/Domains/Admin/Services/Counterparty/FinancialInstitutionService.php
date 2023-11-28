<?php

namespace App\Domains\Admin\Services\Counterparty;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Repositories\Eloquent\Counterparty\FinancialInstitutionRepository;

class FinancialInstitutionService extends Service
{
    protected $modelName = "\App\Models\SysData\FinancialInstitution";

	public function __construct(private FinancialInstitutionRepository $FinancialInstitutionRepository)
	{}

    public function updateOrCreate($data)
    {
        DB::beginTransaction();

        try {
            $result = $this->findIdOrFailOrNew($data['institution_id']);

            //if(empty($result['error']) && !empty($result['data'])){
            if(!empty($result['data'])){
                $row = $result['data'];
            }else{
                return response(json_encode($result))->header('Content-Type','application/json');
            }

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