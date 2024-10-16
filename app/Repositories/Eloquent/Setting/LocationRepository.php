<?php

namespace App\Repositories\Eloquent\Setting;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Models\Setting\Location;

class LocationRepository extends Repository
{
    public $modelName = "\App\Models\Setting\Location";


    public function getLocations($data = [], $debug = 0)
    {
        if(!empty($data['filter_keyword'])){
            $data['andOrWhere'][] = [
                'filter_name' => $data['filter_keyword'],
                'filter_short_name' => $data['filter_keyword'],
            ];
            unset($data['filter_keyword']);
        }

        $locations = $this->getRows($data, $debug);

        return $locations;
    }

    public function delete($location_id)
    {
        try {

            DB::beginTransaction();

            Location::where('id', $location_id)->delete();

            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }

    public function destroy($ids, $debug = 0)
    {
        try {
            DB::beginTransaction();

            $result = Location::whereIn('id', $ids)->delete();
            
            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }

}