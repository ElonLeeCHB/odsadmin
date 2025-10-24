<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Sale\InvoiceIssue;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;

/**
 * Giveme 電子發票 API 直接測試控制器
 *
 * 用途：測試 Giveme API 連線功能（前端直接傳送完整資料）
 * 環境：使用測試環境帳號
 * 特點：不操作資料庫，純 API 測試
 *
 * 測試帳號資訊：
 * - 統編: 53418005
 * - 帳號: Giveme09
 * - 密碼: 9VHGCq
 */

/*
  ✅ Giveme 電子發票 API 直接測試路徑

  基礎路徑

  http://ods.dtstw.test/api/posv2/sales/invoice-issue/giveme/data-test

  可用端點

  | 方法   | 路徑         | 說明                                  |
  |------|------------|-------------------------------------|
  | GET  | /config    | 查看當前環境設定                            |
  | GET  | /signature | 測試簽名算法                              |
  | POST | /b2c       | 測試 B2C 發票開立（傳完整資料）                  |
  | POST | /b2b       | 測試 B2B 發票開立（傳完整資料）                  |
  | POST | /query     | 測試發票查詢（需提供 invoice_number）          |
  | POST | /cancel    | 測試發票作廢（需提供 invoice_number 和 reason） |
  | GET  | /print     | 測試發票列印（需提供 code 參數）                |
  | POST | /picture   | 測試發票圖片列印（需提供 code 和 type）          |

  完整 URL 範例

  # 1. 查看設定
  GET http://ods.dtstw.test/api/posv2/sales/invoice-issue/giveme/data-test/config

  # 2. 測試簽名算法
  GET http://ods.dtstw.test/api/posv2/sales/invoice-issue/giveme/data-test/signature

  # 3. 測試 B2C 發票開立
  POST http://ods.dtstw.test/api/posv2/sales/invoice-issue/giveme/data-test/b2c

  # 4. 測試 B2B 發票開立
  POST http://ods.dtstw.test/api/posv2/sales/invoice-issue/giveme/data-test/b2b

  # 5. 測試發票查詢
  POST http://ods.dtstw.test/api/posv2/sales/invoice-issue/giveme/data-test/query
  Body: { "invoice_number": "AB12345678" }

  # 6. 測試發票作廢
  POST http://ods.dtstw.test/api/posv2/sales/invoice-issue/giveme/data-test/cancel
  Body: { "invoice_number": "AB12345678", "reason": "測試作廢" }

  # 7. 測試發票列印（網頁版）
  GET http://ods.dtstw.test/api/posv2/sales/invoice-issue/giveme/data-test/print?code=AB12345678

  # 8. 測試發票圖片列印
  POST http://ods.dtstw.test/api/posv2/sales/invoice-issue/giveme/data-test/picture
  Body: { "code": "AB12345678", "type": "1" }
  type: 1-證明聯+明細, 2-證明聯, 3-明細
*/

class GivemeDataTestController extends ApiPosController
{
    /**
     * API 基礎 URL
     */
    protected string $apiUrl;

    /**
     * 測試環境參數
     */
    protected string $taxId;
    protected string $account;
    protected string $password;

    /**
     * 建構子
     */
    public function __construct()
    {
        parent::__construct();

        $this->apiUrl = config('invoice.giveme.api_url');
        $this->taxId = config('invoice.test.tax_id');
        $this->account = config('invoice.test.account');
        $this->password = config('invoice.test.password');
    }

