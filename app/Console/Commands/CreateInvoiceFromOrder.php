<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/*
只能開發環境使用此指令！
使用訂單資料，建立發票資料。測試用，只能用測試單，請小心使用！

訂單ID 13314 是測試訂單
php artisan invoice:create-from-order --id=13314
*/

class CreateInvoiceFromOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoice:create-from-order
                            {--id= : 订单 ID}
                            {--code= : 订单编号}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '从订单创建发票（测试用）- 必须提供 --id 或 --code 其中一个';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orderId = $this->option('id');
        $orderCode = $this->option('code');

        // 验证参数
        if (empty($orderId) && empty($orderCode)) {
            $this->error('❌ 错误：必须提供 --id 或 --code 参数');
            $this->info('使用方式：');
            $this->line('  php artisan invoice:create-from-order --id=13314');
            $this->line('  php artisan invoice:create-from-order --code=25100034');
            return 1;
        }

        if (!empty($orderId) && !empty($orderCode)) {
            $this->error('❌ 错误：--id 和 --code 只能选择一个');
            return 1;
        }

        // 查询订单
        $query = DB::table('orders');
        if (!empty($orderId)) {
            $query->where('id', $orderId);
            $searchKey = "ID {$orderId}";
        } else {
            $query->where('code', $orderCode);
            $searchKey = "编号 {$orderCode}";
        }

        $order = $query->first();

        if (!$order) {
            $this->error("❌ 找不到订单：{$searchKey}");
            return 1;
        }

        $orderId = $order->id;

        // 显示订单信息
        $this->info("✅ 找到订单：ID {$orderId}");
        $this->line("订单编号：{$order->code}");
        $this->line("客户姓名：{$order->personal_name}");
        $this->line("订单日期：{$order->order_date}");
        $this->newLine();

        // 查询订单商品
        $orderProducts = DB::table('order_products')->where('order_id', $orderId)->get();
        $this->info("商品数量：" . $orderProducts->count());
        foreach ($orderProducts as $product) {
            $this->line("  - {$product->name} x {$product->quantity} @ {$product->price}");
        }
        $this->newLine();

        // 查询订单金额项目
        $orderTotals = DB::table('order_totals')->where('order_id', $orderId)->get();
        $this->info("金额项目：");
        $totalAmount = 0;
        $taxAmount = 0;
        $discountAmount = 0;
        $couponAmount = 0;
        foreach ($orderTotals as $total) {
            $this->line("  - {$total->code}: {$total->value}");
            if ($total->code === 'total') {
                $totalAmount = (int)$total->value;
            }
            if ($total->code === 'tax') {
                $taxAmount = (int)$total->value;
            }
            if ($total->code === 'discount') {
                $discountAmount = (int)$total->value;
            }
            if ($total->code === 'coupon') {
                $couponAmount = (int)$total->value;
            }
        }

        // 如果没有从 order_totals 取得总金额，使用 orders.payment_total
        if ($totalAmount === 0) {
            $totalAmount = (int)$order->payment_total;
            $this->line("  使用 orders.payment_total: {$totalAmount}");
        }

        $this->line("总金额：{$totalAmount}");
        $this->line("税额：{$taxAmount}");
        if ($discountAmount > 0) {
            $this->line("折扣：-{$discountAmount}");
        }
        if ($couponAmount > 0) {
            $this->line("优惠券：-{$couponAmount}");
        }
        $this->newLine();

        // 确认创建
        if (!$this->confirm('是否创建发票？', true)) {
            $this->info('已取消');
            return 0;
        }

        // 开始创建发票
        DB::beginTransaction();

        try {
            // 创建发票主记录（使用唯一的临时发票号码）
            $tempInvoiceNumber = 'PENDING_' . $orderId . '_' . time();

            $invoiceId = DB::table('invoices')->insertGetId([
                'order_group_id' => null,
                'invoice_number' => $tempInvoiceNumber,
                'invoice_date' => date('Y-m-d'),
                'buyer_name' => $order->personal_name,
                'seller_name' => '賣方公司名稱',
                'tax_id_number' => null, // B2C 不填统编
                'customer_id' => $order->customer_id ?? null,
                'tax_type' => 'taxable',
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'status' => 'unpaid',
                'carrier_type' => 'none',
                'content' => '商品銷售',
                'email' => $order->email ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->info("✅ 创建发票：ID {$invoiceId}");

            // 创建发票项目
            $itemCount = 0;

            // 1. 先加入商品項目
            foreach ($orderProducts as $product) {
                DB::table('invoice_items')->insert([
                    'invoice_id' => $invoiceId,
                    'sort_order' => $itemCount,
                    'name' => $product->name,
                    'quantity' => $product->quantity,
                    'price' => $product->price,
                    'subtotal' => $product->price * $product->quantity,
                    'remark' => null,
                    'item_tax_type' => null,
                ]);
                $itemCount++;
            }

            // 2. 加入折扣項目（負數）
            if ($discountAmount > 0) {
                DB::table('invoice_items')->insert([
                    'invoice_id' => $invoiceId,
                    'sort_order' => $itemCount,
                    'name' => '優惠折扣',
                    'quantity' => 1,
                    'price' => -$discountAmount,
                    'subtotal' => -$discountAmount,
                    'remark' => '訂單折扣',
                    'item_tax_type' => null,
                ]);
                $itemCount++;
            }

            // 3. 加入優惠券項目（負數）
            if ($couponAmount > 0) {
                DB::table('invoice_items')->insert([
                    'invoice_id' => $invoiceId,
                    'sort_order' => $itemCount,
                    'name' => '優惠券折抵',
                    'quantity' => 1,
                    'price' => -$couponAmount,
                    'subtotal' => -$couponAmount,
                    'remark' => '優惠券使用',
                    'item_tax_type' => null,
                ]);
                $itemCount++;
            }

            $this->info("✅ 创建发票项目：{$itemCount} 项");

            // 创建订单发票关联
            DB::table('invoice_order_maps')->insert([
                'invoice_id' => $invoiceId,
                'order_id' => $orderId,
            ]);

            $this->info("✅ 创建订单发票关联");

            DB::commit();

            $this->newLine();
            $this->info("🎉 发票创建成功！");
            $this->line("发票 ID：{$invoiceId}");
            $this->line("订单 ID：{$orderId}");
            $this->newLine();

            // 显示测试参数
            $this->info("📋 可以使用以下参数测试开立发票：");
            $this->line(json_encode([
                'invoice_id' => $invoiceId,
                'order_id' => $orderId,
                'order_code' => $order->code,
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            $this->newLine();
            $this->info("🔗 Postman 测试 URL：");
            $this->line("POST http://ods.dtstw.test/api/posv2/sales/invoice-issue/giveme/test/issue");

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ 错误：" . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
