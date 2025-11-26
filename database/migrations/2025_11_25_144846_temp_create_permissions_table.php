<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * 臨時測試用 Migration - 只建立 permissions 表
 *
 * 用途：測試 permissions 表結構及 parent_id 外鍵
 * 測試完成後請刪除此檔案
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('permissions');
        Schema::create('permissions', function (Blueprint $table) {
            //$table->engine('InnoDB');
            $table->bigIncrements('id'); // permission id
            $table->unsignedBigInteger('parent_id')->nullable(); // 父層 ID，NULL 為頂層
            $table->string('name');       // For MyISAM use string('name', 225); // (or 166 for InnoDB with Redundant/Compact row format)
            $table->string('guard_name'); // For MyISAM use string('guard_name', 25);
            $table->string('title', 50)->nullable(); // 顯示名稱 (用此欄位是因為 spatie permission 的 name 使用上偏向代號的概念。因此新增 title 欄位來存放顯示名稱)
            $table->text('description')->nullable(); // 權限說明
            $table->string('icon', 50)->nullable(); // 圖示 class (FontAwesome)
            $table->integer('sort_order')->default(0); // 排序，數字越小越前面
            $table->enum('type', ['menu', 'action'])->default('menu'); // 類型：menu=顯示在選單, action=功能權限
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
            $table->foreign('parent_id')->references('id')->on('permissions')->onDelete('cascade');
        });
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
