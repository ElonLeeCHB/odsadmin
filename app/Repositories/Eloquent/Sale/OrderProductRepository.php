<?php

namespace App\Repositories\Eloquent\Sale;

use App\Repositories\Eloquent\Repository;
use App\Models\Sale\OrderProduct;

class OrderProductRepository extends Repository
{
    public $modelName = "\App\Models\Sale\OrderProduct";

    public function createMany($arrOrderProducts, $order_id)
    {
        try {
            $rows = [];

            foreach ($arrOrderProducts as $row) {
                $row = $this->getCommonData($row, $order_id);

                //若使用 insert() 則必須
                $row['created_at'] = now();
                $row['updated_at'] = now();

                $rows[] = $row;
            }
    
            return OrderProduct::insert($rows);

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * 必須包含 order_product_id
     */
    public function upsertMany($arrOrderProducts, $order_id)
    {
        $rows = [];

        foreach ($arrOrderProducts as $row) {
            $row = $this->getCommonData($row, $order_id);

            if(empty($updateColumns)){
                $updateColumns = array_keys($row);
                unset($updateColumns['id']);
                unset($updateColumns['created_at']);
            }

            //更新
            if(!empty($row['order_product_id'])){
                $row['id'] = $row['order_product_id'];
                unset($row['order_product_id']);

                $row['created_at'] = now();
                $row['updated_at'] = now();
            }
            //新增
            else{
                unset($row['id']);
                $row['created_at'] = now();
            }

            $row['order_id'] = $order_id;

            $rows[] = $row;
        }

        return OrderProduct::upsert($rows, ['id'], $updateColumns);
    }

    public function getCommonData(array $data, $order_id)
    {
        return [
            'order_id' => $order_id,
            'sort_order' => $data['sort_order'] ?? 0,
            'product_id' => $data['product_id'],
            'name' => $data['name'],
            'model' => $data['model'] ?? '',
            'main_category_code' => $data['main_category_code'] ?? '',
            'price' => $data['price'] ?? 0,
            'quantity' => $data['quantity'] ?? 0,
            'total' => $data['total'] ?? 0,
            'options_total' => $data['options_total'] ?? 0,
            'final_total' => $data['final_total'] ?? 0,
            'tax' => $data['tax'] ?? 0,
            'comment' => $data['comment'] ?? '',
        ];
    }
}