    /**
     * 測試簽名算法
     *
     * GET /api/pos/v2/invoice-issue/giveme/test/signature
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function testSignature()
    {
        try {
            $timeStamp = round(microtime(true) * 1000);

            // 計算簽名
            $signString = $timeStamp . $this->account . $this->password;
            $sign = strtoupper(md5($signString));

            return response()->json([
                'success' => true,
                'message' => '簽名算法測試',
                'data' => [
                    'timeStamp' => $timeStamp,
                    'taxId' => $this->taxId,
                    'account' => $this->account,
                    'sign_string' => $signString,
                    'sign' => $sign,
                ],
                'note' => '簽名有效期 5 分鐘',
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $th) {
            return $this->sendJsonErrorResponse(
                data: ['error' => $th->getMessage()],
                status_code: 500
            );
        }
    }

    /**
     * 測試 B2C 發票開立
     *
     * POST /api/pos/v2/invoice-issue/giveme/test/b2c
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    /* json 範例
  {
    "customerName": "測試公司",
    "phone": "/XYZ5678",
    "datetime": "2025-10-24",
    "email": "test@company.com",
    "state": "0",
    "taxType": 0,
    "totalFee": "550",
    "content": "月結帳單",
    "items": [
      {
        "name": "珍珠奶茶",
        "money": 200,
        "number": 2,
        "remark": ""
      },
      {
        "name": "雞排",
        "money": 150,
        "number": 1,
        "remark": "不辣"
      }
    ]
  }
    */
    public function testB2C(Request $request)
    {
        try {
            $timeStamp = round(microtime(true) * 1000);
            $sign = strtoupper(md5($timeStamp . $this->account . $this->password));

            // 取得前端傳入的參數
            $inputData = $request->all();

            // 如果前端沒有提供參數，使用預設值
            if (empty($inputData)) {
                $data = [
                    'customerName' => '測試客戶',
                    'phone' => '/ABC1234',  // 手機條碼
                    'datetime' => date('Y-m-d'),
                    'email' => 'test@example.com',
                    'state' => '0',  // 0-無捐贈, 1-捐贈
                    'taxType' => 0,  // 0-應稅
                    'totalFee' => '100',
                    'content' => '測試發票',
                    'items' => [
                        [
                            'name' => '測試商品A',
                            'money' => 50,
                            'number' => 1,
                            'remark' => '',
                        ],
                        [
                            'name' => '測試商品B',
                            'money' => 50,
                            'number' => 1,
                            'remark' => '',
                        ],
                    ],
                ];
            } else {
                // 使用前端提供的參數
                $data = $inputData;
            }

            // 組裝請求資料
            $requestData = [
                'timeStamp' => (string)$timeStamp,
                'uncode' => $this->taxId,
                'idno' => $this->account,
                'sign' => $sign,
                'customerName' => $data['customerName'],
                'phone' => $data['phone'],
                'datetime' => $data['datetime'],
                'email' => $data['email'],
                'state' => $data['state'],
                'taxType' => $data['taxType'],
                'totalFee' => $data['totalFee'],
                'content' => $data['content'],
                'items' => $data['items'],
            ];

            Log::info('Giveme B2C Test Request', ['request' => $requestData]);

            // 發送請求（測試環境跳過 SSL 驗證）
            $response = Http::timeout(30)
                ->withoutVerifying()  // 跳過 SSL 憑證驗證（僅用於開發/測試環境）
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . '?action=addB2C', $requestData);

            $responseData = $response->json();

            Log::info('Giveme B2C Test Response', [
                'status' => $response->status(),
                'response' => $responseData,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'B2C 發票開立測試',
                'request' => $requestData,
                'response' => $responseData,
                'http_status' => $response->status(),
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $th) {
            Log::error('Giveme B2C Test Error', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return $this->sendJsonErrorResponse(
                data: ['error' => $th->getMessage()],
                status_code: 500
            );
        }
    }

    /**
     * 測試 B2B 發票開立
     *
     * POST /api/pos/v2/invoice-issue/giveme/test/b2b
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    /* json 範例
  {
    "customerName": "XYZ企業有限公司",
    "phone": "12345678",
    "datetime": "2025-10-23",
    "email": "admin@xyz.com.tw",
    "taxState": "1",
    "totalFee": "1050",
    "amount": "50",
    "sales": "1000",
    "taxType": 0,
    "content": "辦公用品採購",
    "items": [
      {
        "name": "辦公桌椅",
        "money": 500.00,
        "number": 2,
        "remark": ""
      }
    ]
  }
    */
    public function testB2B(Request $request)
    {
        try {
            $timeStamp = round(microtime(true) * 1000);
            $sign = strtoupper(md5($timeStamp . $this->account . $this->password));

            // 取得前端傳入的參數
            $inputData = $request->all();

            // 如果前端沒有提供參數，使用預設值
            if (empty($inputData)) {
                $data = [
                    'customerName' => '測試公司',
                    'phone' => '12345678',  // B2B 的 phone 是買方統編
                    'datetime' => date('Y-m-d'),
                    'email' => 'company@example.com',
                    'taxState' => '0',  // 0-含稅, 1-未稅
                    'totalFee' => '525',
                    'amount' => '25',  // 稅額
                    'sales' => '500',  // 未稅金額
                    'taxType' => 0,  // 0-應稅
                    'content' => '測試 B2B 發票',
                    'items' => [
                        [
                            'name' => '測試商品',
                            'money' => 250.00,
                            'number' => 2,
                            'remark' => '',
                        ],
                    ],
                ];
            } else {
                // 使用前端提供的參數
                $data = $inputData;
            }

            // 組裝請求資料
            $requestData = [
                'timeStamp' => (string)$timeStamp,
                'uncode' => $this->taxId,
                'idno' => $this->account,
                'sign' => $sign,
                'customerName' => $data['customerName'],
                'phone' => $data['phone'],
                'datetime' => $data['datetime'],
                'email' => $data['email'],
                'taxState' => $data['taxState'],
                'totalFee' => $data['totalFee'],
                'amount' => $data['amount'],
                'sales' => $data['sales'],
                'taxType' => $data['taxType'],
                'content' => $data['content'],
                'items' => $data['items'],
            ];

            Log::info('Giveme B2B Test Request', ['request' => $requestData]);

            // 發送請求（測試環境跳過 SSL 驗證）
            $response = Http::timeout(30)
                ->withoutVerifying()  // 跳過 SSL 憑證驗證（僅用於開發/測試環境）
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . '?action=addB2B', $requestData);

            $responseData = $response->json();

            Log::info('Giveme B2B Test Response', [
                'status' => $response->status(),
                'response' => $responseData,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'B2B 發票開立測試',
                'request' => $requestData,
                'response' => $responseData,
                'http_status' => $response->status(),
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $th) {
            Log::error('Giveme B2B Test Error', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return $this->sendJsonErrorResponse(
                data: ['error' => $th->getMessage()],
                status_code: 500
            );
        }
    }

    /**
     * 測試發票查詢
     *
     * POST /api/pos/v2/invoice-issue/giveme/test/query
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testQuery(Request $request)
    {
        try {
            $invoiceNumber = $request->input('invoice_number', '');

            if (empty($invoiceNumber)) {
                return response()->json([
                    'success' => false,
                    'message' => '請提供發票號碼',
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            $timeStamp = round(microtime(true) * 1000);
            $sign = strtoupper(md5($timeStamp . $this->account . $this->password));

            // 組裝請求資料
            $requestData = [
                'timeStamp' => (string)$timeStamp,
                'uncode' => $this->taxId,
                'idno' => $this->account,
                'sign' => $sign,
                'code' => $invoiceNumber,
            ];

            Log::info('Giveme Query Test Request', ['request' => $requestData]);

            // 發送請求（測試環境跳過 SSL 驗證）
            $response = Http::timeout(30)
                ->withoutVerifying()  // 跳過 SSL 憑證驗證（僅用於開發/測試環境）
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . '?action=query', $requestData);

            $responseData = $response->json();

            Log::info('Giveme Query Test Response', [
                'status' => $response->status(),
                'response' => $responseData,
            ]);

            return response()->json([
                'success' => true,
                'message' => '發票查詢測試',
                'request' => $requestData,
                'response' => $responseData,
                'http_status' => $response->status(),
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $th) {
            Log::error('Giveme Query Test Error', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return $this->sendJsonErrorResponse(
                data: ['error' => $th->getMessage()],
                status_code: 500
            );
        }
    }

    /**
     * 測試發票作廢
     *
     * POST /api/pos/v2/invoice-issue/giveme/test/cancel
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testCancel(Request $request)
    {
        try {
            $invoiceNumber = $request->input('invoice_number', '');
            $reason = $request->input('reason', '測試作廢');

            if (empty($invoiceNumber)) {
                return response()->json([
                    'success' => false,
                    'message' => '請提供發票號碼',
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            $timeStamp = round(microtime(true) * 1000);
            $sign = strtoupper(md5($timeStamp . $this->account . $this->password));

            // 組裝請求資料
            $requestData = [
                'timeStamp' => (string)$timeStamp,
                'uncode' => $this->taxId,
                'idno' => $this->account,
                'sign' => $sign,
                'code' => $invoiceNumber,
                'remark' => $reason,
            ];

            Log::info('Giveme Cancel Test Request', ['request' => $requestData]);

            // 發送請求（測試環境跳過 SSL 驗證）
            $response = Http::timeout(30)
                ->withoutVerifying()  // 跳過 SSL 憑證驗證（僅用於開發/測試環境）
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . '?action=cancelInvoice', $requestData);

            $responseData = $response->json();

            Log::info('Giveme Cancel Test Response', [
                'status' => $response->status(),
                'response' => $responseData,
            ]);

            return response()->json([
                'success' => true,
                'message' => '發票作廢測試',
                'request' => $requestData,
                'response' => $responseData,
                'http_status' => $response->status(),
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $th) {
            Log::error('Giveme Cancel Test Error', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return $this->sendJsonErrorResponse(
                data: ['error' => $th->getMessage()],
                status_code: 500
            );
        }
    }

    /**
     * 測試發票列印（網頁版）
     *
     * GET /api/pos/v2/invoice-issue/giveme/data-test/print
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testPrint(Request $request)
    {
        try {
            $invoiceNumber = $request->input('code', '');

            if (empty($invoiceNumber)) {
                return response()->json([
                    'success' => false,
                    'message' => '請提供發票號碼 (code)',
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            // 組裝列印 URL（根據文檔，這是 GET 請求，不需要簽名）
            $printUrl = $this->apiUrl . '?action=invoicePrint&code=' . urlencode($invoiceNumber) . '&uncode=' . urlencode($this->taxId);

            Log::info('Giveme Print Test Request', [
                'invoice_number' => $invoiceNumber,
                'print_url' => $printUrl,
            ]);

            // 發送請求（測試環境跳過 SSL 驗證）
            $response = Http::timeout(30)
                ->withoutVerifying()  // 跳過 SSL 憑證驗證（僅用於開發/測試環境）
                ->get($printUrl);

            // 判斷是否返回 HTML 或 JSON
            $contentType = $response->header('Content-Type');

            if (strpos($contentType, 'application/json') !== false) {
                $responseData = $response->json();

                Log::info('Giveme Print Test Response (JSON)', [
                    'status' => $response->status(),
                    'response' => $responseData,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => '發票列印測試（JSON 回應）',
                    'print_url' => $printUrl,
                    'response' => $responseData,
                    'http_status' => $response->status(),
                ], 200, [], JSON_UNESCAPED_UNICODE);
            } else {
                // 返回 HTML 內容
                Log::info('Giveme Print Test Response (HTML)', [
                    'status' => $response->status(),
                    'content_type' => $contentType,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => '發票列印測試（HTML 回應）',
                    'print_url' => $printUrl,
                    'note' => '請直接在瀏覽器中開啟 print_url 查看發票列印頁面',
                    'http_status' => $response->status(),
                    'content_type' => $contentType,
                ], 200, [], JSON_UNESCAPED_UNICODE);
            }

        } catch (\Throwable $th) {
            Log::error('Giveme Print Test Error', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return $this->sendJsonErrorResponse(
                data: ['error' => $th->getMessage()],
                status_code: 500
            );
        }
    }

    /**
     * 測試發票圖片列印
     *
     * POST /api/pos/v2/invoice-issue/giveme/data-test/picture
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testPicture(Request $request)
    {
        try {
            $invoiceNumber = $request->input('code', '');
            $type = $request->input('type', '1');  // 1-證明聯+明細, 2-證明聯, 3-明細

            if (empty($invoiceNumber)) {
                return response()->json([
                    'success' => false,
                    'message' => '請提供發票號碼 (code)',
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            if (!in_array($type, ['1', '2', '3'])) {
                return response()->json([
                    'success' => false,
                    'message' => '圖片類型錯誤，請選擇 1-證明聯+明細, 2-證明聯, 3-明細',
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            $timeStamp = round(microtime(true) * 1000);
            $sign = strtoupper(md5($timeStamp . $this->account . $this->password));

            // 組裝請求資料
            $requestData = [
                'timeStamp' => (string)$timeStamp,
                'uncode' => $this->taxId,
                'idno' => $this->account,
                'sign' => $sign,
                'code' => $invoiceNumber,
                'type' => $type,
            ];

            Log::info('Giveme Picture Test Request', ['request' => $requestData]);

            // 發送請求（測試環境跳過 SSL 驗證）
            $response = Http::timeout(30)
                ->withoutVerifying()  // 跳過 SSL 憑證驗證（僅用於開發/測試環境）
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . '?action=picture', $requestData);

            $contentType = $response->header('Content-Type');

            // 判斷回應類型
            if (strpos($contentType, 'application/json') !== false) {
                $responseData = $response->json();

                Log::info('Giveme Picture Test Response (JSON)', [
                    'status' => $response->status(),
                    'response' => $responseData,
                ]);

                return response()->json([
                    'success' => $responseData['success'] ?? false,
                    'message' => '發票圖片測試（錯誤回應）',
                    'request' => $requestData,
                    'response' => $responseData,
                    'http_status' => $response->status(),
                ], 200, [], JSON_UNESCAPED_UNICODE);
            } else {
                // 返回圖片流
                Log::info('Giveme Picture Test Response (Image)', [
                    'status' => $response->status(),
                    'content_type' => $contentType,
                    'content_length' => strlen($response->body()),
                ]);

                // 將圖片轉為 base64
                $imageBase64 = base64_encode($response->body());

                return response()->json([
                    'success' => true,
                    'message' => '發票圖片測試（成功）',
                    'request' => $requestData,
                    'http_status' => $response->status(),
                    'content_type' => $contentType,
                    'image_base64' => $imageBase64,
                    'note' => '請將 image_base64 解碼後顯示或儲存為圖片',
                ], 200, [], JSON_UNESCAPED_UNICODE);
            }

        } catch (\Throwable $th) {
            Log::error('Giveme Picture Test Error', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return $this->sendJsonErrorResponse(
                data: ['error' => $th->getMessage()],
                status_code: 500
            );
        }
    }

    /**
     * 查看當前環境設定
     *
     * GET /api/pos/v2/invoice-issue/giveme/test/config
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function showConfig()
    {
        return response()->json([
            'success' => true,
            'message' => 'Giveme API 測試環境設定',
            'config' => [
                'api_url' => $this->apiUrl,
                'tax_id' => $this->taxId,
                'account' => $this->account,
                'password' => str_repeat('*', strlen($this->password)),  // 隱藏密碼
            ],
            'note' => '使用測試環境帳號',
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
