<?php

namespace App\Repositories\Eloquent\Sale;

use Illuminate\Support\Facades\DB;
use App\Models\Sale\Invoice;
use App\Models\Sale\InvoiceItem;

/* 將誤差補到最後一筆單價。
    參考綠界
    若有統編，則明細一律使用未稅價，取到小數三位，若還有誤差，補到最後一個品項。發票會顯示稅額。
    若無統編，單價就是含稅價，但資料表仍然要有稅額。

    單身明細要有單價、數量，不需要稅額，稅額計算在發票總額上。所以在有統編的情況下，發票明細的單價由含稅回推未稅金額，會產生小數點。將誤差補到最後一筆單價。
*/


class InvoiceRepository
{
    public static function calculateInvoiceItemPrices(array $invoice, array $invoice_items): array
    {
        $hasTaxId = !empty($invoice['tax_id_num']);
        $taxRate = 0.05;

        $totalAmount = $invoice['total_amount']; // 訂單的付款總額（含稅）

        $totalTaxAmount = round($totalAmount - $totalAmount / (1 + $taxRate), 0); // 稅額：總金額 - 未稅金額
        $netTotalAmount = $totalAmount - $totalTaxAmount; // 未稅金額 (為了處理小數誤差問題，將總金額-稅額後得到新的未稅金額)

        $items = [];

        $calculatedNetSubtotal = 0;

        foreach ($invoice_items as $item) {
            $quantity = $item['quantity'] ?? 1;
            $grossSubtotal = $item['subtotal'];
            $grossUnitPrice = $grossSubtotal / $quantity;

            if ($hasTaxId) {
                // 未稅單價與未稅小計
                $netUnitPrice = round($grossUnitPrice / (1 + $taxRate), 3);
                $netSubtotal = round($netUnitPrice * $quantity, 0); // 小計整數
            } else {
                // 含稅
                $netUnitPrice = $grossUnitPrice;
                $netSubtotal = $grossSubtotal;
            }

            $items[] = [
                'name'    => $item['name'],
                'price'    => $netUnitPrice,
                'quantity' => $quantity,
                'subtotal' => $netSubtotal,
            ];

            if ($hasTaxId) {
                $calculatedNetSubtotal += $netSubtotal;
            }
        }

        // ✅ 有統編時：將小計補誤差到最後一筆單價
        if ($hasTaxId && count($items) > 0) {
            $diff = $netTotalAmount - $calculatedNetSubtotal;

            if (abs($diff) > 0){
                $lastIndex = count($items) - 1;
                $lastItem = $items[$lastIndex];
                $quantity = $lastItem['quantity'];

                $newSubtotal = $lastItem['subtotal'] + $diff;
                $newUnitPrice = round($newSubtotal / $quantity, 3);

                $items[$lastIndex]['price'] = $newUnitPrice;
                $items[$lastIndex]['subtotal'] = $newSubtotal;
            }
        }

        return [
            'items' => $items,
            'total_amount' => $totalAmount, // 本方法不變動此值
            'tax_amount' => $totalTaxAmount ?? 0
        ];
    }



    public static function createWithItems(array $invoice, array $invoice_items): Invoice
    {
        return DB::transaction(function () use ($invoice, $invoice_items) {

            // 先建立發票記錄（不含金額）
            $invoiceModel = Invoice::create($invoice);

            // 計算明細價格（含補誤差邏輯）
            $result = self::calculateInvoiceItemPrices($invoice, $invoice_items, $invoiceModel);

            // 更新發票的金額欄位
            $invoiceModel->update([
                'total_amount' => $result['total_amount'],
                'tax_amount'   => $result['tax_amount'],
            ]);

            // 批次建立明細
            foreach ($result['items'] as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoiceModel->id,
                    'price'      => $item['price'],
                    'quantity'   => $item['quantity'],
                    'subtotal'   => $item['subtotal'],
                ]);
            }

            return $invoiceModel;
        });
    }
}
