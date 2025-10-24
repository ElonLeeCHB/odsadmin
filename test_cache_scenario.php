<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Sale\Order;
use App\Helpers\Classes\DataHelper;

$order_id = 13314;

echo "=== 測試實際快取情境 (Order ID: $order_id) ===\n\n";

// 模擬 getOrderByIdOrCode 的邏輯
$cache_key = 'cache/sale/order/id-' . $order_id . '.serialized.txt';

// 1. 清除快取
echo "1. 清除現有快取...\n";
DataHelper::deleteDataFromStorage($cache_key);
echo "   - 快取已清除\n\n";

// 2. 第一次查詢（會寫入快取）
echo "2. 第一次查詢（建立快取）...\n";
\DB::enableQueryLog();

$order = DataHelper::remember($cache_key, 60*60*24, 'serialize', function() use ($order_id) {
    $query = Order::query();
    $query->where('id', $order_id);

    $query->with(['orderProducts' => function($qry) {
        $qry->with('orderProductOptions');
    }]);

    $order = $query->first();
    return $order;
});

$queries1 = \DB::getQueryLog();
echo "   - SQL 查詢數量: " . count($queries1) . "\n";
echo "   - orderProducts 數量: " . $order->orderProducts->count() . "\n\n";

// 3. 模擬更新資料庫（但不清除快取）
echo "3. 更新資料庫（模擬新增一筆 orderProduct）...\n";
echo "   [假設在這裡資料庫被其他程序更新了]\n\n";

// 4. 第二次查詢（從快取讀取）
echo "4. 第二次查詢（從快取讀取）...\n";
\DB::flushQueryLog();
\DB::enableQueryLog();

$order_cached = DataHelper::remember($cache_key, 60*60*24, 'serialize', function() use ($order_id) {
    // 這個 callback 不應該被執行，因為快取存在
    echo "   [警告] Callback 被執行了！這不應該發生\n";
    return Order::with(['orderProducts'])->find($order_id);
});

$queries2 = \DB::getQueryLog();
echo "   - SQL 查詢數量: " . count($queries2) . "\n";
echo "   - orderProducts 數量: " . $order_cached->orderProducts->count() . "\n";
echo "   - 關聯已載入: " . ($order_cached->relationLoaded('orderProducts') ? 'Yes' : 'No') . "\n\n";

// 5. 呼叫 toArray() 並監控查詢
echo "5. 呼叫 toArray() 並監控查詢...\n";
\DB::flushQueryLog();
\DB::enableQueryLog();

$array_result = $order_cached->toArray();

$queries3 = \DB::getQueryLog();
echo "   - SQL 查詢數量: " . count($queries3) . "\n";

if (count($queries3) > 0) {
    echo "\n   [重要] toArray() 觸發了 SQL 查詢:\n";
    foreach ($queries3 as $idx => $query) {
        echo "   [" . ($idx + 1) . "] " . $query['query'] . "\n";
        if (!empty($query['bindings'])) {
            echo "       Bindings: " . json_encode($query['bindings']) . "\n";
        }
    }
} else {
    echo "   - 沒有執行 SQL 查詢\n";
}

echo "\n   - toArray() 結果的 order_products 數量: " . count($array_result['order_products']) . "\n";

// 6. 檢查 attributes 和 relations
echo "\n6. 檢查 Model 的內部狀態...\n";
echo "   - getAttributes() 的 id: " . $order_cached->getAttributes()['id'] . "\n";
echo "   - 已載入的關聯: " . implode(', ', array_keys($order_cached->getRelations())) . "\n";

echo "\n=== 測試完成 ===\n";
