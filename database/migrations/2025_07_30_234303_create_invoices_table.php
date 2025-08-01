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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id(); // 主鍵
            $table->unsignedBigInteger('order_group_id')->nullable(); // order_groups.id 如果有值，會同時記錄在 orders.order_group_id, 但 invoice_order_maps 就不會有記錄。
            $table->string('invoice_number')->unique(); // 發票號碼
            $table->date('invoice_date'); // 發票日期
            $table->string('buyer_name')->nullable(); // 買受人
            $table->string('seller_name')->nullable();  // 賣方名稱
            $table->string('tax_id_number', 20)->nullable(); // 統一編號

            $table->unsignedBigInteger('user_id')->nullable(); // 所屬使用者
            $table->enum('tax_type', ['taxable', 'exempt', 'zero_rate'])->default('taxable')->nullable();
            $table->decimal('tax_amount', 12, 2)->default(0); // 稅額
            $table->integer('total_amount'); // 總金額
            $table->enum('status', ['unpaid', 'paid', 'canceled'])->default('unpaid')->nullable();

            $table->unsignedBigInteger('creator_id')->nullable();
            $table->unsignedBigInteger('modifier_id')->nullable();
            $table->timestamps(); // created_at, updated_at
        });

        Schema::create('invoice_order_maps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('order_id');

            // 外鍵關聯（視需要開啟 onDelete cascade）
            // $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            // $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');

            // 防止重複（同一張發票不能重複對應同一張訂單）
            $table->unique(['invoice_id', 'order_id']);;
        });

        // 如果應稅，通常稅額在主檔 invoices ，品項不須處理稅額。若應稅外加，稅額在主檔 invoices，若應稅內含，則品項單價 price 就是含稅價。
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('invoice_id');
            $table->string('name');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('price');
            $table->decimal('subtotal', 12, 2);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoice_order_maps');
        Schema::dropIfExists('invoices');
    }
};
