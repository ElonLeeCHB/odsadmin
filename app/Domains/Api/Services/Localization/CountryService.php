<?php

namespace App\Domains\Api\Services\Localization;

use App\Domains\Api\Services\Service;

class CountryService extends Service
{
    public $modelName = "\App\Models\Localization\Country";
	private $lang;
	
	public function __construct()
	{
	}

	public function getCountries($data = [])
	{
        $cacheName = app()->getLocale() . '_countries';

        $result = cache()->remember($cacheName, 60*60*24*365, function() use ($data) {
			if(empty($data)){
				$data = [
                    'filter_is_active' => '1',
                    'regexp' => false,
                    'pagination' => false,
                    'limit' => 0,
				];
			}
            return $this->getRecords($data);
        });

        if(empty($result)){
            $result = [];
        }

        return $result;
	}
}