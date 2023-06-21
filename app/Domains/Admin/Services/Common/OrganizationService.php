<?php

namespace App\Domains\Admin\Services\Common;

use App\Domains\Admin\Services\Service;
use App\Repositories\Eloquent\Member\OrganizationRepository;
use App\Repositories\Eloquent\Member\OrganizationMetaRepository;
use App\Repositories\Eloquent\Member\MemberRepository;
use Illuminate\Support\Facades\Validator;
use App\Libraries\TranslationLibrary;
use DB;

class OrganizationService extends Service
{
    private $lang;
    
	public function __construct(protected OrganizationRepository $repository
        , private OrganizationMetaRepository $organizationMetaRepository
        , private MemberRepository $memberRepository)
	{
        
        $this->lang = (new TranslationLibrary())->getTranslations(['admin/member/organization',]);
	}

	public function getRows($data=[], $debug = 0)
	{
		if(!empty($data['filter_mixed_name'])){
            $data['andOrWhere'][] = [
                'filter_name' => $data['filter_mixed_name'],
                'filter_short_name' => $data['filter_mixed_name'],
            ];
			unset($data['filter_mixed_name']);
		}

        $data['with'] = ['company', 'corporation'];
		
		$rows = $this->repository->getRows($data);

		if(!empty($rows)){
            foreach ($rows as $row) {
                $row->edit_url = route('lang.admin.member.organizations.form', array_merge([$row->id], $data));
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

            $organization = $this->repository->findOrNew(['id' => $data['organization_id']]);
            $organization->uniform_invoice_no = $data['uniform_invoice_no'] ?? null;
            $organization->type1 = $data['type1'] ?? null;
            $organization->name = $data['name'];
            $organization->short_name = $data['short_name'] ?? null;
            $organization->telephone_prefix = $data['telephone_prefix'] ?? null;
            $organization->telephone = str_replace('-','',$data['telephone']) ?? null;

            $organization->save();

            DB::commit();
            
            $result['data']['organization_id'] = $organization->id;

            return $result;

        } catch (\Exception $ex) {
            DB::rollback();
            $msg = $ex->getMessage();
            $json['error'] = $msg;
            return $json;
        }
    }

	public function add($data)
	{
        $organization = $this->repository->newModel();
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
            $organization_meta = $this->organizationMetaRepository->newModel();
            $organization_meta->organization_id = $organization->id;
            $organization_meta->meta_key = 'tin';
            $organization_meta->meta_value = $data['tin'];
            $organization_meta->save();
        }

        return $organization->id;
	}

	public function edit($id, $data)
    {
        $organization = $this->repository->newModel()->find($id);

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
            $organization_meta = $this->organizationMetaRepository->newModel();
            $organization_meta->organization_id = $organization->id;
            $organization_meta->meta_key = 'tin';
            $organization_meta->meta_value = $data['tin'];
            $organization_meta->save();
        }

        return $organization->id;
    }

    public function validator(array $data)
    {
        return Validator::make($data, [
                'organization_id' =>'nullable|integer',
                'code' =>'nullable|unique:organizations,code,'.$data['organization_id'],
                'name' =>'nullable|max:10',
                'short_name' =>'nullable|max:10',
                'mobile' =>'nullable|min:9|max:15',
                'telephone' =>'nullable|min:7|max:15',
            ],[
                'organization_id.*' => $this->lang->error_organization_id,
                'code.*' => $this->lang->error_code,
                'name.*' => $this->lang->error_name,
                'short_name.*' => $this->lang->error_short_name,
                'mobile.*' => $this->lang->error_mobile,
                'telephone.*' => $this->lang->error_telephone,
        ]);
    }


    public function api()
    {

        /*

        分公司統編查分公司資料
        'https://data.gcis.nat.gov.tw/od/data/api/23632BB3-5DB7-4423-9643-1D4AC140D479?$format=json&$filter=Branch_Office_Business_Accounting_NO%20eq%20' + 統編;


        */


    }
}