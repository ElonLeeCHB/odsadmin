<?php

namespace App\Domains\Admin\Services\Setting;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Repositories\Eloquent\Setting\LocationRepository;

class LocationService extends Service
{
    protected $modelName = "\App\Models\Sale\Location";
    public $repository;


    public function __construct(protected LocationRepository $LocationRepository)
    {
        $this->repository = $LocationRepository;
    }


    public function getLocations($data=[], $debug = 0)
    {
        $rows = $this->LocationRepository->getLocations($data, $debug);

        return $rows;
    }

    public function updateOrCreate($data)
    {
        DB::beginTransaction();

        try {
            $result = $this->findIdOrFailOrNew($data['location_id']);

            if(empty($result['error']) && !empty($result['data'])){
                $location = $result['data'];
            }else{
                return response(json_encode($result))->header('Content-Type','application/json');
            }

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


    public function deleteLocation($location_id)
    {
        try {

            DB::beginTransaction();

            $this->LocationRepository->delete($location_id);

            DB::commit();

            $result['success'] = true;

            return $result;

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }
}