<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoice_carrier_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // 載具代碼（對應 invoices.carrier_type）
            $table->string('name', 50); // 中文名稱
            $table->string('description')->nullable(); // 說明
            $table->string('giveme_param', 20)->nullable(); // 對應 Giveme API 參數名稱（phone/orderCode）
            $table->unsignedTinyInteger('sort_order')->default(0); // 排序
            $table->boolean('is_active')->default(true); // 是否啟用
            $table->timestamps();

            $table->index('code');
            $table->index('sort_order');
        });

        // 插入預設載具類型資料
        DB::table('invoice_carrier_types')->insert([
            [
                'code' => 'none',
                'name' => '無載具（列印紙本）',
                'description' => '列印紙本發票，不使用載具',
                'giveme_param' => null,
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'phone_barcode',
                'name' => '手機條碼',
                'description' => '財政部手機條碼載具，格式：/XXXXXXX（第1碼為/，其餘7碼為數字、大寫英文或+-.）',
                'giveme_param' => 'phone',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'citizen_cert',
                'name' => '自然人憑證',
                'description' => '自然人憑證條碼',
                'giveme_param' => 'orderCode',
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'member_card',
                'name' => '會員卡',
                'description' => '會員卡號載具',
                'giveme_param' => 'orderCode',
                'sort_order' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'credit_card',
                'name' => '信用卡載具',
                'description' => '信用卡載具號碼',
                'giveme_param' => 'orderCode',
                'sort_order' => 5,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'icash',
                'name' => 'icash',
                'description' => 'icash 卡號',
                'giveme_param' => 'orderCode',
                'sort_order' => 6,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'easycard',
                'name' => '悠遊卡',
                'description' => '悠遊卡卡號',
                'giveme_param' => 'orderCode',
                'sort_order' => 7,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ipass',
                'name' => '一卡通',
                'description' => '一卡通卡號',
                'giveme_param' => 'orderCode',
                'sort_order' => 8,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'email',
                'name' => '電子郵件載具',
                'description' => '電子郵件載具（跨境電商）',
                'giveme_param' => 'orderCode',
                'sort_order' => 9,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'donation',
                'name' => '捐贈',
                'description' => '發票捐贈給愛心碼',
                'giveme_param' => 'donationCode',
                'sort_order' => 10,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_carrier_types');
    }
};
