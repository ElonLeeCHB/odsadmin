<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Sale\Order;
use App\Helpers\Classes\DataHelper;
use App\Domains\ApiPosV2\Services\Sale\OrderService;

$order_id = 13314;

echo "=== 完整模擬實際流程 (Order ID: $order_id) ===\n\n";

// 1. 直接從資料庫查詢當前狀態（作為基準）
echo "1. 從資料庫查詢當前實際資料（基準）...\n";
$order_real = Order::with(['orderProducts' => function($qry) {
    $qry->with('orderProductOptions');
}])->find($order_id);

$real_products_data = [];
foreach ($order_real->orderProducts as $product) {
    $real_products_data[] = [
        'id' => $product->id,
        'name' => $product->name,
        'quantity' => $product->quantity,
        'updated_at' => $product->updated_at->format('Y-m-d H:i:s'),
    ];
}

echo "   當前資料庫中的 orderProducts:\n";
foreach ($real_products_data as $p) {
    echo "   - ID: {$p['id']}, Name: {$p['name']}, Qty: {$p['quantity']}, Updated: {$p['updated_at']}\n";
}
echo "\n";

// 2. 使用 OrderService 獲取（會使用快取）
echo "2. 使用 OrderService->getOrderByIdOrCode() 獲取訂單...\n";
$orderService = new OrderService(new \App\Repositories\Eloquent\Sale\OrderRepository());

\DB::enableQueryLog();
$order_from_service = $orderService->getOrderByIdOrCode($order_id, 'id');
$queries = \DB::getQueryLog();

echo "   - 執行的 SQL 查詢數量: " . count($queries) . "\n";
if (count($queries) > 0) {
    echo "   執行的查詢:\n";
    foreach ($queries as $idx => $query) {
        echo "   [" . ($idx + 1) . "] " . substr($query['query'], 0, 80) . "...\n";
    }
}

$cached_products_data = [];
foreach ($order_from_service->orderProducts as $product) {
    $cached_products_data[] = [
        'id' => $product->id,
        'name' => $product->name,
        'quantity' => $product->quantity,
        'updated_at' => $product->updated_at->format('Y-m-d H:i:s'),
    ];
}

echo "\n   快取中的 orderProducts:\n";
foreach ($cached_products_data as $p) {
    echo "   - ID: {$p['id']}, Name: {$p['name']}, Qty: {$p['quantity']}, Updated: {$p['updated_at']}\n";
}
echo "\n";

// 3. 比較
echo "3. 比較資料庫與快取...\n";
if (json_encode($real_products_data) === json_encode($cached_products_data)) {
    echo "   ✓ 資料一致\n\n";
} else {
    echo "   ✗ 資料不一致！\n";
    echo "   差異:\n";
    for ($i = 0; $i < count($real_products_data); $i++) {
        if (isset($cached_products_data[$i])) {
            if ($real_products_data[$i]['updated_at'] !== $cached_products_data[$i]['updated_at']) {
                echo "   - Product ID {$real_products_data[$i]['id']}: updated_at 不同\n";
                echo "     資料庫: {$real_products_data[$i]['updated_at']}\n";
                echo "     快取: {$cached_products_data[$i]['updated_at']}\n";
            }
            if ($real_products_data[$i]['quantity'] !== $cached_products_data[$i]['quantity']) {
                echo "   - Product ID {$real_products_data[$i]['id']}: quantity 不同\n";
                echo "     資料庫: {$real_products_data[$i]['quantity']}\n";
                echo "     快取: {$cached_products_data[$i]['quantity']}\n";
            }
        }
    }
    if (count($real_products_data) !== count($cached_products_data)) {
        echo "   - 數量不同: 資料庫 " . count($real_products_data) . " vs 快取 " . count($cached_products_data) . "\n";
    }
    echo "\n";
}

// 4. 呼叫 toArray()
echo "4. 呼叫 toArray() 並監控 SQL...\n";
\DB::flushQueryLog();
\DB::enableQueryLog();

$array_result = $order_from_service->toArray();

$queries2 = \DB::getQueryLog();
echo "   - 執行的 SQL 查詢數量: " . count($queries2) . "\n";

if (count($queries2) > 0) {
    echo "\n   [重要] toArray() 觸發了 SQL 查詢:\n";
    foreach ($queries2 as $idx => $query) {
        echo "   [" . ($idx + 1) . "] " . substr($query['query'], 0, 100) . "...\n";
    }
}

$toarray_products_data = [];
foreach ($array_result['order_products'] as $product) {
    $toarray_products_data[] = [
        'id' => $product['id'],
        'name' => $product['name'],
        'quantity' => $product['quantity'],
        'updated_at' => $product['updated_at'],
    ];
}

echo "\n   toArray() 結果中的 orderProducts:\n";
foreach ($toarray_products_data as $p) {
    echo "   - ID: {$p['id']}, Name: {$p['name']}, Qty: {$p['quantity']}, Updated: {$p['updated_at']}\n";
}
echo "\n";

// 5. 最終比較
echo "5. 最終比較...\n";
if (json_encode($real_products_data) === json_encode($toarray_products_data)) {
    echo "   ✓ toArray() 的資料與資料庫一致\n";
} else {
    echo "   ✗ toArray() 的資料與資料庫不一致\n";
}

if (json_encode($cached_products_data) === json_encode($toarray_products_data)) {
    echo "   ✓ toArray() 的資料與快取一致（沒有重新查詢）\n";
} else {
    echo "   ✗ toArray() 的資料與快取不一致（可能重新查詢了）\n";
}

echo "\n=== 測試完成 ===\n";
