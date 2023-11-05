<?php

namespace App\Domains\Admin\Services\Inventory;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Models\Common\TermRelation;
use App\Models\Catalog\ProductOption;
use App\Models\Catalog\ProductOptionValue;
use App\Repositories\Eloquent\Catalog\ProductRepository;
use App\Repositories\Eloquent\Catalog\ProductUnitRepository;
use Carbon\Carbon;

class ProductService extends Service
{
    public $modelName = "\App\Models\Catalog\Product";

    public function __construct(ProductRepository $ProductRepository, private ProductUnitRepository $ProductUnitRepository)
    {
        $this->repository = $ProductRepository;
    }


    public function saveProduct($post_data, $debug = 0)
    {
        DB:: beginTransaction();
        try {

            $product_id = $post_data['product_id'] ?? $post_data['id'] ?? null;

            // 若庫存單位已存在則不改
            // if(!empty($product->stock_unit_code)){
            //     unset($product->stock_unit_code);
            // }
            // if(!empty($post_data['stock_unit_code'])){
            //     unset($post_data['stock_unit_code']);
            // }
            
            $result = $this->saveRow($product_id, $post_data);
           // echo '<pre>', print_r($post_data, 1), "</pre>"; exit;
            if(!empty($result['error'])){
                throw new \Exception($result['error']);
            }

            $product = $this->findIdOrFailOrNew($product_id);

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
                    ];
                }
                
                $this->ProductUnitRepository->newModel()->where('product_id', $product->id)->delete();
                if(!empty($upsert_data)){
                    $this->ProductUnitRepository->newModel()->upsert($upsert_data, ['id']);
                }
            }

            DB::commit();
    
            return ['id' => $product->id];

        } catch (\Exception $ex) {
            DB::rollBack();
            $result['error'] = 'Error code: ' . $ex->getCode() . ', Message: ' . $ex->getMessage();
            return $result;
        }
    }
}

