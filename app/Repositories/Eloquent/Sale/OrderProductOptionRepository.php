<?php

namespace App\Repositories\Eloquent\Sale;

use App\Repositories\Eloquent\Repository;
use App\Models\Sale\OrderProductOption;

class OrderProductOptionRepository extends Repository
{
    public $modelName = "\App\Models\Sale\OrderProductOption";

    public function createMany($arrOrderProductOptions, $order_id, $order_product_id)
    {
        try {
            $rows = [];

            foreach ($arrOrderProductOptions ?? [] as $row) {
                $row = $this->normalizeData($row, $order_id, $order_product_id);

                $row['created_at'] = now();
                $row['updated_at'] = now();

                $rows[] = $row;
            }

            if(!empty($rows)){
                return OrderProductOption::insert($rows);
            }

            return [];

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * 必須包含 order_product_option_id
     */
    public function upsertMany($arrOrderProductOptions, $order_id, $order_product_id)
    {
        $rows = [];

        foreach ($arrOrderProductOptions as $row) {
            $row = $this->normalizeData($row, $order_id, $order_product_id);

            if(empty($updateColumns)){
                $updateColumns = array_keys($row);
                unset($updateColumns['id']);
                unset($updateColumns['created_at']);
            }

            //更新
            if(!empty($row['order_product_option_id'])){
                $row['id'] = $row['order_product_option_id'];
                unset($row['order_product_option_id']);

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

        return OrderProductOption::upsert($rows, ['id'], $updateColumns);
    }

    public function normalizeData(array $data, $order_id, $order_product_id)
    {
        return [
            'order_id' => $order_id,
            'order_product_id' => $order_product_id,
            'product_id' => $data['product_id'] ?? 0,
            'product_option_id' => $data['product_option_id'],
            'product_option_value_id' => $data['product_option_value_id'],
            'parent_product_option_value_id' => $data['parent_product_option_value_id'] ?? 0,
            'name' => $data['name'],
            'value' => $data['value'],
            'type' => $data['type'],
            'quantity' => $data['quantity'] ?? 0,
            'option_id' => $data['option_id'] ?? 0,
            'option_value_id' => $data['option_value_id'] ?? 0,
            'map_product_id' => $data['map_product_id'] ?? 0,
        ];
    }
}

