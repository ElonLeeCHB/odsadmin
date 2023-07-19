<?php

namespace App\Repositories\Eloquent\Setting;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Models\Setting\Location;

class LocationRepository extends Repository
{
    public $modelName = "\App\Models\Setting\Location";


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

}