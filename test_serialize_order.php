<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Sale\Order;
use App\Helpers\Classes\DataHelper;

// 使用指定的訂單 ID
$order_id = 13314;

echo "=== 測試訂單 ID: $order_id ===\n\n";

// 1. 直接從資料庫查詢（fresh query）
echo "1. 從資料庫查詢訂單...\n";
$order_fresh = Order::with(['orderProducts' => function($qry) {
    $qry->with('orderProductOptions');
}])->find($order_id);

if (!$order_fresh) {
    die("找不到訂單 $order_id\n");
}

echo "   - orderProducts 數量: " . $order_fresh->orderProducts->count() . "\n";
echo "   - 關聯已載入: " . ($order_fresh->relationLoaded('orderProducts') ? 'Yes' : 'No') . "\n\n";

// 2. 序列化並反序列化
echo "2. 序列化並反序列化...\n";
$serialized = serialize($order_fresh);
$order_unserialized = unserialize($serialized);

echo "   - orderProducts 數量: " . $order_unserialized->orderProducts->count() . "\n";
echo "   - 關聯已載入: " . ($order_unserialized->relationLoaded('orderProducts') ? 'Yes' : 'No') . "\n";
echo "   - orderProducts 是什麼類型: " . get_class($order_unserialized->orderProducts) . "\n\n";

// 3. 檢查關聯資料的 Connection 狀態
echo "3. 檢查第一個 orderProduct 的狀態...\n";
if ($order_unserialized->orderProducts->count() > 0) {
    $first_product = $order_unserialized->orderProducts->first();
    echo "   - 類型: " . get_class($first_product) . "\n";
    echo "   - ID: " . $first_product->id . "\n";
    echo "   - Name: " . $first_product->name . "\n";

    // 檢查是否有 database connection
    try {
        $connection = $first_product->getConnection();
        echo "   - 有 Database Connection: Yes\n";
        echo "   - Connection Name: " . $connection->getName() . "\n";
    } catch (\Exception $e) {
        echo "   - 有 Database Connection: No - " . $e->getMessage() . "\n";
    }
}

// 4. 呼叫 toArray() 並啟用 Query Log
echo "\n4. 呼叫 toArray() 並監控 SQL 查詢...\n";
\DB::enableQueryLog();

$array_result = $order_unserialized->toArray();

$queries = \DB::getQueryLog();
echo "   - 執行的 SQL 查詢數量: " . count($queries) . "\n";

if (count($queries) > 0) {
    echo "\n   執行的 SQL 查詢:\n";
    foreach ($queries as $idx => $query) {
        echo "   [" . ($idx + 1) . "] " . $query['query'] . "\n";
        if (!empty($query['bindings'])) {
            echo "       Bindings: " . json_encode($query['bindings']) . "\n";
        }
    }
} else {
    echo "   - 沒有執行任何 SQL 查詢（使用快取資料）\n";
}

echo "\n5. orderProducts 在 toArray() 結果中的數量: " . count($array_result['order_products']) . "\n";

echo "\n=== 測試完成 ===\n";
