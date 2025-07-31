<?php

namespace App\Domains\Admin\Services\Inventory;

use Illuminate\Support\Facades\DB;
use App\Helpers\Classes\UnitConverter;
use App\Services\Service;
use App\Models\Common\TermRelation;
use App\Models\Catalog\ProductOption;
use App\Models\Catalog\ProductOptionValue;
use App\Repositories\Eloquent\Catalog\ProductRepository;
use App\Repositories\Eloquent\Catalog\ProductUnitRepository;
use App\Repositories\Eloquent\Inventory\UnitRepository;
use Carbon\Carbon;
use App\Helpers\Classes\DataHelper;

class ProductService extends Service
{
    public $modelName = "\App\Models\Catalog\Product";

    public function __construct(ProductRepository $ProductRepository, private ProductUnitRepository $ProductUnitRepository, private UnitRepository $UnitRepository)
    {
        $this->repository = $ProductRepository;
    }


    public function saveProduct($post_data, $debug = 0)
    {
        DB:: beginTransaction();

        try {

            $product_id = $post_data['product_id'] ?? $post_data['id'] ?? null;

            $result = $this->findIdOrFailOrNew($product_id);

            if(!empty($result['error'])){
                return response(json_encode($result))->header('Content-Type','application/json');
            }

            $product = $result['data'];

            $stock_unit_code = '';

            // 若庫存單位已存在資料庫則不改
            if(!empty($product->stock_unit_code)){
                unset($post_data['stock_unit_code']);

                $stock_unit_code = $product->stock_unit_code;
            }
            // 新增庫存單位
            else if(empty($product->stock_unit_code) && !empty($post_data['stock_unit_code'])){
                $stock_unit_code = $post_data['stock_unit_code'];
            }
            if(!empty($stock_unit_code) && !empty($post_data['usage_unit_code'])){
                // 庫存單位 = 用量單位
                if($stock_unit_code == $post_data['usage_unit_code']){
                    $usage_factor = 1;
                }
                //換算用量
                else{

                    echo "<pre>",print_r($post_data['usage_unit_code'],true),"</pre>";
                    $usage_factor = UnitConverter::build()->qty(1)
                                        ->from($post_data['usage_unit_code'])
                                        ->to($stock_unit_code)
                                        ->product($post_data['product_id'])
                                        ->get();
echo "<pre>",print_r(999,true),"</pre>";exit;
                    if(!empty($usage_factor['error'])){
                        throw new \Exception($usage_factor['error']);

                    }
                }
            }

            if(!empty($post_data['stock_price']) && !empty($usage_factor)){
                $post_data['usage_price'] = $post_data['stock_price'] * $usage_factor;
            }else{
                $post_data['usage_price'] = 0;
            }

            $result = $this->saveRow($product_id, $post_data);

            if(!empty($result['error'])){
                throw new \Exception($result['error']);
            }

            $product_id = $result['id'];

            $result = $this->findIdOrFailOrNew($product_id);

            if(!empty($result['error'])){
                return response(json_encode($result))->header('Content-Type','application/json');
            }

            $product = $result['data'];

            // 商品單位表 product_units
            if(!empty($post_data['product_units'])){
                $upsert_data = [];
                foreach ($post_data['product_units'] as $product_unit) {

                    if(empty($product_unit['source_quantity']) || empty($product_unit['source_unit_code']) || empty($product_unit['destination_quantity']) || empty($product_unit['destination_unit_code'])){
                       continue;
                    }

                    $upsert_data[] = [
                        'id' => $product_unit['id'] ?? null,
                        'product_id' => $product->id,
                        'source_quantity' => $product_unit['source_quantity'],
                        'source_unit_code' => $product_unit['source_unit_code'],
                        'destination_unit_code' => $product_unit['destination_unit_code'],
                        'destination_quantity' => $product_unit['destination_quantity'],
                        'purchase_unit_status' => $product_unit['purchase_unit_status'],
                        'factor' => $product_unit['destination_quantity'] / $product_unit['source_quantity'] ,
                    ];
                }

                $this->ProductUnitRepository->newModel()->where('product_id', $product->id)->delete();
                if(!empty($upsert_data)){
                    $this->ProductUnitRepository->newModel()->upsert($upsert_data, ['id']);
                }
            }

            DB::commit();
            DB::commit();

            return ['id' => $product->id];

        } catch (\Exception $ex) {
            DB::rollBack();
            $result['error'] = 'Error code: ' . $ex->getCode() . ', Message: ' . $ex->getMessage();
            return $result;
        }
    }
}

