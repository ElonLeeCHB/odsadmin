<?php

namespace App\Repositories\Eloquent\Sale;

use App\Repositories\Eloquent\Repository;
use App\Models\Sale\OrderProduct;
use App\Models\Sale\OrderProductOption;

class OrderProductRepository extends Repository
{
    public $modelName = "\App\Models\Sale\OrderProduct";

    public function createMany($arrOrderProducts, $order_id)
    {
        try {
            $rows = [];

            OrderProductOption::where('order_id', $order_id)->delete();
            OrderProduct::where('order_id', $order_id)->delete();

            foreach ($arrOrderProducts as $row) {
                $row = $this->normalizeData($row, $order_id);

                unset($row['id']);
                unset($row['order_product_id']);

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

    public function upsertMany($arrOrderProducts, $order_id)
    {
        $rows = [];

        // OrderProductOption 必須在後續另外處理，這裡先全刪。
        OrderProductOption::where('order_id', $order_id)->delete();
        OrderProduct::where('order_id', $order_id)->delete();

        foreach ($arrOrderProducts as $row) {
            $row = $this->normalizeData($row, $order_id);

            // 要求 id 必須存在。如果前端用了 order_product_id，改為 id
            $row['id'] = $row['id'] ?? $row['order_product_id'] ?? null;
            unset($row['order_product_id']);

            // 如果記錄已存在，需要更新的欄位
            if(empty($updateColumns)){
                $updateColumns = array_keys($row);
                unset($updateColumns['id']);
                unset($updateColumns['created_at']);
            }

            //更新
            if(!empty($row['id'])){
                $row['created_at'] = now();
                $row['updated_at'] = now();
            }
            //新增
            else{
                $row['created_at'] = now();
            }

            $row['order_id'] = $order_id;

            $rows[] = $row;
        }

        return OrderProduct::upsert($rows, ['id'], $updateColumns);
    }

    public function normalizeData(array $data, $order_id)
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

    // public function resortOrderProducts($order_products)
    // {
    //     //整理排序
    //     foreach ($order_products as &$row) {
    //         if (empty($row['sort_order'])) {
    //             $row['sort_order'] = 0;
    //         }
    //     }

    //     usort($order_products, function ($a, $b) {
    //         if ($a['sort_order'] == 0 && $b['sort_order'] == 0) {
    //             return 0; // 若兩者都是 0，保持原順序
    //         }
    //         if ($a['sort_order'] == 0) {
    //             return 1; // $a 的 sort_order 為 0，應排在後面
    //         }
    //         if ($b['sort_order'] == 0) {
    //             return -1; // $b 的 sort_order 為 0，應排在後面
    //         }
    //         return $a['sort_order'] <=> $b['sort_order']; // 非 0 的情況下升冪排序
    //     });

    //     // 給所有 sort_order 為 0 的項目重新編號，從最大的 non-zero sort_order 開始遞增
    //     $sortOrderCounter = count(array_filter($order_products, function ($row) {
    //         return $row['sort_order'] !== 0; // 計算非 0 的項目數量
    //     })) + 1; // 確保從最大的 non-zero sort_order 開始編號

    //     // 重新編號所有 sort_order 為 0 的項目
    //     foreach ($order_products as &$row) {
    //         if ($row['sort_order'] == 0) {
    //             $row['sort_order'] = $sortOrderCounter++; // 重新編號
    //         }
    //     }

    //     // 最後重新索引，讓陣列的索引等於 sort_order
    //     return array_column($order_products, null, 'sort_order');
    // }
}

