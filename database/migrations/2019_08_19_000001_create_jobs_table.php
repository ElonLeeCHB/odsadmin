<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id(); // 主鍵 id
            $table->string('queue'); // 隊列名稱
            $table->text('payload'); // 任務的 payload，這是一個 JSON 格式的欄位，儲存任務的數據
            $table->unsignedInteger('attempts')->default(0); // 已嘗試處理的次數
            $table->timestamp('reserved_at')->nullable(); // 任務被鎖定處理的時間
            $table->timestamp('available_at'); // 任務可處理的時間
            $table->timestamp('created_at')->nullable(); // 任務創建時間
            $table->timestamp('updated_at')->nullable(); // 任務更新時間
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jobs');
    }
};
