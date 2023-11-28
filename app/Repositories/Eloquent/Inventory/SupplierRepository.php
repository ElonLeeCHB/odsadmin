<?php

namespace App\Repositories\Eloquent\Inventory;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Models\Counterparty\Organization;
use App\Models\Counterparty\OrganizationMeta;
use App\Helpers\Classes\DataHelper;
use App\Http\Resources\Inventory\SupplierCollection;

class SupplierRepository extends Repository
{
    protected $modelName = "\App\Models\Counterparty\Organization";

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

        // if(!empty($rows)){
        //     foreach ($rows as $row) {
        //         if(!empty($row->company)){
        //             $row->company_name = $row->company->name;
        //         }
        //         if(!empty($row->corporation)){
        //             $row->corporation_name = $row->corporation->name;
        //         }
        //     }
        // }

        return $rows;
    }

    public function saveSupplier($data)
    {
        DB::beginTransaction();

        try {
            $result = $this->findIdOrFailOrNew($data['supplier_id']);

            if(!empty($result['data'])){
                $supplier = $result['data'];
            }else if($result['error']){
                throw new \Exception($result['error']);
            }
            unset($result);

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
            
            $result = $this->saveRowMetaData($supplier, $data);

            if(!empty($result['error'])){
                throw new \Exception($result['error']);
            }

            DB::commit();

            // 常用廠商
            if(isset($data['is_often_used_supplier']) && $data['is_often_used_supplier']){


                $params = [
                    //'select' => ['id', 'name', 'short_name', 'tax_rate', 'formatted_tax_rate'],
                    'with' => 'metas',
                    'whereHas' => ['metas' => ['meta_key' => 'is_often_used_supplier', 'meta_value' => 1]],
                    'pagination' => false,
                    'limit' => 0
                ];
                $suppliers = $this->getRows($params);

                foreach ($suppliers as $key => $supplier) {
                    $this->getMetaRows($supplier);
                }

                $suppliers_resource_collection = (new SupplierCollection($suppliers))->toArray();
                
                $cache_name = 'cache/counterparty/suppliers/often_used.json';
                DataHelper::setJsonToStorage($cache_name, $suppliers_resource_collection);
            }
    
            return ['id' => $supplier->id];


        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }

    public function deleteSupplier($supplier_id)
    {
        DB::beginTransaction();

        try {
            OrganizationMeta::where('organization_id', $supplier_id)->delete();
            Organization::where('organization_id', $supplier_id)->delete();

            DB::commit();

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