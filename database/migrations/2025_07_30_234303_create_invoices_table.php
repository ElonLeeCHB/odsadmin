<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id(); // 主鍵
            $table->unsignedBigInteger('order_group_id')->nullable(); // 若有值，invoice_order_maps 就不會有對應紀錄
            $table->string('invoice_number')->unique(); // 發票號碼
            $table->date('invoice_date'); // 發票日期
            $table->string('buyer_name')->nullable(); // 買受人名稱
            $table->string('seller_name')->nullable();  // 賣方名稱
            $table->string('tax_id_number', 20)->nullable(); // 統一編號（若為 null，表示無統編）

            $table->unsignedBigInteger('customer_id')->nullable(); // 所屬使用者
            $table->enum('tax_type', ['taxable', 'exempt', 'zero_rate'])->default('taxable')->nullable(); // 課稅類別
            $table->integer('tax_amount')->default(0); // 稅額：整數，四捨五入後的稅
            $table->integer('total_amount'); // 總金額：整數，含稅
            $table->enum('status', ['unpaid', 'paid', 'canceled'])->default('unpaid')->nullable();

            $table->unsignedBigInteger('creator_id')->nullable();
            $table->unsignedBigInteger('modifier_id')->nullable();
            $table->timestamps();

            // 索引（可加速查詢）
            $table->index('order_group_id');
            $table->index('tax_id_number');
            $table->index('customer_id');
        });

        Schema::create('invoice_order_maps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('order_id');
            $table->unique(['invoice_id', 'order_id']); // 不可重複對應
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('name');
            $table->boolean('is_tax_included')->default(true); // 此項目是否為含稅價，方便稅額推算
            $table->decimal('quantity', 12, 3)->default(1);
            $table->decimal('price', 12, 3); // 單價：小數三位，若含稅，price 為含稅價；若未稅，為未稅價
            $table->decimal('subtotal', 12, 3); // 小計：price * quantity，小數三位。欄位名稱 subtotal 已調研過，比 amount 更符合會計用語，並且不加底線。
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoice_order_maps');
        Schema::dropIfExists('invoices');
    }
};
