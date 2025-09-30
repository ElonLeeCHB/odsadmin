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
        Schema::connection('sysdata')->create('monthly_operation_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->comment('年份');
            $table->unsignedTinyInteger('month')->comment('月份');
            $table->decimal('order_total_amount', 15, 2)->default(0)->comment('訂單總金額');
            $table->unsignedInteger('order_count')->default(0)->comment('訂單數量');
            $table->unsignedInteger('order_customer_count')->default(0)->comment('訂單客戶數量');
            $table->unsignedInteger('new_customer_count')->default(0)->comment('新客戶數量');
            $table->decimal('purchase_total_amount', 15, 2)->default(0)->comment('進貨總金額');
            $table->unsignedInteger('supplier_count')->default(0)->comment('廠商數量');
            $table->timestamps();

            $table->unique(['year', 'month'], 'uk_year_month');
            $table->index(['year', 'month'], 'idx_year_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sysdata')->dropIfExists('monthly_operation_reports');
    }
};
