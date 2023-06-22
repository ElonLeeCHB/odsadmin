<?php

namespace App\Domains\Admin\Services\Setting;

use Illuminate\Support\Facades\DB;
use App\Domains\Admin\Services\Service;
use App\Repositories\Eloquent\Setting\LocationRepository;

class LocationService extends Service
{
    protected $modelName = "\App\Models\Setting\Location";
	public $repository;

	public function __construct()
	{
        $this->repository = new LocationRepository;
	}


	public function updateOrCreate($data)
	{
        DB::beginTransaction();

        try {
            $location = $this->repository->findIdOrFailOrNew($data['location_id']);

			$location->name = $data['name'];
			$location->short_name = $data['short_name'] ?? '';
			$location->telephone = $data['telephone'] ?? '';
			//$location->geocode = $data['geocode'];
			$location->owner = $data['owner'] ?? '';

			$location->save();


            DB::commit();

            $result['location_id'] = $location->id;
    
            return $result;


        } catch (\Exception $ex) {
            DB::rollback();
            $result['error'] = $ex->getMessage();
            return $result;
        }
	}

}