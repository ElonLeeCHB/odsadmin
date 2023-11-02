<?php

namespace App\Repositories\Eloquent\Inventory;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Models\Inventory\Counting;
use App\Models\Inventory\CountingProduct;
use App\Repositories\Eloquent\Common\UnitRepository;
use App\Repositories\Eloquent\Catalog\ProductRepository;
use App\Traits\EloquentTrait;

use Maatwebsite\Excel\Facades\Excel;
use App\Domains\Admin\ExportsLaravelExcel\CommonExport;
use App\Domains\Admin\Exports\InventoryCountingListExport;

class CountingRepository
{
    use EloquentTrait;

    public $modelName = "\App\Models\Inventory\Counting";


    public function __construct(private UnitRepository $UnitRepository, private ProductRepository $ProductRepository)
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

    public function saveCounting($data)
    {
        DB::beginTransaction();

        try {

            $counting = $this->findIdOrFailOrNew($data['counting_id']);

            $counting->location_id = $data['location_id'] ?? 0;
            //$counting->code = (Observer)
            $counting->form_date = $data['form_date'];
            $counting->status_code = $data['status_code'] ?? null;
            $counting->comment = $data['comment'];
            $counting->total = $data['total'];
            $counting->created_user_id = auth()->user()->id;
            $counting->modified_user_id = auth()->user()->id;

            $counting->save();

            if(!empty($data['products'])){
                $local_units = $this->UnitRepository->getLocaleKeyedActiveUnits();
                
                CountingProduct::where('counting_id', $counting->id)->delete();

                foreach ($data['products'] as $product) {

                    $unit_name = $product['unit_name'];
                    if(!empty($unit_name) && !empty($local_units[$unit_name])){
                        $unit_code = $local_units[$unit_name]['code'];
                    }

                    // amount
                    $arr = [
                        'counting_id' => $counting->id,
                        'product_id' => $product['id'],
                        //'product_name' => $product['name'],
                        //'product_specification' => $product['specification'],
                        'unit_code' => $unit_code,
                        'price' => $product['price'],
                        'quantity' => $product['quantity'],
                        'amount' => $product['amount'],
                    ];
                    $upsert_data[] = $arr;
                }
                
                if(!empty($upsert_data)){
                    CountingProduct::upsert($upsert_data, ['id']);
                }
            }

            DB::commit();

            return ['id' => $counting->id, 'code' => $counting->code];

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

                //日期
                $form_date = $sheet[2][1];
                //$form_date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($sheet[2][1])->format('Y-m-d');
                $counting->form_date = $form_date; 

                if(empty($counting->id)){
                    $counting->created_user_id = auth()->id();
                }

                $counting->modified_user_id = auth()->id();

                $counting->save();
                
                $counting_id = $counting->id;

                // counting products
                if(!empty($sheet[8])){

                    //以當前語言的單位名稱做為索引
                    $local_units = $this->UnitRepository->getLocaleKeyedActiveUnits();


                    //$sheet[8] = excel檔第 7 列的品號

                    CountingProduct::where('counting_id', $counting->id)->delete();

                    foreach ($sheet as $rownum => $row) {
                        if($rownum < 7){  // 這裡的 7 = excel 的第 6 列
                            continue;
                        }

                        $counting_unit_name = $row[4];
                        if(!empty($counting_unit_name) && !empty($local_units[$counting_unit_name])){
                            $unit_code = $local_units[$counting_unit_name]['code'];
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
                            //'comment' => $row[8],
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

    public function readExcel($filename, $counting_id = null)
    {
        $data = Excel::toArray(new \App\Domains\Admin\Imports\Common, $filename);

        $sheet = $data[0];

        $result['location_id'] = $sheet[0][1]; //門市代號
        $result['comment'] = $sheet[0][4]; //備註
        $result['form_date'] = $sheet[2][1];

        // counting products 
        
        if(!empty($sheet[7][0])){ //$sheet[7][0] = excel檔第 6 列的品號

            //以當前語言的單位名稱做為索引
            $local_units = $this->UnitRepository->getLocaleKeyedActiveUnits();

            $result['counting_products'] = [];

            foreach ($sheet as $rownum => $row) {
                if($rownum < 5){  // 這裡的 7 = excel 的第 6 列
                    continue;
                }

                $counting_unit_name = $row[4];
                if(!empty($counting_unit_name) && !empty($local_units[$counting_unit_name])){
                    $unit_code = $local_units[$counting_unit_name]['code'];
                }

                $result['counting_products'][] = (object) [
                    'product_id' => $row[0],
                    'product_name' => $row[1],
                    'product_specification' => $row[2],
                    'stock_unit_name' => $row[3],
                    'unit_name' => $row[4],
                    'price' => $row[5],
                    'quantity' => $row[6],
                    'amount' => $row[5]*$row[6],
                    //'comment' => $row[8],
                ];
            }
        }

        return $result;
    }


    public function exportCountingProductList($post_data = [], $debug = 0)
    {
        $filename = '盤點表_'.date('Y-m-d_H-i-s').'.xlsx';

        return Excel::download(new InventoryCountingListExport($post_data, $this->ProductRepository), $filename);
    }
}