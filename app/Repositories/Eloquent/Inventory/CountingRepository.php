<?php

namespace App\Repositories\Eloquent\Inventory;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Models\Inventory\Counting;
use App\Models\Inventory\CountingProduct;
use App\Repositories\Eloquent\Common\UnitRepository;
use App\Traits\EloquentTrait;
use Maatwebsite\Excel\Facades\Excel;

class CountingRepository
{
    use EloquentTrait;

    public $modelName = "\App\Models\Inventory\Counting";


    public function __construct(private UnitRepository $UnitRepository)
    {}

    
    public function getCountingTasks()
    {
        $getCountingTasks = [];


        return $getCountingTasks;
    }

    public function delete($id)
    {
        DB::beginTransaction();

        try {

            

            //Warehouse::where('id', $warehouse_id)->delete();

            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }


    public function import($filename, $counting_id = null)
    {
        DB::beginTransaction();

        try {
            if ($filename) {
                $counting = $this->findIdOrFailOrNew($counting_id);

                $data = Excel::toArray(new \App\Domains\Admin\Imports\Common, $filename);

                $sheet = $data[0];

                $counting->location_id = $sheet[0][1]; //門市代號
                $counting->comment = $sheet[0][4]; //備註

                $counting_date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($sheet[2][1])->format('Y-m-d');
                $counting->counting_date = $counting_date; //日期

                if(empty($counting->id)){
                    $counting->created_user_id = auth()->id();
                }

                $counting->modified_user_id = auth()->id();

                $counting->save();
                

                $counting_id = $counting->id;

                // counting products
                if(!empty($sheet[8])){

                    $local_units = $this->UnitRepository->getLocaleKeyedActiveUnits();


                    //$sheet[8] = excel檔第 7 列的品號

                    CountingProduct::where('counting_id', $counting->id)->delete();

                    foreach ($sheet as $rownum => $row) {
                        if($rownum < 6){
                            continue;
                        }

                        $locale_unit_name = $row[4];
                        if(!empty($locale_unit_name) && !empty($local_units[$locale_unit_name])){
                            $unit_code = $local_units[$locale_unit_name]['code'];
                        }

                        if(empty($unit_code)){
                            DB::rollBack();
                            throw new \Exception('Unit error. Product ID:' . $row[0]);
                        }

                        // amount
                        $arr = [
                            'counting_id' => $counting_id,
                            'product_id' => $row[0],
                            'unit_code' => $unit_code,
                            'price' => !empty($row[5]) ? $row[5] : 0,
                            'quantity' => $row[6],
                            'amount' => $row[5]*$row[6],
                            'comment' => $row[8],
                        ];
                        $upsert_data[] = $arr;
                    }

                    if(!empty($upsert_data)){
                        CountingProduct::insert($upsert_data);
                    }
                }

                DB::commit();
                
                return ['id' => $counting->id, 'code' => $counting->code];
            }

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        } 
        

    }
}