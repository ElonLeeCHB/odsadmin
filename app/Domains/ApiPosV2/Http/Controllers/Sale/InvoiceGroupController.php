<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Sale;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use App\Models\Sale\InvoiceGroup;
use App\Models\Sale\InvoiceGroupOrder;
use App\Models\Sale\InvoiceGroupInvoice;
use App\Models\Sale\Order;
use App\Models\Sale\Invoice;
use App\Models\Sale\InvoiceItem;

class InvoiceGroupController extends ApiPosController
{
    /**
     * 查詢群組資料（RESTful show - 單一資源詳情）
     * 參數優先順序：group_no > order_code > invoice_number
     * 如果提供 group_no，則忽略其他參數
     *
     * 返回規則：
     * - 群組：只返回 status='active' 的群組
     * - 發票：只返回 status='pending' 或 'issued' 的發票（排除已作廢）
     *
     * 注意：所有開票方式都使用群組（包括標準一對一）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $groupNo = $request->input('group_no');
        $orderCode = $request->input('order_code');
        $invoiceNumber = $request->input('invoice_number');

        // 驗證：必須提供其中一個參數
        if (empty($groupNo) && empty($orderCode) && empty($invoiceNumber)) {
            return response()->json([
                'success' => false,
                'message' => '請提供 group_no、order_code 或 invoice_number 其中一個參數',
            ], 400, [], JSON_UNESCAPED_UNICODE);
        }

        // 根據優先順序查詢群組
        $invoiceGroup = null;
        $usedParam = null; // 記錄實際使用的參數

        // 優先順序 1: group_no
        if (!empty($groupNo)) {
            // 只查詢有效的群組
            $invoiceGroup = InvoiceGroup::where('group_no', $groupNo)
                ->where('status', 'active')
                ->first();
            $usedParam = 'group_no';
        }
        // 優先順序 2: order_code（僅在沒有 group_no 時使用）
        elseif (!empty($orderCode)) {
            $order = Order::where('code', $orderCode)->first();

            if ($order) {
                // 查找該訂單所屬的有效群組
                $invoiceGroup = InvoiceGroup::whereHas('orders', function ($query) use ($order) {
                    $query->where('order_id', $order->id);
                })->where('status', 'active')->first();
            }
            $usedParam = 'order_code';
        }
        // 優先順序 3: invoice_number（僅在前兩個都沒有時使用）
        elseif (!empty($invoiceNumber)) {
            // 查找有效的發票（pending 或 issued）
            $invoice = Invoice::where('invoice_number', $invoiceNumber)
                ->whereIn('status', ['pending', 'issued'])
                ->first();

            if ($invoice) {
                // 查找該發票所屬的有效群組
                $invoiceGroup = InvoiceGroup::whereHas('invoices', function ($query) use ($invoice) {
                    $query->where('invoice_id', $invoice->id);
                })->where('status', 'active')->first();
            }
            $usedParam = 'invoice_number';
        }

        // 如果找不到群組
        if (!$invoiceGroup) {
            return response()->json([
                'success' => false,
                'message' => '找不到相關的發票群組',
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }

        // 載入相關的訂單和發票資料（發票只載入有效的：pending 或 issued）
        $invoiceGroup->load([
            'orders' => function ($query) {
                $query->select([
                    'orders.id',
                    'orders.code',
                    'orders.customer_id',
                    'orders.personal_name',
                    'orders.mobile',
                    'orders.payment_total',
                    'orders.order_date',
                    'orders.status_code',
                    'orders.created_at'
                ]);
            },
            'invoices' => function ($query) {
                // 只載入有效的發票（排除已作廢）
                $query->whereIn('status', ['pending', 'issued'])
                    ->select([
                        'invoices.id',
                        'invoices.invoice_number',
                        'invoices.invoice_type',
                        'invoices.invoice_date',
                        'invoices.buyer_name',
                        'invoices.tax_id_number',
                        'invoices.total_amount',
                        'invoices.tax_amount',
                        'invoices.net_amount',
                        'invoices.status',
                        'invoices.carrier_type',
                        'invoices.carrier_number',
                        'invoices.created_at'
                    ])->with('invoiceItems');
            },
            'invoiceGroupOrders',
            'invoiceGroupInvoices',
        ]);

        // 組織回傳資料
        $data = [
            'used_param' => $usedParam, // 標示實際使用的參數
            'group' => [
                'id' => $invoiceGroup->id,
                'group_no' => $invoiceGroup->group_no,
                'invoice_issue_mode' => $invoiceGroup->invoice_issue_mode,
                'status' => $invoiceGroup->status,
                'order_count' => $invoiceGroup->order_count,
                'invoice_count' => $invoiceGroup->invoice_count,
                'total_amount' => $invoiceGroup->total_amount,
                'void_reason' => $invoiceGroup->void_reason,
                'voided_at' => $invoiceGroup->voided_at,
                'created_at' => $invoiceGroup->created_at,
            ],
            'orders' => $invoiceGroup->orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'code' => $order->code,
                    'customer_id' => $order->customer_id,
                    'personal_name' => $order->personal_name,
                    'mobile' => $order->mobile,
                    'payment_total' => $order->payment_total,
                    'order_date' => $order->order_date,
                    'status_code' => $order->status_code,
                    'allocated_amount' => $order->pivot->order_amount, // 從中間表取得分配金額
                    'created_at' => $order->created_at,
                ];
            }),
            'invoices' => $invoiceGroup->invoices->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'invoice_type' => $invoice->invoice_type,
                    'invoice_date' => $invoice->invoice_date,
                    'buyer_name' => $invoice->buyer_name,
                    'tax_id_number' => $invoice->tax_id_number,
                    'total_amount' => $invoice->total_amount,
                    'tax_amount' => $invoice->tax_amount,
                    'net_amount' => $invoice->net_amount,
                    'status' => $invoice->status,
                    'carrier_type' => $invoice->carrier_type,
                    'carrier_number' => $invoice->carrier_number,
                    'allocated_amount' => $invoice->pivot->invoice_amount, // 從中間表取得分配金額
                    'invoice_items' => $invoice->invoiceItems,
                    'created_at' => $invoice->created_at,
                ];
            }),
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * 建立發票群組資料（RESTful store）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // 驗證：新增時不能有 group_no
        if ($request->has('group_no')) {
            return response()->json([
                'success' => false,
                'message' => '新增時不應提供 group_no'
            ], 400, [], JSON_UNESCAPED_UNICODE);
        }

        return $this->save($request, null);
    }

    /**
     * 修改發票群組資料（RESTful update）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        // 驗證：修改時必須有 group_no
        if (!$request->has('group_no')) {
            return response()->json([
                'success' => false,
                'message' => '修改時必須提供 group_no'
            ], 400, [], JSON_UNESCAPED_UNICODE);
        }

        $groupNo = $request->input('group_no');

        // 查找現有群組
        $invoiceGroup = InvoiceGroup::where('group_no', $groupNo)
            ->where('status', 'active')
            ->first();

        if (!$invoiceGroup) {
            return response()->json([
                'success' => false,
                'message' => '找不到指定的發票群組或群組已作廢'
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }

        return $this->save($request, $invoiceGroup);
    }

    /**
     * 統一處理新增和修改邏輯（私有方法）
     *
     * @param Request $request
     * @param InvoiceGroup|null $invoiceGroup
     * @return JsonResponse
     */
    private function save(Request $request, ?InvoiceGroup $invoiceGroup): JsonResponse
    {
        // 驗證請求
        $validated = $this->validateRequest($request, !is_null($invoiceGroup));

        DB::beginTransaction();

        // 1. 處理或建立群組
        if (!$invoiceGroup) {
            // 新增群組
            $invoiceGroup = $this->createInvoiceGroup($validated);
        } else {
            // 更新群組（更新統計欄位）
            $this->updateInvoiceGroup($invoiceGroup, $validated);
        }

        // 2. 同步訂單關聯
        $this->syncOrders($invoiceGroup, $validated['order_ids']);

        // 3. 同步發票和明細
        $this->syncInvoices($invoiceGroup, $validated['invoices']);

        DB::commit();

        // 重新載入資料
        $invoiceGroup->load(['orders', 'invoices.invoiceItems']);

        // 組織回傳的發票和明細資料
        $invoicesData = $invoiceGroup->invoices->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status,
                'total_amount' => $invoice->total_amount,
                'invoice_items' => $invoice->invoiceItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'subtotal' => $item->subtotal,
                    ];
                })->toArray(),
            ];
        })->toArray();

        return response()->json([
            'success' => true,
            'message' => $invoiceGroup->wasRecentlyCreated ? '發票群組建立成功' : '發票群組更新成功',
            'data' => [
                'group_no' => $invoiceGroup->group_no,
                'group_id' => $invoiceGroup->id,
                'invoice_issue_mode' => $invoiceGroup->invoice_issue_mode,
                'order_count' => $invoiceGroup->order_count,
                'order_ids' => $invoiceGroup->orders->pluck('id')->toArray(),
                'invoice_count' => $invoiceGroup->invoice_count,
                'total_amount' => $invoiceGroup->total_amount,
                'invoices' => $invoicesData,
            ]
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * 驗證請求資料
     *
     * @param Request $request
     * @param bool $isUpdate
     * @return array
     */
    private function validateRequest(Request $request, bool $isUpdate = false): array
    {
        $rules = [
            'order_ids' => 'required|string',
            'invoice_issue_mode' => 'required|in:standard,split,merge,mixed',
            'invoices' => 'required|array|min:1',
            'invoices.*.invoice_type' => 'nullable|in:single,duplicate,triplicate',
            'invoices.*.invoice_format' => 'nullable|in:thermal,a5',
            'invoices.*.invoice_date' => 'required|date',
            'invoices.*.buyer_name' => 'nullable|string|max:255',
            'invoices.*.seller_name' => 'nullable|string|max:255',
            'invoices.*.tax_id_number' => 'nullable|string|max:20',
            'invoices.*.customer_id' => 'nullable|integer',
            'invoices.*.tax_type' => 'required|in:taxable,exempt,zero_rate,mixed,special',
            'invoices.*.tax_state' => 'required|integer|in:0,1',
            'invoices.*.email' => 'nullable|email',
            'invoices.*.content' => 'nullable|string',
            'invoices.*.carrier_type' => 'required|in:none,phone_barcode,citizen_cert,member_card,credit_card,icash,easycard,ipass,email,donation',
            'invoices.*.carrier_number' => 'nullable|string|max:255',
            'invoices.*.donation_code' => 'nullable|string|max:20',
            'invoices.*.invoice_items' => 'required|array|min:1',
            'invoices.*.invoice_items.*.name' => 'required|string|max:255',
            'invoices.*.invoice_items.*.quantity' => 'required|numeric|min:0.001',
            'invoices.*.invoice_items.*.price' => 'required|numeric|min:0',
            'invoices.*.invoice_items.*.is_tax_included' => 'required|boolean',
            'invoices.*.invoice_items.*.remark' => 'nullable|string|max:255',
            'invoices.*.invoice_items.*.item_tax_type' => 'nullable|integer|in:0,1,2',
        ];

        if ($isUpdate) {
            $rules['group_no'] = 'required|string';
        }

        return $request->validate($rules);
    }

    /**
     * 建立新群組
     *
     * @param array $validated
     * @return InvoiceGroup
     */
    private function createInvoiceGroup(array $validated): InvoiceGroup
    {
        // 生成群組編號：年份(4碼) + 流水號(4碼)
        $year = now()->format('Y');
        $lastGroup = InvoiceGroup::where('group_no', 'like', "{$year}%")
            ->orderByDesc('group_no')
            ->first();

        if ($lastGroup) {
            $lastSerial = (int)substr($lastGroup->group_no, -4);
            $nextSerial = $lastSerial + 1;
        } else {
            $nextSerial = 1;
        }

        $groupNo = $year . str_pad($nextSerial, 4, '0', STR_PAD_LEFT);

        $invoiceGroup = InvoiceGroup::create([
            'group_no' => $groupNo,
            'invoice_issue_mode' => $validated['invoice_issue_mode'],
            'status' => 'active',
            'created_by' => auth()->id(),
            'order_count' => 0,
            'invoice_count' => 0,
            'total_amount' => 0,
        ]);

        return $invoiceGroup;
    }

    /**
     * 更新群組統計資料
     *
     * @param InvoiceGroup $invoiceGroup
     * @param array $validated
     * @return void
     */
    private function updateInvoiceGroup(InvoiceGroup $invoiceGroup, array $validated): void
    {
        // 統計資料會在 syncOrders 和 syncInvoices 完成後更新
        // 這裡可以更新其他需要的欄位
    }

    /**
     * 同步訂單關聯
     *
     * @param InvoiceGroup $invoiceGroup
     * @param string $orderIds
     * @return void
     */
    private function syncOrders(InvoiceGroup $invoiceGroup, string $orderIds): void
    {
        $orderIdArray = array_map('trim', explode(',', $orderIds));

        // 查詢訂單
        $orders = Order::whereIn('id', $orderIdArray)->get();

        if ($orders->count() !== count($orderIdArray)) {
            throw new \Exception('部分訂單不存在');
        }

        // 刪除舊的關聯
        InvoiceGroupOrder::where('group_id', $invoiceGroup->id)->delete();

        // 建立新的關聯
        $totalOrderAmount = 0;
        foreach ($orders as $order) {
            InvoiceGroupOrder::create([
                'group_id' => $invoiceGroup->id,
                'order_id' => $order->id,
                'order_amount' => $order->payment_total,
            ]);
            $totalOrderAmount += $order->payment_total;
        }

        // 更新群組的訂單統計
        $invoiceGroup->update([
            'order_count' => $orders->count(),
        ]);
    }

    /**
     * 同步發票和明細
     *
     * @param InvoiceGroup $invoiceGroup
     * @param array $invoicesData
     * @return void
     */
    private function syncInvoices(InvoiceGroup $invoiceGroup, array $invoicesData): void
    {
        // 根據開票模式決定要處理的發票數量
        // standard/merge: 只取第一張發票（忽略多餘的）
        // split/mixed: 處理所有發票
        if (in_array($invoiceGroup->invoice_issue_mode, ['standard', 'merge'])) {
            $invoicesData = array_slice($invoicesData, 0, 1);
        }

        // 刪除舊的發票關聯和發票
        $oldInvoiceIds = InvoiceGroupInvoice::where('group_id', $invoiceGroup->id)
            ->pluck('invoice_id')
            ->toArray();

        InvoiceGroupInvoice::where('group_id', $invoiceGroup->id)->delete();
        Invoice::whereIn('id', $oldInvoiceIds)->delete(); // 這會連帶刪除 invoice_items

        // 建立新的發票
        $totalInvoiceAmount = 0;
        $invoiceCount = 0;

        foreach ($invoicesData as $invoiceData) {
            $items = $invoiceData['invoice_items'];

            // 計算發票總額和稅額
            $calculated = $this->calculateInvoiceAmounts($items, $invoiceData['tax_state'] == 0);

            // 建立發票
            $invoice = Invoice::create([
                'invoice_number' => null, // pending 狀態時為 null，issue 時才取得發票號碼
                'invoice_type' => $invoiceData['invoice_type'] ?? 'single', // 預設單聯式
                'invoice_format' => $invoiceData['invoice_format'] ?? 'thermal', // 預設小張熱感紙
                'invoice_date' => $invoiceData['invoice_date'],
                'customer_id' => $invoiceData['customer_id'] ?? null,
                'buyer_name' => $invoiceData['buyer_name'] ?? null,
                'seller_name' => $invoiceData['seller_name'] ?? null,
                'tax_id_number' => $invoiceData['tax_id_number'] ?? null,
                'tax_type' => $invoiceData['tax_type'],
                'tax_state' => $invoiceData['tax_state'],
                'tax_amount' => $calculated['tax_amount'],
                'net_amount' => $calculated['net_amount'],
                'total_amount' => $calculated['total_amount'],
                'email' => $invoiceData['email'] ?? null,
                'content' => $invoiceData['content'] ?? null,
                'carrier_type' => $invoiceData['carrier_type'],
                'carrier_number' => $invoiceData['carrier_number'] ?? null,
                'donation_code' => $invoiceData['donation_code'] ?? null,
                'status' => 'pending', // 初始狀態為 pending
                'created_by' => auth()->id(),
            ]);

            // 建立發票明細
            foreach ($items as $index => $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'sort_order' => $index + 1,
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['price'] * $item['quantity'],
                    'is_tax_included' => $item['is_tax_included'],
                    'remark' => $item['remark'] ?? null,
                    'item_tax_type' => $item['item_tax_type'] ?? null,
                ]);
            }

            // 建立群組-發票關聯
            InvoiceGroupInvoice::create([
                'group_id' => $invoiceGroup->id,
                'invoice_id' => $invoice->id,
                'invoice_amount' => $calculated['total_amount'],
            ]);

            $totalInvoiceAmount += $calculated['total_amount'];
            $invoiceCount++;
        }

        // 更新群組的發票統計
        $invoiceGroup->update([
            'invoice_count' => $invoiceCount,
            'total_amount' => $totalInvoiceAmount,
        ]);
    }

    /**
     * 計算發票金額和稅額
     *
     * @param array $items
     * @param bool $isTaxIncluded
     * @return array
     */
    private function calculateInvoiceAmounts(array $items, bool $isTaxIncluded): array
    {
        $subtotalSum = 0;

        foreach ($items as $item) {
            $subtotalSum += $item['price'] * $item['quantity'];
        }

        if ($isTaxIncluded) {
            // 含稅：總額 = 小計總和，稅額 = 總額 / 1.05 * 0.05
            $totalAmount = round($subtotalSum, 0);
            $netAmount = round($totalAmount / 1.05, 0);
            $taxAmount = $totalAmount - $netAmount;
        } else {
            // 未稅：淨額 = 小計總和，稅額 = 淨額 * 0.05，總額 = 淨額 + 稅額
            $netAmount = round($subtotalSum, 0);
            $taxAmount = round($netAmount * 0.05, 0);
            $totalAmount = $netAmount + $taxAmount;
        }

        return [
            'tax_amount' => $taxAmount,
            'net_amount' => $netAmount,
            'total_amount' => $totalAmount,
        ];
    }
}
