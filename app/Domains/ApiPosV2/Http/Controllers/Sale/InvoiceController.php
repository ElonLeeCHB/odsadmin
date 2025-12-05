<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Sale;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use App\Repositories\Eloquent\Sale\InvoiceRepository;
use App\Models\Sale\Invoice;
use App\Helpers\Classes\OrmHelper;

class InvoiceController extends ApiPosController
{

    public function __construct(protected InvoiceRepository $InvoiceRepository)
    {
        parent::__construct();
        $this->InvoiceRepository = $InvoiceRepository;
    }

    /**
     * 發票列表
     * @return \Illuminate\Http\JsonResponse
     * 編輯連結使用發票群組查詢參數 /api/posv2/sales/invoices/groups/edit?invoice_id=123
     */
    public function index()
    {
        try {
            $filter_data = $this->all_data;

            $query = Invoice::query();
            OrmHelper::prepare($query, $filter_data);

            $invoices = OrmHelper::getResult($query, $filter_data);

            // 編輯連結使用發票群組查詢參數 /api/posv2/sales/invoices/groups/edit?invoice_number=AB12345678
            foreach ($invoices as $invoice) {
                $invoice->edit_url = route('api.posv2.sales.invoices.groups.edit', ['invoice_id' => $invoice->id]);
            }

            return response()->json(['success' => true, 'data' => $invoices], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $th) {
            return $this->sendJsonErrorResponse(data: ['sys_error' => $th->getMessage()], status_code: 500);
        }
    }

    public function show(string $id)
    {
        try {
            $includes = parseInclude(request('include'));

            $query = Invoice::query();

            // $rows = $query->findOrFail($id);
            $invoice = Invoice::with('invoiceItems')->findOrFail($id);

            return response()->json(['success' => true, 'data' => $invoice], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $th) {
            return $this->sendJsonErrorResponse(data: ['sys_error' => $th->getMessage()], status_code: 500, th: $th);
        }
    }

    public function store(Request $request)
    {
        $invoice = new Invoice();
        $invoice->invoice_number = $this->generateInvoiceNumber();
        return $this->save($invoice, $request);
    }

    public function update(Request $request, Invoice $invoice)
    {
        return $this->save($invoice, $request);
    }

    private function save(Invoice $invoice, Request $request)
    {
        $validated = $request->validate([
            'invoice_date' => 'required|date',
            'buyer_name' => 'nullable|string|max:20',
            'seller_name' => 'nullable|string|max:20',
            'tax_id_number' => 'nullable|string|max:12',
            'total_amount' => 'required|numeric|min:0',
            'status' => 'required|in:unpaid,paid,canceled',
            'invoice_items' => 'required|array|min:1',
            'invoice_items.*.name' => 'required|string|max:255',
            'invoice_items.*.quantity' => 'required|integer|min:1',
            'invoice_items.*.price' => 'nullable|numeric|min:0', // 建議補驗證
            'invoice_items.*.subtotal' => 'nullable|numeric|min:0'
        ]);

        try {
            DB::beginTransaction();

            // 測試時先清空
            $invoice->invoiceItems()->delete();
            $invoice->delete();

            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = $this->generateInvoiceNumber();
            }

            $invoice->fill($validated)->save();

            // 刪除舊明細
            $invoice->invoiceItems()->delete();

            $invoiceItems = $validated['invoice_items'];

            $result = InvoiceRepository::calculateInvoiceItemPrices(
                invoice: $invoice->toArray(),
                invoice_items: $invoiceItems
            );

            // $invoiceItems = $result['items'];

            // 更新稅額與總金額
            $invoice->update([
                'tax_amount' => $result['tax_amount'],
                'total_amount' => $result['total_amount'],
            ]);

            // 寫入 InvoiceItem
            foreach ($result['items'] as $index => $item) {
                $invoice->invoiceItems()->create([
                    'sort_order'       => $index + 1,
                    'name'             => $item['name'],
                    'quantity'         => $item['quantity'],
                    'price'            => $item['price'],
                    'subtotal'         => $item['subtotal'],
                ]);
            }

            DB::commit();

            return response()->json(['success' => true,'message' => '發票儲存成功','data' => $invoice->load('invoiceItems'),], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->sendJsonErrorResponse(data: ['sys_error' => $th->getMessage()],status_code: 500,th: $th);
        }
    }

    protected function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $year = now()->format('y'); // 25
        $month = now()->format('m'); // 07

        $base = $prefix . $year . $month;

        // 找當月最新編號
        $last = \App\Models\Sale\Invoice::where('invoice_number', 'like', "{$base}%")
            ->orderByDesc('invoice_number')
            ->first();

        if ($last) {
            $lastSerial = (int)substr($last->invoice_number, -4);
            $nextSerial = $lastSerial + 1;
        } else {
            $nextSerial = 1;
        }

        return $base . str_pad($nextSerial, 4, '0', STR_PAD_LEFT);
    }
}
