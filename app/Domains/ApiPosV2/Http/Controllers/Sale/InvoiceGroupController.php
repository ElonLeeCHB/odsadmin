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
     * 解析開票上下文（統一入口）
     *
     * 根據提供的參數（order_id、invoice_id、group_id 等）判斷：
     * - 是否已有群組（edit 模式）
     * - 是否需要新建群組（create 模式）
     *
     * 參數優先順序：group_id > group_no > order_id > order_code > invoice_id > invoice_number
     *
     * 使用模式：
     * - 不帶參數：返回空資料結構，用於新增模式（mode: create）
     * - 帶參數且找到群組：返回現有群組資料（mode: edit）
     * - 帶參數但找不到群組：返回訂單/發票資料（mode: create）
     *
     * 返回規則：
     * - 群組：只返回 status='active' 的群組
     * - 發票：返回所有狀態的發票（包括 pending、issued、voided）
     *
     * 注意：所有開票方式都使用群組（包括標準一對一）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resolve(Request $request): JsonResponse
    {
        // 預先定義訂單欄位
        $orderColumns = [
            'id',
            'code',
            'customer_id',
            'personal_name',
            'mobile',
            'payment_total',
            'payment_tin',
            'order_date',
            'status_code',
            'created_at'
        ];

        // group_no order_code invoice_number

        // $groupNo = $request->input('group_no');
        $groupId = $request->input('group_id');
        $groupNo = $request->input('group_no');
        $orderId = $request->input('order_id');
        $orderCode = $request->input('order_code');
        $invoiceId = $request->input('invoice_id');
        $invoiceNumber = $request->input('invoice_number');

        // 發票預設項目
        $invoiceItems = (new \App\Caches\Custom\Sales\DefaultInvoiceItems())->getData();

        // 如果沒有提供任何參數，視為新增模式
        if (empty($groupId) && empty($groupNo) && empty($orderId) && empty($orderCode) && empty($invoiceId) && empty($invoiceNumber)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'mode' => 'create',
                    'used_param' => null,
                    'group' => null,
                    'orders' => [],
                    'invoices' => [],
                    'invoice_defaults' => $this->getInvoiceDefaults(null),
                ],
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }

        // 根據優先順序查詢群組
        $invoiceGroup = null;
        $usedParam = null; // 記錄實際使用的參數

        // 先依據提供的參數查找群組，此時重點在群組。原則上先不載入關聯資料。

        // groups
        if (!empty($groupId) || !empty($groupNo)) {

            // 優先順序 1: groupId
            if (!empty($groupId)) {
                $invoiceGroup = InvoiceGroup::find($groupId);
            }
            // 優先順序 3: order_code
            else if (!empty($groupNo)) {
                $invoiceGroup = InvoiceGroup::where('code', $groupNo)->first();
            }

            if (empty($invoiceGroup)) {
                return response()->json([
                    'success' => false,
                    'message' => '找不到指定的群組',
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            $usedParam = 'group_no';
        }
        // orders
        elseif (!empty($orderId) || !empty($orderCode)) {

            // 優先順序 3: order_id
            if (!empty($orderId)) {
                $order = Order::find($orderId);
                $usedParam = 'order_id';
            }
            // 優先順序 4: order_code
            else if (!empty($orderCode)) {
                $order = Order::where('code', $orderCode)->first();
                $usedParam = 'order_code';
            }

            if (empty($order)) {
                return response()->json([
                    'success' => false,
                    'message' => '找不到指定的訂單',
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // 查找該訂單所屬的有效群組
            $invoiceGroup = InvoiceGroup::whereHas('orders', function ($query) use ($order) {
                $query->where('order_id', $order->id);
            })->where('status', 'active')->first();
        }
        // invoices
        elseif (!empty($invoiceId) || !empty($invoiceNumber)) {

            // 優先順序 5: invoice_id
            if (!empty($invoiceId)) {
                $invoice = Invoice::find($invoiceId);
                $usedParam = 'invoice_id';
            }
            // 優先順序 6: invoice_number
            else if (!empty($invoiceNumber)) {
                $invoice = Invoice::where('invoice_number', $invoiceNumber)->first();
                $usedParam = 'invoice_number';
            }

            if (empty($invoice)) {
                return response()->json([
                    'success' => false,
                    'message' => '找不到指定的發票',
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // 查找該發票所屬的有效群組
            if ($invoice) {
                $invoiceGroup = InvoiceGroup::whereHas('invoices', function ($query) use ($invoice) {
                    $query->where('invoice_id', $invoice->id);
                })->where('status', 'active')->first();
            }
        }

        // 找不到群組時，視為新增模式（回傳訂單或發票資訊）
        if (empty($invoiceGroup)) {
            // 準備訂單資料（如果有透過訂單查詢）
            $orderData = [];
            if (isset($order) && $order) {
                $order->load([
                    'orderProducts' => function ($query) {
                        $query->select('id', 'order_id', 'name', 'price', 'quantity');
                    },
                    'orderTotals'
                ]);
                $orderData = [[
                    'id' => $order->id,
                    'code' => $order->code,
                    'payment_total' => $order->payment_total,
                    'payment_tin' => $order->payment_tin,
                    'order_products' => $order->orderProducts,
                    'order_totals' => $order->orderTotals,
                ]];
            }

            // 準備發票資料（如果有透過發票查詢）
            $invoiceData = [];
            if (isset($invoice) && $invoice) {
                $invoice->load('invoiceItems');
                $invoiceData = [[
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
                    'created_at' => $invoice->created_at,
                    'invoice_items' => $invoice->invoiceItems,
                ]];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'mode' => 'create',
                    'used_param' => $usedParam,
                    'group' => null,
                    'orders' => $orderData,
                    'invoices' => $invoiceData,
                    'invoice_defaults' => $this->getInvoiceDefaults($order ?? null),
                ],
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }

        // 載入相關的訂單和發票資料（發票載入所有狀態，包括作廢）
        if ($invoiceGroup) {
            $invoiceGroup->load([
            'orders' => function ($query) {
                $query->select('orders.id', 'orders.code', 'orders.payment_total', 'orders.payment_tin')
                    ->with([
                        'orderProducts' => function ($query) {
                            $query->select('id', 'order_id', 'name', 'price', 'quantity');
                        },
                        'orderTotals'
                    ]);
            },
            'invoices' => function ($query) {
                // 載入所有狀態的發票（包括 pending、issued、voided）
                $query->select([
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
        }

        // 組織回傳資料
        $data = [];

        $data['mode'] = 'edit'; // 找到群組，為編輯模式
        $data['used_param'] = $usedParam; // 標示實際使用的參數

        $data['group'] = $invoiceGroup ? [
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
        ] : null;

        $data['orders'] = $invoiceGroup ? $invoiceGroup->orders : (isset($order) ? [$order] : []);
        $data['invoices'] = $invoiceGroup ? $invoiceGroup->invoices : [];

        // 編輯模式不需要 invoice_defaults
        $data['invoice_defaults'] = null;

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * 取得群組詳情（RESTful show）
     *
     * @param int $id 群組 ID
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $invoiceGroup = InvoiceGroup::find($id);

        if (!$invoiceGroup) {
            return response()->json([
                'success' => false,
                'message' => '找不到指定的群組',
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }

        // 載入相關的訂單和發票資料
        $invoiceGroup->load([
            'orders' => function ($query) {
                $query->select('orders.id', 'orders.code', 'orders.payment_total', 'orders.payment_tin')
                    ->with([
                        'orderProducts' => function ($query) {
                            $query->select('id', 'order_id', 'name', 'price', 'quantity');
                        },
                        'orderTotals'
                    ]);
            },
            'invoices' => function ($query) {
                $query->select([
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
        ]);

        return response()->json([
            'success' => true,
            'data' => [
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
                'orders' => $invoiceGroup->orders,
                'invoices' => $invoiceGroup->invoices,
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * 檢查訂單是否可加入發票群組
     *
     * 單筆檢查：?order_id=123 或 ?order_code=ORD20250001
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkOrder(Request $request): JsonResponse
    {
        $orderId = $request->input('order_id');
        $orderCode = $request->input('order_code');

        // 驗證：必須提供其中一個參數，且只能提供一個
        if (empty($orderId) && empty($orderCode)) {
            return response()->json([
                'success' => false,
                'message' => '請提供訂單參數（order_id 或 order_code）',
            ], 400, [], JSON_UNESCAPED_UNICODE);
        }

        if (!empty($orderId) && !empty($orderCode)) {
            return response()->json([
                'success' => false,
                'message' => '只能提供一個訂單參數（order_id 或 order_code）',
            ], 400, [], JSON_UNESCAPED_UNICODE);
        }

        // 處理單筆檢查
        if (!empty($orderId)) {
            return $this->checkOrderSingle($orderId, 'id');
        } else {
            return $this->checkOrderSingle($orderCode, 'code');
        }
    }

    /**
     * 單個訂單檢查
     *
     * @param string|int $key 訂單 ID 或編號
     * @param string $type 查詢類型：'id' 或 'code'
     * @return JsonResponse
     */
    private function checkOrderSingle($key, string $type): JsonResponse
    {
        // 1. 查詢訂單是否存在
        if ($type === 'id') {
            $order = Order::select('id', 'code', 'payment_total', 'payment_tin')
                ->where('id', $key)
                ->with([
                    'orderProducts' => function ($query) {
                        $query->select('id', 'order_id', 'name', 'price', 'quantity')
                            ->with(['orderProductOptions' => function ($query) {
                                $query->select('id', 'order_product_id', 'name', 'value', 'quantity', 'price', 'subtotal');
                            }]);
                    },
                    'orderTotals'
                ])
                ->first();
        } else {
            $order = Order::select('id', 'code', 'payment_total', 'payment_tin')
                ->where('code', $key)
                ->with([
                    'orderProducts' => function ($query) {
                        $query->select('id', 'order_id', 'name', 'price', 'quantity')
                            ->with(['orderProductOptions' => function ($query) {
                                $query->select('id', 'order_product_id', 'name', 'value', 'quantity', 'price', 'subtotal');
                            }]);
                    },
                    'orderTotals'
                ])
                ->first();
        }

        if (!$order) {
            return response()->json([
                'success' => true,
                'available' => false,
                'reason_code' => 'not_exist',
                'message' => "找不到訂單：{$key}",
                'data' => [
                    'order_code' => $type === 'code' ? $key : null,
                    'order_id' => $type === 'id' ? $key : null,
                ],
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }

        // 2. 檢查訂單是否已在活動群組中
        $groupOrder = InvoiceGroupOrder::where('order_id', $order->id)
            ->where('is_active', 1)
            ->with(['invoiceGroup' => function ($query) {
                $query->where('status', 'active');
            }])
            ->first();

        // 建議的發票項目（拆解加價購）
        $suggestItems = $this->splitOrderInvoiceItems($order);

        // 訂單未在群組中，可用
        if (!$groupOrder || !$groupOrder->invoiceGroup) {

            // 取得發票預設值，移除不需要的欄位
            $invoiceDefaults = $this->getInvoiceDefaults($order);
            unset($invoiceDefaults['suggest_items']);
            unset($invoiceDefaults['invoice_items']);

            return response()->json([
                'success' => true,
                'available' => true,
                'message' => '此訂單尚未加入群組',
                'data' => [
                    'order_code' => $order->code,
                    'order_id' => $order->id,
                    'payment_total' => $order->payment_total,
                    'payment_tin' => $order->payment_tin,
                    'order_totals' => $order->orderTotals,
                    'order_products' => $order->orderProducts,
                    'suggest_items' => $suggestItems,
                    'invoice_defaults' => $invoiceDefaults,
                ],
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }

        // 訂單已在群組中，不可用
        $group = $groupOrder->invoiceGroup;
        return response()->json([
            'success' => true,
            'available' => false,
            'reason_code' => 'in_group',
            'message' => "此訂單已加入群組 {$group->group_no}",
            'data' => [
                'order_code' => $order->code,
                'order_id' => $order->id,
                'payment_total' => $order->payment_total,
                'payment_tin' => $order->payment_tin,
                'group_info' => [
                    'group_id' => $group->id,
                    'group_no' => $group->group_no,
                    'invoice_issue_mode' => $group->invoice_issue_mode,
                    'order_count' => $group->order_count,
                    'invoice_count' => $group->invoice_count,
                    'total_amount' => $group->total_amount,
                    'created_at' => $group->created_at,
                ],
                'suggest_items' => $suggestItems,
            ],
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
    public function update(Request $request, $group_id): JsonResponse
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
        $invoiceGroup = InvoiceGroup::where('id', $group_id)
            // ->where('group_no', $groupNo)
            // ->where('status', 'active')
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

        try {
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
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400, [], JSON_UNESCAPED_UNICODE);
        }

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
            'invoices.*.tax_included' => 'nullable|integer|in:0,1',
            'invoices.*.email' => 'nullable|email',
            'invoices.*.content' => 'nullable|string',
            'invoices.*.carrier_type' => 'required|in:none,phone_barcode,citizen_cert,member_card,credit_card,icash,easycard,ipass,email,donation',
            'invoices.*.carrier_number' => 'nullable|string|max:255',
            'invoices.*.donation_code' => 'nullable|string|max:20',
            'invoices.*.invoice_items' => 'required|array|min:1',
            'invoices.*.invoice_items.*.name' => 'required|string|max:255',
            'invoices.*.invoice_items.*.quantity' => 'required|numeric|min:0.001',
            'invoices.*.invoice_items.*.price' => 'required|numeric',
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

        // 檢查訂單是否已經在其他活動群組中（利用數據庫唯一索引防止重複）
        $existingGroupOrders = InvoiceGroupOrder::whereIn('order_id', $orderIdArray)
            ->where('group_id', '!=', $invoiceGroup->id)
            ->where('is_active', 1) // 只檢查活動中的群組
            ->with(['invoiceGroup:id,group_no', 'order:id,code'])
            ->get();

        if ($existingGroupOrders->isNotEmpty()) {
            $duplicates = $existingGroupOrders->map(function ($groupOrder) {
                return sprintf(
                    '訂單 %s 已在群組 %s 中',
                    $groupOrder->order->code ?? "ID:{$groupOrder->order_id}",
                    $groupOrder->invoiceGroup->group_no ?? "ID:{$groupOrder->group_id}"
                );
            })->toArray();

            throw new \Exception('以下訂單已經在其他群組中，不可重複加入：' . implode('、', $duplicates));
        }

        // 取得目前群組中的訂單 ID
        $currentOrderIds = InvoiceGroupOrder::where('group_id', $invoiceGroup->id)
            ->where('is_active', 1)
            ->pluck('order_id')
            ->toArray();

        $newOrderIds = $orders->pluck('id')->toArray();

        // 找出需要移除的訂單（在舊的但不在新的）
        $toRemoveIds = array_diff($currentOrderIds, $newOrderIds);

        // 將移除的訂單標記為失效
        if (!empty($toRemoveIds)) {
            InvoiceGroupOrder::where('group_id', $invoiceGroup->id)
                ->whereIn('order_id', $toRemoveIds)
                ->update(['is_active' => null]);
        }

        // 使用 updateOrCreate 處理訂單關聯（新增或更新）
        $totalOrderAmount = 0;
        foreach ($orders as $order) {
            InvoiceGroupOrder::updateOrCreate(
                [
                    'group_id' => $invoiceGroup->id,
                    'order_id' => $order->id,
                ],
                [
                    'order_amount' => $order->payment_total,
                    'is_active' => 1,
                ]
            );
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

        // 取得現有發票並檢查狀態
        $oldInvoiceIds = InvoiceGroupInvoice::where('group_id', $invoiceGroup->id)
            ->pluck('invoice_id')
            ->toArray();

        // 檢查發票狀態限制，並收集需要保留的發票 ID（voided 狀態）
        $voidedInvoiceIds = [];
        if (!empty($oldInvoiceIds)) {
            $existingInvoices = Invoice::whereIn('id', $oldInvoiceIds)->get();

            foreach ($existingInvoices as $existingInvoice) {
                // voided 狀態：保留關聯，不刪除
                if ($existingInvoice->status->value === 'voided') {
                    $voidedInvoiceIds[] = $existingInvoice->id;
                    continue; // 作廢的發票不影響其他發票的修改
                }

                // issued 狀態：禁止修改，只允許作廢操作
                if ($existingInvoice->status->value === 'issued') {
                    throw new \Exception(
                        "發票 {$existingInvoice->invoice_number} 已開立，無法修改。如需變更請先作廢該發票"
                    );
                }
            }
        }

        // 計算需要刪除的發票 ID（排除已作廢的發票）
        $invoiceIdsToDelete = array_diff($oldInvoiceIds, $voidedInvoiceIds);

        // 刪除舊的發票關聯和發票（保留已作廢的發票關聯）
        if (!empty($invoiceIdsToDelete)) {
            InvoiceGroupInvoice::where('group_id', $invoiceGroup->id)
                ->whereIn('invoice_id', $invoiceIdsToDelete)
                ->delete();
            Invoice::whereIn('id', $invoiceIdsToDelete)->delete(); // 這會連帶刪除 invoice_items
        }

        // 建立新的發票
        $totalInvoiceAmount = 0;
        $invoiceCount = 0;

        foreach ($invoicesData as $invoiceData) {
            $items = $invoiceData['invoice_items'];

            // 計算發票總額和稅額
            // tax_included: 1=含稅（不外加稅額），0=未稅（需外加稅額），預設為 1
            $taxIncluded = $invoiceData['tax_included'] ?? 1;
            $calculated = $this->calculateInvoiceAmounts($items, $taxIncluded == 1);

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
                'tax_included' => $taxIncluded,
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

    /**
     * 拆解訂單為發票項目（處理加價購 + orderTotals）
     *
     * 說明：
     * - 主商品作為一個發票項目
     * - 有加價的選項（price > 0）拆分為獨立的發票項目
     * - 免費選項（price = 0）不拆分
     * - orderTotals 處理運費、折扣、優惠券
     * - 預設整合相同項目（name + price 相同即合併）
     *
     * 項目順序：
     * 1. 主商品
     * 2. 加價購項目
     * 3. 運費（如有）
     * 4. 折扣（負數）
     * 5. 優惠券折扣（負數）
     *
     * @param Order $order
     * @param bool $consolidate 是否整合相同項目（預設 true）
     * @param bool $includeOrderTotals 是否包含 orderTotals（預設 true）
     * @return array
     */
    private function splitOrderInvoiceItems(Order $order, bool $consolidate = true, bool $includeOrderTotals = true): array
    {
        $items = [];

        // 步驟 1：拆解主商品和加價購
        foreach ($order->orderProducts as $orderProduct) {
            // 1. 主商品項目
            $items[] = [
                'name' => $orderProduct->name,
                'quantity' => $orderProduct->quantity,
                'price' => $orderProduct->price,
                'subtotal' => $orderProduct->price * $orderProduct->quantity,
                'item_tax_type' => 1,
                'remark' => null,
            ];

            // 2. 加價購選項（需要分開開票）
            if ($orderProduct->orderProductOptions) {
                $paidOptions = $orderProduct->orderProductOptions->filter(function ($option) {
                    return $option->price > 0;  // 只取有加價的選項
                });

                foreach ($paidOptions as $option) {
                    $items[] = [
                        'name' => $option->name . '（' . $option->value . '）',
                        'quantity' => $option->quantity,
                        'price' => $option->price,
                        'subtotal' => $option->subtotal,
                        'item_tax_type' => 1,
                        'remark' => '加購項目',
                    ];
                }
            }
        }

        // 步驟 2：整合相同項目（如果啟用）
        if ($consolidate) {
            $items = $this->consolidateInvoiceItems($items);
        }

        // 步驟 3：處理 orderTotals（運費、折扣、優惠券）
        if ($includeOrderTotals && $order->orderTotals) {
            $totalItems = $this->processOrderTotals($order->orderTotals);
            $items = array_merge($items, $totalItems);
        }

        return $items;
    }

    /**
     * 整合相同的發票項目
     *
     * 整合規則：name + price 相同即合併
     * - 符合發票規定：同品項同單價
     * - 保留完整資訊：名稱、規格清楚
     * - 參考麥當勞實務：品名 + 規格 + 單價 相同才合併
     *
     * @param array $items
     * @return array
     */
    private function consolidateInvoiceItems(array $items): array
    {
        $grouped = [];

        foreach ($items as $item) {
            // 分組鍵：name + price（確保同品項同單價）
            $key = $item['name'] . '|' . $item['price'];

            if (!isset($grouped[$key])) {
                // 第一次出現，直接加入
                $grouped[$key] = $item;
            } else {
                // 已存在，合併數量和小計
                $grouped[$key]['quantity'] += $item['quantity'];
                $grouped[$key]['subtotal'] += $item['subtotal'];
            }
        }

        // 重新索引陣列（移除分組鍵）
        return array_values($grouped);
    }

    /**
     * 處理 orderTotals 為發票項目
     *
     * 處理規則：
     * - shipping（運費）：正數項目
     * - discount（折扣）：轉為負數項目
     * - coupon（優惠券）：轉為負數項目
     * - sub_total、total：忽略（計算值）
     * - 金額為 0 的項目：不列出
     *
     * @param \Illuminate\Database\Eloquent\Collection $orderTotals
     * @return array
     */
    private function processOrderTotals($orderTotals): array
    {
        $items = [];

        // 需要處理的項目類型
        // 注意：運費的 code 可能是 'shipping' 或 'shipping_fee'
        $includeCodes = ['shipping', 'shipping_fee', 'discount', 'coupon'];

        foreach ($orderTotals as $total) {
            // 只處理指定類型
            if (!in_array($total->code, $includeCodes)) {
                continue;
            }

            // 折扣和優惠券項目轉為負數
            $value = $total->value;
            if (in_array($total->code, ['discount', 'coupon'])) {
                // 如果資料庫存的是正數，轉為負數
                if ($value > 0) {
                    $value = -$value;
                }
            }

            // 忽略金額為 0 的項目
            if (abs($value) < 0.01) {
                continue;
            }

            $items[] = [
                'name' => $total->title,
                'quantity' => 1,
                'price' => $value,
                'subtotal' => $value,
                'item_tax_type' => 1,
                'remark' => $this->getRemarkForOrderTotal($total->code),
            ];
        }

        return $items;
    }

    /**
     * 取得 orderTotal 的備註
     *
     * @param string $code
     * @return string|null
     */
    private function getRemarkForOrderTotal(string $code): ?string
    {
        $remarks = [
            'shipping' => '運費',
            'shipping_fee' => '運費',
            'discount' => '折扣優惠',
            'coupon' => '優惠券折抵',
        ];

        return $remarks[$code] ?? null;
    }

    /**
     * 取得發票預設值（create 模式使用）
     *
     * @param Order|null $order 如果有訂單，自動帶入商品明細
     * @return array
     */
    private function getInvoiceDefaults(?Order $order): array
    {
        // 發票預設項目
        $invoiceItems = (new \App\Caches\Custom\Sales\DefaultInvoiceItems())->getData();

        $defaults = [
            'invoice_type' => 'single',        // 單聯式
            'invoice_format' => 'thermal',     // 小張熱感紙
            'invoice_date' => now()->format('Y-m-d'),
            'carrier_type' => 'none',          // 無載具（紙本）
            'tax_type' => 'taxable',           // 應稅
            'tax_included' => 1,               // 含稅
            'invoice_items' => $invoiceItems,  // 預設發票項目
        ];

        // 如果有訂單，自動帶入商品明細（建議項目）
        if ($order) {
            // 確保訂單已載入相關資料
            if (!$order->relationLoaded('orderProducts')) {
                $order->load([
                    'orderProducts' => function ($query) {
                        $query->select('id', 'order_id', 'name', 'price', 'quantity')
                            ->with(['orderProductOptions' => function ($query) {
                                $query->select('id', 'order_product_id', 'name', 'value', 'quantity', 'price', 'subtotal');
                            }]);
                    },
                    'orderTotals'
                ]);
            }

            $defaults['suggest_items'] = $this->splitOrderInvoiceItems($order);

            // 如果訂單有統編，帶入
            if (!empty($order->payment_tin)) {
                $defaults['tax_id_number'] = $order->payment_tin;
                $defaults['invoice_type'] = 'triplicate'; // 有統編改為三聯式
            }

            // 如果訂單有買受人名稱
            if (!empty($order->personal_name)) {
                $defaults['buyer_name'] = $order->personal_name;
            }
        }

        return $defaults;
    }
}
