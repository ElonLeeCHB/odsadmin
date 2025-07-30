<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Sale;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use App\Helpers\Classes\DateHelper;
use App\Helpers\Classes\OrmHelper;
use App\Models\Sale\Invoice;
use Illuminate\Support\Facades\Validator;

/*
public function index()    // GET 所有資料
public function store()    // POST 新增
public function show()     // GET 單筆資料
public function update()   // PUT/PATCH 更新
public function destroy()  // DELETE 刪除
*/

class InvoiceBatchController extends ApiPosController
{
    public function store(Request $request)
    {
        return $this->save($request);
    }

    public function update(Request $request)
    {
        return $this->save($request);
    }

    protected function save(Request $request)
    {
        $data = $request->all();

        // order_id, order_group_id 不能都沒有
        if (!isset($data['order_id']) && !isset($data['order_group_id'])) {
            return response()->json(['error' => 'order_id, order_group_id 必須二擇一'], 422);
        }

        // order_id, order_group_id 不能同時存在
        if (isset($data['order_id']) && isset($data['order_group_id'])) {
            return response()->json(['error' => 'order_id, order_group_id 必須二擇一'], 422);
        }

        $validator = Validator::make($data, [
            'order_id' => 'nullable|integer',
            'order_group_id' => 'nullable|integer',

            'invoices' => 'required|array|min:1',
            'invoices.*.order_group_id' => 'nullable|integer',

            'invoices.*.invoice_number' => 'nullable|string|max:50',

            'invoices.*.invoice_date' => 'required|date',
            'invoices.*.buyer_name' => 'required|string|max:100',
            'invoices.*.seller_name' => 'required|string|max:100',
            'invoices.*.tax_id_number' => 'nullable|string|max:20',
            'invoices.*.total' => 'required|numeric|min:0',
            'invoices.*.status' => 'required|in:unpaid,paid,canceled',
            'invoices.*.invoice_items' => 'required|array|min:1',

            'invoices.*.invoice_items.*.name' => 'required|string|max:255',
            'invoices.*.invoice_items.*.quantity' => 'required|integer|min:1',
            'invoices.*.invoice_items.*.unit_price' => 'required|numeric|min:0',
            'invoices.*.invoice_items.*.amount' => 'required|numeric|min:0',
        ], [
            'invoices.*.invoice_items.*.name.required' => '明細名稱必填。',
            'invoices.*.invoice_items.*.name.string' => '明細名稱必須是字串。',
            'invoices.*.invoice_items.*.name.max' => '明細名稱不能超過 255 字元。',

            'invoices.*.invoice_items.*.quantity.required' => '明細數量必填。',
            'invoices.*.invoice_items.*.quantity.integer' => '明細數量必須是整數。',
            'invoices.*.invoice_items.*.quantity.min' => '明細數量至少為 1。',

            'invoices.*.invoice_items.*.unit_price.required' => '明細單價必填。',
            'invoices.*.invoice_items.*.unit_price.numeric' => '明細單價必須是數字。',
            'invoices.*.invoice_items.*.unit_price.min' => '明細單價不能小於 0。',

            'invoices.*.invoice_items.*.amount.required' => '明細金額必填。',
            'invoices.*.invoice_items.*.amount.numeric' => '明細金額必須是數字。',
            'invoices.*.invoice_items.*.amount.min' => '明細金額不能小於 0。',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $invoices = $data['invoices'] ?? [];
        if (empty($invoices)) {
            return response()->json(['error' => 'invoices cannot be empty'], 422);
        }

        // 自動補 invoice_number
        foreach ($invoices as &$invoice) {
            if (empty($invoice['invoice_number'])) {
                $invoice['invoice_number'] = $this->generateInvoiceNumber();
            }
        }
        unset($invoice);

        DB::beginTransaction();
        try {
            // 先找出資料庫已有的該 order_id 或 order_group_id 的發票號
            if (isset($data['order_id'])) {
                $existingInvoiceNumbers = Invoice::whereHas('invoiceOrderMaps', function ($q) use ($data) {
                    $q->where('order_id', $data['order_id']);
                })->pluck('invoice_number')->toArray();
            } elseif (isset($data['order_group_id'])) {
                $existingInvoiceNumbers = Invoice::where('order_group_id', $data['order_group_id'])->pluck('invoice_number')->toArray();
            } else {
                $existingInvoiceNumbers = [];
            }

            $incomingInvoiceNumbers = array_column($invoices, 'invoice_number');

            // 找出要刪除的：存在 DB 但請求沒帶的 invoice_number
            $toDelete = array_diff($existingInvoiceNumbers, $incomingInvoiceNumbers);

            // 刪除這些發票與相關資料
            foreach ($toDelete as $invNumber) {
                $invoice = Invoice::where('invoice_number', $invNumber)->first();
                if ($invoice) {
                    // 刪除明細
                    $invoice->invoiceItems()->delete();

                    // 刪除中介表資料
                    DB::table('invoice_order_maps')->where('invoice_id', $invoice->id)->delete();

                    // 刪除發票
                    $invoice->delete();
                }
            }

            // 新增或更新請求中的發票
            foreach ($invoices as $invoiceData) {
                $invoice = Invoice::updateOrCreate(
                    ['invoice_number' => $invoiceData['invoice_number']],
                    [
                        // 只有在沒有 order_id 時才設定 order_group_id，避免衝突
                        'order_group_id' => isset($data['order_id']) ? null : ($data['order_group_id'] ?? null),
                        'invoice_date'   => $invoiceData['invoice_date'],
                        'tax_id_number'  => $invoiceData['tax_id_number'] ?? null,
                        'buyer_name'     => $invoiceData['buyer_name'],
                        'seller_name'    => $invoiceData['seller_name'],
                        'total'          => $invoiceData['total'],
                        'status'         => $invoiceData['status'],
                    ]
                );

                // 清除原本明細
                $invoice->invoiceItems()->delete();

                // 新增明細
                foreach ($invoiceData['invoice_items'] as $item) {
                    $invoice->invoiceItems()->create([
                        'name'       => $item['name'],
                        'quantity'   => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'amount'     => $item['amount'],
                    ]);
                }

                // 有 order_id，更新中介表映射
                if (isset($data['order_id'])) {
                    DB::table('invoice_order_maps')->where('invoice_id', $invoice->id)->delete();

                    DB::table('invoice_order_maps')->insert([
                        'invoice_id' => $invoice->id,
                        'order_id' => $data['order_id'],
                    ]);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => '發票處理完成']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    protected function generateInvoiceNumber(): string
    {
        $prefix = 'AB-';

        do {
            // 亂數 8 位數字
            $randomNumber = str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);

            $invoiceNumber = $prefix . $randomNumber;

            // 檢查是否已存在該號碼
            $exists = \App\Models\Sale\Invoice::where('invoice_number', $invoiceNumber)->exists();
        } while ($exists);

        return $invoiceNumber;
    }
}
