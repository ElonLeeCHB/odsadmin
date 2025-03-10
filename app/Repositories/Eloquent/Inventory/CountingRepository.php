<?php

namespace App\Repositories\Eloquent\Inventory;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Models\Inventory\Counting;
use App\Models\Inventory\CountingProduct;
use App\Models\Material\ProductUnit;
use App\Models\Material\Product;
use App\Repositories\Eloquent\Inventory\UnitRepository;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Repositories\Eloquent\Catalog\ProductRepository;
use App\Traits\Model\EloquentTrait;

use Maatwebsite\Excel\Facades\Excel;
use App\Domains\Admin\ExportsLaravelExcel\CommonExport;
use App\Domains\Admin\Exports\InventoryCountingListExport;
use App\Helpers\Classes\DataHelper;

use App\Helpers\Classes\UnitConverter;
//use App\Repositories\Eloquent\UserCopy\UserRepository;

class CountingRepository
{
    use EloquentTrait;

    public $modelName = "\App\Models\Inventory\Counting";


    public function __construct(private UnitRepository $UnitRepository, private ProductRepository $ProductRepository, private TermRepository $TermRepository)
    {}

    
    public function getCountings($data, $debug = 0)
    {
        $filter_data = $this->resetQueryData($data);

        $rows = $this->getRows($filter_data, $debug);
        
        foreach ($rows as $row) {

            // 額外欄位 掛載到資料集
            if(!empty($data['extra_columns'])){

            }
        }

        return $rows ?? [];
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
            $result = $this->findIdOrFailOrNew($data['counting_id']);

            if(!empty($result['data'])){
                $counting = $result['data'];
            }else if($result['error']){
                throw new \Exception($result['error']);
            }
            unset($result);

            $counting->location_id = $data['location_id'] ?? 0;
            //$counting->code = (Observer)
            $counting->form_date = $data['form_date'];
            $counting->stocktaker = $data['stocktaker'] ?? '';
            $counting->status_code = !empty($data['status_code']) ? $data['status_code'] : 'P';
            $counting->comment = $data['comment'];
            $counting->total = $data['total'];
            $counting->created_user_id = auth()->user()->id;
            $counting->modified_user_id = auth()->user()->id;

            $counting->save();
            
            DB::commit();

            
            DB::beginTransaction();

            if(!empty($data['products'])){
                $local_units = $this->UnitRepository->getLocaleKeyedActiveUnits(toArray:true);
                
                CountingProduct::where('counting_id', $counting->id)->delete();

                $unitRepository = new UnitRepository;

                foreach ($data['products'] as $key => $product) {

                    $counting_quantity = str_replace(',','',$product['quantity']);

                    if(empty($product['id']) || empty($counting_quantity)){
                        continue;
                    }

                    // $counting_unit_name = $product['unit_name'];
                    // $counting_unit_code = $product['unit_code'] ?? '';
                    // if(empty($counting_unit_code) && (!empty($counting_unit_name) && !empty($local_units[$counting_unit_name]))){
                    //     $counting_unit_code = $local_units[$counting_unit_name]['code'];
                    // }

                    $counting_unit_code = $product['unit_code'] ?? '';

                    $stock_unit_name = $product['stock_unit_name'];
                    $stock_unit_code = $product['stock_unit_code'] ?? '';
                    if(empty($stock_unit_code) && (!empty($stock_unit_name) && !empty($local_units[$stock_unit_name]))){
                        $stock_unit_code = $local_units[$stock_unit_name]['code'];
                    }

                    // CountingProduct
                    $upsert_data1[$key] = [
                        'counting_id' => $counting->id,
                        'product_id' => $product['id'],
                        //'product_name' => $product['name'],
                        //'product_specification' => $product['specification'],
                        'unit_code' => $counting_unit_code,
                        'price' => $product['price'],
                        'quantity' => $counting_quantity,
                        'amount' => $product['amount'],
                        'stock_unit_code' => $stock_unit_code,
                        'stock_quantity' => $product['stock_quantity'],
                    ];

                    // Product
                    $upsert_data2[$key] = [
                        'id' => $product['id'],
                        'quantity' => $product['stock_quantity'],
                        //'from_quantity' => $counting_quantity,
                    ];
                }


                if(!empty($upsert_data1)){
                    CountingProduct::upsert($upsert_data1, ['id']);
                }

                if(!empty($upsert_data2)){
                    Product::upsert($upsert_data2, ['id']);
                }
            }

            DB::commit();

            return ['id' => $counting->id, 'code' => $counting->code];

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }

