<?php

namespace App\Domains\Admin\Services\Counterparty;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Repositories\Eloquent\Counterparty\OrganizationRepository;
use App\Repositories\Eloquent\Counterparty\SupplierRepository;
use App\Http\Resources\Inventory\SupplierCollection;
use App\Helpers\Classes\OrmHelper;
use App\Models\Counterparty\Supplier;
use App\Models\Counterparty\OrganizationMeta;

class SupplierService extends Service
{
    protected $modelName = "\App\Models\Counterparty\Supplier";

    public function getSuppliers($data = [], $debug = 0)
    {
        if (empty($data)) {
            $data = request()->all();
        }

        $query = Supplier::query();
        OrmHelper::prepare($query, $data);

        // 關鍵字查詢（需手動指定查詢欄位）
        if (!empty($data['filter_keyword'])) {
            $search = $data['filter_keyword'];

            $query->where(function ($q) use ($search) {
                OrmHelper::filterOrEqualColumn($q, 'filter_name', $search);

                $q->orWhere(function ($subQ) use ($search) {
                    OrmHelper::filterOrEqualColumn($subQ, 'filter_short_name', $search);
                });
            });

            unset($data['filter_keyword']);
        }

        $suppliers = OrmHelper::getResult($query, $data);

        return $suppliers ?? [];
    }

    public function saveSupplier($data, $supplier_id = null)
    {
        DB::beginTransaction();

        try {
            $supplier = OrmHelper::save($this->modelName, $data, $supplier_id);
            
            $result = $this->saveRowMetaData($supplier, $data);

            if(!empty($result['error'])){
                throw new \Exception($result['error']);
            }

            DB::commit();
    
            return ['id' => $supplier->id];

        } catch (\Throwable $ex) {
            DB::rollback();
            throw $ex;
        }
    }

    public function destroy($ids)
    {
        try {
            DB::beginTransaction();

            OrganizationMeta::whereIn('organization_id', $ids)->delete();
            Supplier::whereIn('id', $ids)->delete();

            DB::commit();

            return ['success' => true];
        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }

    public function delete($supplier_id)
    {
        try {
            DB::beginTransaction();

            OrganizationMeta::where('organization_id', $supplier_id)->delete();
            Supplier::where('organization_id', $supplier_id)->delete();

            DB::commit();

            return ['success' => true];
        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }
}