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
        Schema::create('purchasing_orders', function (Blueprint $table) {
            $table->id();
            $table->string('code',10)->index(); //編號
            $table->unsignedInteger('location_id')->index(); //門市代號
            $table->datetime('purchasing_date')->index(); //採購日
            $table->datetime('receiving_date')->nullable(); //收貨日
            $table->unsignedInteger('supplier_id')->index(); //廠商代號
            $table->string('supplier_name',100)->index(); //廠商名稱
            $table->string('tax_id_num',8)->index(); //統一編號
            $table->decimal('before_tax', $precision = 13, $scale = 4)->nullable(); //稅前金額
            $table->decimal('tax', $precision = 10, $scale = 4)->nullable(); //稅額
            $table->decimal('total', $precision = 13, $scale = 4)->nullable(); //稅後金額
            $table->string('status_code',2)->nullable(); //狀態碼 terms
            $table->timestamps();
        });

        Schema::create('purchasing_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('purchasing_order_id')->index();
            $table->unsignedTinyInteger('sort_order')->default(255);

            $table->unsignedInteger('product_id',); //
            $table->string('product_name')->nullable(); //料件名稱
            $table->string('product_specification')->nullable(); //料件規格

            $table->string('supplier_product_code')->nullable(); //廠商料件編號
            $table->string('supplier_product_name')->nullable(); //廠商料件名稱
            $table->string('supplier_product_specification')->nullable(); //廠商料件規格

            $table->unsignedInteger('purchasing_unit_id',)->nullable(); //採購單位
            $table->string('purchasing_unit_name',)->nullable(); //採購單位名稱
            $table->decimal('purchasing_quantity', $precision = 13, $scale = 4)->nullable(); //採購數量
            
            $table->unsignedInteger('stock_unit_id',)->nullable(); //庫存單位
            $table->string('stock_unit_name',)->nullable(); //庫存單位名稱
            $table->decimal('stock_quantity', $precision = 13, $scale = 4)->nullable(); //庫存數量

            $table->decimal('product_total', $precision = 13, $scale = 4)->nullable(); //稅後金額

            $table->string('comment')->nullable(); //備註
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchasing_products');
        Schema::dropIfExists('purchasing_orders');
    }
};