    public function resetQueryData($data)
    {
        if(!empty($data['filter_form_date'])){
            $rawSql = $this->parseDateToSqlWhere('form_date', $data['filter_form_date']);
            if($rawSql){
                $data['whereRawSqls'][] = $rawSql;
            }
            unset($data['filter_form_date']);
        }

        // 狀態
        if(!empty($data['filter_status_code']) && $data['filter_status_code'] == 'withoutV'){
            $data['whereNotIn'] = ['status_code' => ['V']];
            unset($data['filter_status_code']);
        }
        

        //刪除空值
        foreach ($data as $key => $value) {
            if(str_starts_with($key, 'filter_') || str_starts_with($key, 'equal_')){
                if($value == ''){
                    unset($data[$key]);
                }
            }
        }

        return $data;
    }

    public function import($filename, $counting_id = null)
    {
        DB::beginTransaction();

        try {
            if ($filename) {
                $result = $this->findIdOrFailOrNew($counting_id);

                if(!empty($result['data'])){
                    $counting = $result['data'];
                }else if($result['error']){
                    throw new \Exception($result['error']);
                }
                unset($result);

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

        $data_start_row_num = 5; // 這裡的5 = excel檔第 6 列

        // counting products 
        if(!empty($sheet[$data_start_row_num][0])){ //$sheet[5][0] = excel檔第 6 列的 product id

            //以當前語言的單位名稱做為索引
            $local_units = $this->UnitRepository->getLocaleKeyedActiveUnits(toArray:true);

            $result['counting_products'] = [];

            foreach ($sheet as $rownum => $row) {
                if($rownum < $data_start_row_num){
                    continue;
                }

                if(empty($row[0])){
                    continue;
                }

                $product_id = $row[0];
                $counting_quantity = $row[6] ?? 0;

                $counting_unit_name = $row[4];
                $counting_unit_code = '';
                if(!empty($counting_unit_name) && !empty($local_units[$counting_unit_name])){
                    $counting_unit_code = $local_units[$counting_unit_name]['code'] ?? '';
                }

                $stock_unit_name = $row[3];
                $stock_unit_code = '';
                if(!empty($stock_unit_name) && !empty($local_units[$stock_unit_name])){
                    $stock_unit_code = $local_units[$stock_unit_name]['code'] ?? '';
                }

                //stock_quantity
                if($counting_unit_code == $stock_unit_code){
                    $stock_quantity = $row[6];
                }else{
                    $stock_quantity = UnitConverter::build()->qty($counting_quantity)
                            ->from($counting_unit_code)
                            ->to($stock_unit_code)
                            ->product($product_id)
                            ->get();
                }

                if(!is_numeric($stock_quantity)){
                    $stock_quantity = 0;
                }

                $price = 

                $amount = 0;
                if(is_numeric($row[5]) && is_numeric($row[6])){
                    $amount = $row[5]*$row[6];
                }
                $factor = 0;
                if(is_numeric($row[5]) && is_numeric($row[6]) && $row[5]!==0 && $row[6]!==0 ){
                    $factor = $stock_quantity / $counting_quantity;
                }

                $result['counting_products'][] = (object) [
                    'product_id' => $row[0],
                    'product_name' => $row[1],
                    'product_specification' => $row[2],
                    'stock_unit_name' => $row[3],
                    'unit_name' => $row[4],
                    'price' => $row[5],
                    'quantity' => $row[6],
                    'amount' => $amount,
                    
                    'unit_code' => $counting_unit_code,
                    'stock_unit_code' => $stock_unit_code,
                    'stock_quantity' => $stock_quantity,
                    'factor' => $factor,
                    'product_edit_url' => route('lang.admin.inventory.products.form', $row[0]),
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