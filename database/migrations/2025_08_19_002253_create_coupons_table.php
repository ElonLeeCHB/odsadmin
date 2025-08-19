<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // coupons (定義券的種類)
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 一百元抵用券
            $table->decimal('threshold', 10, 2)->default(0); // 消費門檻
            $table->enum('discount_type', ['fixed', 'percent']); //fixed = 固定金額, percent = 百分比
            $table->enum('type', ['standard', 'manual']);
            $table->decimal('discount_value', 10, 2);// 金額或百分比 
            $table->timestamps();
        });

        // user_coupons (紀錄每個用戶擁有的券)
        Schema::create('user_coupons', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->comment('使用者 users.id');
            $table->unsignedInteger('coupon_id')->comment('用戶擁有的券ID coupons.id');
            $table->unsignedInteger('quantity')->comment('擁有幾張');
            $table->timestamps();

            $table->unique(['user_id', 'coupon_id']); // 一個人同種類券數量統一管理
        });

        // order_coupons (訂單使用的券)
        Schema::create('order_coupons', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order_id')->comment('訂單 orders.id');
            $table->unsignedInteger('coupon_id')->comment('優惠券 cooupons.id');
            $table->unsignedInteger('quantity')->default(1); // 用了幾張
            $table->decimal('subtotal', 10, 2); // 折抵總金額
            $table->timestamps();

            $table->unique(['order_id', 'coupon_id']); // 避免一張訂單重複紀錄同一種券
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_coupons');
        Schema::dropIfExists('user_coupons');
        Schema::dropIfExists('coupons');
    }
};
