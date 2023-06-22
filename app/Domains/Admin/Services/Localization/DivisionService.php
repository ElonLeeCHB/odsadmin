<?php

namespace App\Domains\Admin\Services\Localization;

use App\Domains\Admin\Services\Service;
use App\Repositories\Eloquent\Localization\DivisionRepository;

class DivisionService extends Service
{
    protected $modelName = "\App\Models\Localization\Division";
	private $lang;

	public function __construct(public DivisionRepository $repository)
	{
		$this->repository = $repository;
	}

	public function getStates($filter_data=[], $debug = 0)
    {
        $cacheName = app()->getLocale() . '_states';

        $result = cache()->remember($cacheName, 60*60*24*14, function() use ($filter_data){
			if(empty($filter_data)){
				$filter_data = [
					'filter_country_code' => 'tw',
					'filter_level' => '1',
					'filter_is_active' => '1',
					'sort' => 'id',
					'order' => 'ASC',
					'regx' => 0,
					'pagination' => false,
					'limit' => 0,
				];
			}
            $collections = $this->repository->getRows($filter_data);
            
            return $collections;
        });

        if(empty($result)){
            $result = [];
        }

        return $result;
    }


	public function getCities($data = [], $debug = 0)
	{
		$filter_data = [];

		// Re-define array to make sure it matches the table's index
		if(empty($data['filter_country_code'])){
			$filter_data['filter_country_code'] = 'tw';
		}else{
			$filter_data['filter_country_code'] = $data['filter_country_code'];
		}

		$filter_data['filter_level'] = 2;

		if(empty($data['filter_parent_id'])){
			return false;
		}else{
			$filter_data['filter_parent_id'] = $data['filter_parent_id'];
		}

		$filter_data['filter_is_active'] = 1;
		$filter_data['regexp'] = false;
		$filter_data['pagination'] = false;
		$filter_data['limit'] = 0;

		if(empty($data['sort'])){
			$filter_data['sort'] = 'postal_code';
			$filter_data['order'] = 'ASC';
		}else{
			$filter_data['sort'] = $data['sort'];
			$filter_data['order'] = $data['order'];
		}

        $rows = $this->repository->getRows($filter_data, $debug);
    
        return $rows;
	}
}