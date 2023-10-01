<?php

namespace App\Domains\Admin\Services\Counterparty;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Repositories\Eloquent\Counterparty\OrganizationRepository;
use App\Models\Counterparty\OrganizationMeta;

class OrganizationService extends Service
{
    protected $modelName = "\App\Models\Counterparty\Organization";

	public function __construct(protected OrganizationRepository $OrganizationRepository)
	{}

	public function getOrganizations($data=[], $debug = 0)
	{
		if(!empty($data['filter_mixed_name'])){
            $data['andOrWhere'][] = [
                'filter_name' => $data['filter_mixed_name'],
                'filter_short_name' => $data['filter_mixed_name'],
            ];
			unset($data['filter_mixed_name']);
		}

        $data['with'] = ['company', 'corporation'];
		
		$rows = $this->getRows($data);

		if(!empty($rows)){
            foreach ($rows as $row) {
                $row->edit_url = route('lang.admin.organization.organizations.form', array_merge([$row->id], $data));
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
            foreach ($data as $key => $value) {
                $data[$key] = trim($value);
            }

            $organization = $this->findIdOrFailOrNew($data['organization_id']);

            $organization->parent_id = $data['parent_id'] ?? 0;
            $organization->code = $data['code'];
            $organization->name = $data['name'];
            $organization->short_name = $data['short_name'] ?? null;
            $organization->tax_id_num = $data['tax_id_num'] ?? null;

            $organization->save();
            echo '<pre>', print_r(999, 1), "</pre>"; exit;
            $this->saveMetaDataset($organization, $data);

            DB::commit();
            
            return ['organization_id' => $organization->id];

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' =>$ex->getMessage()];
        }
    }

	public function add($data)
	{
        $organization = $this->newModel();
        $organization->code = $data['code'] ?? null;
        $organization->parent_id = $data['parent_id'] ?? 0;
        $organization->name = $data['name'];
        $organization->short_name = $data['short_name'];
        $organization->telephone = $data['telephone'] ?? null;
        $organization->corporation_id = $data['corporation_id'] ?? 0;
        $organization->company_id = $data['company_id'] ?? 0;;
        $organization->is_corporation = $data['is_corporation'] ?? 0;
        $organization->is_juridical_entity = $data['is_juridical_entity'] ?? 0; //是否法人組織
        $organization->is_active = $data['is_active'] ?? 0;
        $organization->save();

        if(!empty($data['tin'])){
            $organization_meta = new OrganizationMeta;
            $organization_meta->organization_id = $organization->id;
            $organization_meta->meta_key = 'tin';
            $organization_meta->meta_value = $data['tin'];
            $organization_meta->save();
        }

        return $organization->id;
	}

	public function edit($id, $data)
    {
        $organization = $this->newModel()->find($id);

        $organization->code = $data['code'] ?? null;
        $organization->parent_id = $data['parent_id'] ?? 0;
        $organization->name = $data['name'];
        $organization->short_name = $data['short_name'];
        $organization->telephone = $data['telephone'] ?? null;
        $organization->corporation_id = $data['corporation_id'] ?? 0;
        $organization->company_id = $data['company_id'] ?? 0;;
        $organization->is_corporation = $data['is_corporation'] ?? 0;
        $organization->is_juridical_entity = $data['is_juridical_entity'] ?? 0; //是否法人組織
        $organization->is_active = $data['is_active'] ?? 0;
        $organization->save();

        if(!empty($data['tin'])){
            $organization_meta = new OrganizationMeta;
            $organization_meta->organization_id = $organization->id;
            $organization_meta->meta_key = 'tin';
            $organization_meta->meta_value = $data['tin'];
            $organization_meta->save();
        }

        return $organization->id;
    }


    public function api()
    {

        /*

        分公司統編查分公司資料
        'https://data.gcis.nat.gov.tw/od/data/api/23632BB3-5DB7-4423-9643-1D4AC140D479?$format=json&$filter=Branch_Office_Business_Accounting_NO%20eq%20' + 統編;


        */


    }
}