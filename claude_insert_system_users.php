<?php

/*
這是 claude 寫的一個腳本，用於將所有 is_admin = 1 的用戶插入到 system_users 表中。
保留做為參考。
*/

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// 查詢所有 is_admin = 1 的用戶
$users = \App\Models\User\User::where('is_admin', 1)->get();

$count = 0;
foreach ($users as $user) {
    \App\Models\SystemUser::firstOrCreate(
        ['user_id' => $user->id],
        [
            'user_code' => $user->code,
            'first_access_at' => now(),
            'last_access_at' => now(),
            'access_count' => 1,
        ]
    );
    $count++;
}

echo "Successfully inserted {$count} admin users into system_users table.\n";

// 驗證結果
$systemUsersCount = \App\Models\SystemUser::count();
echo "Total system_users count: {$systemUsersCount}\n";

// 顯示示例數據
echo "\nSample data (first 5 records):\n";
echo str_repeat('-', 100) . "\n";
printf("%-10s %-20s %-20s %-20s %-15s\n", "User ID", "User Code", "Name", "Last Access", "Access Count");
echo str_repeat('-', 100) . "\n";

$samples = \App\Models\SystemUser::with('user')->take(5)->get();
foreach ($samples as $record) {
    printf(
        "%-10d %-20s %-20s %-20s %-15d\n",
        $record->user_id,
        $record->user_code ?? 'NULL',
        mb_substr($record->user->name ?? 'N/A', 0, 20),
        $record->last_access_at ? $record->last_access_at->format('Y-m-d H:i:s') : 'N/A',
        $record->access_count
    );
}
echo str_repeat('-', 100) . "\n";
