<?php

namespace App\Domains\Admin\Services\Localization;

use App\Domains\Admin\Services\Service;
use App\Libraries\TranslationLibrary;
use App\Repositories\Eloquent\Localization\AddressRepository;
use App\Repositories\Eloquent\Localization\DivisionRepository;
use App\Repositories\Eloquent\Localization\CountryRepository;
use Illuminate\Support\Facades\Validator;

class AddressService extends Service
{
    protected $modelName = "\App\Models\Localization\Address";
	private $lang;

	public function __construct(public AddressRepository $repository
		, private DivisionRepository $divisionRepository
		, private CountryRepository $countryRepository
	)
	{
		$this->lang = (new TranslationLibrary())->getTranslations(['admin/localization/address',]);
	}

	public function getRows($queries=[], $debug=0)
	{
		$rows = $this->repository->getRows($queries, $debug);

		if(count($rows) == 0){
			return $rows;
		}

		foreach($rows as $row){
			$row->zone_name = $row->zone->name;
			$row->is_enabled = 1;
		}
		
        return $rows;
	}

	public function deleteThenCreate($address, $_debug = 0)
	{            
        // Either user_id or organization_id
        if(!empty($address['user_id']) && !empty($address['organization_id'])){
            $json['error']['user_id'] = $this->lang->trans('error_user_organization_id');
            $json['error']['organization_id'] = $this->lang->trans('error_user_organization_id');
        }

        // Zone
        if(empty($address['zone_id'])){
            if(!empty($address['zone'])){
                $where = [
                    'filter_country_id' => $address['country_id'],
                    'filter_name' => $address['zone'],
                    'filter_level' => 1,
                    'regx' => false,
                ];
                $zone = $this->divisionRepository->getRow($where);
            }

            if(!empty($zone)){
                $address['zone_id'] = $zone->id;
            }else{
                $json['error']['zone'] = $this->lang->trans('error_zone');
            }
        }

        // City
        if(empty($address['city'])){
            $json['error']['city'] = $this->lang->trans('error_city');
        }

		if (!$json) {
            if(empty($address['user_id']) && empty($address['organization_id'])){
                $address_id = $this->repository->create($data);
            }
            else{
                $address_id = $this->repository->update($data);
            }
            $json['success'] = $this->lang->trans('text_success');                
        }

		return $json;
    }
	
	public function validator(array $data)
    {
		$result = Validator::make($data, [
			'user_id' =>'nullable|integer',
			'organization_id' =>'nullable|integer',
			'name' =>'nullable|max:20',
			'address_1' =>'required|min:2|max:10',
			'address_2' =>'required|min:2|max:20',
			'city' =>'required|min:2|max:10',
			'zone_id' =>'required|integer',
			'country_id' =>'integer',
		],[
			'user_id.*' => $this->lang->error_user_organization_id,
			'organization_id.*' => $this->lang->error_user_organization_id,
			'name.*' => $this->lang->error_name,
			'address_1.*' => $this->lang->error_address_1,
			'address_2.*' => $this->lang->error_address_2,
			'city.*' => $this->lang->error_city,
			'zone_id.*' => $this->lang->error_zone,
			'country_id.*' => $this->lang->error_country,
		]);

        return $result;
    }
}