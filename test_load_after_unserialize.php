<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Sale\Order;
use App\Helpers\Classes\DataHelper;

$order_id = 13314;

echo "=== 測試 load() 對反序列化 Model 的影響 ===\n\n";

// 1. 查詢並序列化
echo "1. 查詢訂單並序列化...\n";
$order_fresh = Order::with(['orderProducts' => function($qry) {
    $qry->with('orderProductOptions');
}])->find($order_id);

echo "   - orderProducts 數量: " . $order_fresh->orderProducts->count() . "\n";
echo "   - 已載入的關聯: " . implode(', ', array_keys($order_fresh->getRelations())) . "\n\n";

// 2. 序列化並反序列化
echo "2. 序列化並反序列化...\n";
$serialized = serialize($order_fresh);
$order = unserialize($serialized);

echo "   - orderProducts 數量: " . $order->orderProducts->count() . "\n";
echo "   - 已載入的關聯: " . implode(', ', array_keys($order->getRelations())) . "\n";
echo "   - relationLoaded('orderProducts'): " . ($order->relationLoaded('orderProducts') ? 'true' : 'false') . "\n\n";

// 3. 呼叫 load('customer') 之前先監控
echo "3. 準備呼叫 load('customer:id,comment')...\n\n";

// 4. 呼叫 load() 並監控 SQL
echo "4. 呼叫 load('customer:id,comment') 並監控 SQL...\n";
\DB::enableQueryLog();

$order->load('customer:id,comment');

$queries = \DB::getQueryLog();
echo "   - SQL 查詢數量: " . count($queries) . "\n";
if (count($queries) > 0) {
    foreach ($queries as $idx => $query) {
        echo "   [" . ($idx + 1) . "] " . $query['query'] . "\n";
    }
}
echo "\n";

// 5. 檢查 load() 後的狀態
echo "5. load() 後檢查狀態...\n";
echo "   - orderProducts 數量: " . $order->orderProducts->count() . "\n";
echo "   - 已載入的關聯: " . implode(', ', array_keys($order->getRelations())) . "\n";
echo "   - relationLoaded('orderProducts'): " . ($order->relationLoaded('orderProducts') ? 'true' : 'false') . "\n";
echo "   - relationLoaded('customer'): " . ($order->relationLoaded('customer') ? 'true' : 'false') . "\n\n";

// 6. 呼叫 toArray() 並監控
echo "6. 呼叫 toArray() 並監控 SQL...\n";
\DB::flushQueryLog();
\DB::enableQueryLog();

$array_result = $order->toArray();

$queries2 = \DB::getQueryLog();
echo "   - SQL 查詢數量: " . count($queries2) . "\n";

if (count($queries2) > 0) {
    echo "\n   [重要] toArray() 觸發了以下 SQL 查詢:\n";
    foreach ($queries2 as $idx => $query) {
        echo "   [" . ($idx + 1) . "] " . $query['query'] . "\n";
        if (!empty($query['bindings'])) {
            echo "       Bindings: " . json_encode($query['bindings']) . "\n";
        }
    }
} else {
    echo "   - 沒有觸發 SQL 查詢\n";
}

echo "\n   - toArray() 中的 order_products 數量: " . count($array_result['order_products']) . "\n";
echo "   - toArray() 中的 customer: " . (isset($array_result['customer']) ? 'exists' : 'not exists') . "\n";

echo "\n=== 測試完成 ===\n";
