<?php

namespace App\Domains\Admin\Services\Inventory;

use Illuminate\Support\Facades\DB;
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

            // 暫時不用。正式上線後要啟用
            // 若庫存單位已存在則不改
            // if(!empty($product->stock_unit_code)){
            //     unset($product->stock_unit_code);
            // }
            // if(!empty($post_data['stock_unit_code'])){
            //     unset($post_data['stock_unit_code']);
            // }

            if(!empty($post_data['stock_unit_code']) && !empty($post_data['usage_unit_code'])){
                if($post_data['stock_unit_code'] == $post_data['usage_unit_code']){
                    $usage_factor = 1;
                }else{
                    $params = [
                        'product_id' => $post_data['product_id'],
                        'from_unit_code' => $post_data['usage_unit_code'],
                        'to_unit_code' => $post_data['stock_unit_code'],
                        'from_quantity' => 1,
                    ];
                    $usage_factor = $this->UnitRepository->calculateQty($params);
    
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

            $result = $this->findIdOrFailOrNew($product_id);

            if(empty($result['error']) && !empty($result['data'])){
                $product = $result['data'];
            }else{
                return response(json_encode($result))->header('Content-Type','application/json');
            }

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

            // save to json cache
            $this->repository->setJsonCache($product->id);

            return ['id' => $product->id];

        } catch (\Exception $ex) {
            DB::rollBack();
            $result['error'] = 'Error code: ' . $ex->getCode() . ', Message: ' . $ex->getMessage();
            return $result;
        }
    }
}

