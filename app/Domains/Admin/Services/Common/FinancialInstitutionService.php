<?php

namespace App\Domains\Admin\Services\Common;

use App\Domains\Admin\Services\Service;
use Illuminate\Support\Facades\DB;

class FinancialInstitutionService extends Service
{
    protected $modelName = "\App\Models\Common\FinancialInstitution";

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

}