<?php

namespace App\Domains\Admin\Services\Inventory;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Repositories\Eloquent\Inventory\CountingRepository;
use App\Repositories\Eloquent\Setting\SettingRepository;
use App\Repositories\Eloquent\Catalog\ProductRepository;
use App\Models\Setting\Setting;
use App\Models\Catalog\ProductMeta;
use App\Models\Inventory\Counting;
use App\Models\Inventory\CountingProduct;
use App\Models\Catalog\Product;
use App\Repositories\Eloquent\Inventory\UnitRepository;

class CountingService extends Service
{
    protected $modelName = "\App\Models\Inventory\Counting";

    public function __construct(private CountingRepository $CountingRepository, private UnitRepository $UnitRepository)
    {
        $this->repository = $CountingRepository;
    }


    public function getCountingSettings()
    {
        $filter_data = [
            'equal_group' => 'inventory',
            'equal_setting_key' => 'inventory_counting_setting',
            'first' => 1,
            'pluck' => 'setting_value',

        ];
        $result = (new SettingRepository)->getRow($filter_data);

        if(empty($result['error'])){
            $inventory_counting_setting = json_decode($result);

            foreach ($inventory_counting_setting->products as $product) {
                $product_ids[] = $product->product_id;
            }

            // temperature_type_code 存在 product_metas
            $filter_data = [
                'select' => ['id','code','name'],
                'whereIn' => ['id' => $product_ids],
                'pagination' => 0,
                'limit' => 500,
                'keyBy' => 'id',
                'with' => ['metas'],
            ];
            $eloqProducts = (new ProductRepository)->getRows($filter_data);
            $eloqProducts = (new ProductRepository)->setMetasToRows($eloqProducts);
            
            $result = new \stdClass;

            foreach ($inventory_counting_setting->products as $product) {
                $result->products[] = (object)[
                    'sort_order' => $product->sort_order,
                    'product_id' => $product->product_id,
                    'product_name' => $eloqProducts[$product->product_id]->name,
                    'temperature_type_code' => $eloqProducts[$product->product_id]->temperature_type_code,
                    'temperature_type_name' => $eloqProducts[$product->product_id]->temperature_type_code,
                ];
            }

            return $result;
        }

        return [];
    }

    public function saveCountingSettings($post_data)
    {
        try {
            DB::beginTransaction();

            // 排序
            usort($post_data['products'], function($a, $b) {
                // 按照 sort_order 進行排序
                return $a['sort_order'] <=> $b['sort_order'];
            });

            if(!empty($post_data['products'])){
                // product_metas
                    $update_data = [];

                    foreach ($post_data['products'] ?? [] as $product) {
                        $update_data[] = [
                            'product_id' => $product['product_id'],
                            'meta_key' => 'temperature_type_code',
                            'meta_value' => $product['temperature_type_code'] ?? 0,
                        ];
                    }

                    if(!empty($update_data)){
                        //多筆用 upsert()
                        ProductMeta::upsert(
                            $update_data,
                            ['product_id', 'meta_key'],  // 查找條件：這裡使用 product_id 和 meta_key 來唯一識別
                            ['meta_value']  // 當記錄已存在時，更新的欄位
                        );
                    }
                // end product_metas

                // settings
                    $update_data = [];

                    $update_data['someField'] = 'someValue';

                    foreach ($post_data['products'] as $product) {
                        $update_data['products'][] = [
                            'product_id' => $product['product_id'],
                            'product_name' => $product['product_name'],
                            'sort_order' => $product['sort_order'],
                        ];
                    }

                    // 單一設定記錄用 updateOrCreate()
                    Setting::updateOrCreate(
                        // 查找條件
                        ['group' => 'inventory', 'setting_key' => 'inventory_counting_setting'], 
        
                        // 更新或新增的資料
                        ['location_id' => 0,
                        'group' => 'inventory',
                        'setting_key' => 'inventory_counting_setting',
                        'setting_value' => json_encode($update_data),
                        'is_json' => 1,
                        'comment' => '',
                        'is_autoload' => 0,
                        ] 
                    );
                // settings
            }

            DB::commit();

            return true;

        } catch (\Throwable $th) {
            DB::rollBack();
            report($th); //寫入 laravel 的錯誤機制
            return ['error' => $th->getMessage()];
        }

    }

    public function batchDelete($ids)
    {
        try {
            DB::beginTransaction();

            CountingProduct::whereIn('counting_id', $ids)->delete();
            Counting::whereIn('id', $ids)->delete();

            DB::commit();

            return true;

        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }

    }

    public function saveCounting($data)
    {
        try {

            $result = $this->findIdOrFailOrNew($data['counting_id']);

            if (!empty($result['data'])) {
                $counting = $result['data'];
            } else if ($result['error']) {
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

            DB::transaction(function () use ($counting) {
                $counting->save();
            });

            DB::transaction(function () use ($data, $counting) {

                if (!empty($data['products'])) {
                    $local_units = $this->UnitRepository->getLocaleKeyedActiveUnits(toArray: true);

                    CountingProduct::where('counting_id', $counting->id)->delete();

                    $unitRepository = new UnitRepository;

                    foreach ($data['products'] as $key => $product) {

                        if (empty($product['id']) || empty($product['quantity'])){
                            continue;
                        }

                        $counting_quantity = str_replace(',', '', $product['quantity']);

                        // $counting_unit_name = $product['unit_name'];
                        // $counting_unit_code = $product['unit_code'] ?? '';
                        // if(empty($counting_unit_code) && (!empty($counting_unit_name) && !empty($local_units[$counting_unit_name]))){
                        //     $counting_unit_code = $local_units[$counting_unit_name]['code'];
                        // }

                        $counting_unit_code = $product['unit_code'] ?? '';

                        $stock_unit_name = $product['stock_unit_name'];
                        $stock_unit_code = $product['stock_unit_code'] ?? '';
                        if (empty($stock_unit_code) && (!empty($stock_unit_name) && !empty($local_units[$stock_unit_name]))) {
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

                    if (!empty($upsert_data1)) {
                        CountingProduct::upsert($upsert_data1, ['id']);
                    }

                    if (!empty($upsert_data2)) {
                        Product::upsert($upsert_data2, ['id']);
                    }
                }
            });

            return ['id' => $counting->id, 'code' => $counting->code];
        } catch (\Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }



}