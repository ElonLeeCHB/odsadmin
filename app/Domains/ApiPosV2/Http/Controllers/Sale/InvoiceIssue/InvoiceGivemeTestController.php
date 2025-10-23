<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Sale\InvoiceIssue;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;

/**
 * Giveme 電子發票 API 測試控制器
 *
 * 用途：測試 Giveme API 串接功能
 * 環境：使用測試環境帳號
 *
 * 測試帳號資訊：
 * - 統編: 53418005
 * - 帳號: Giveme09
 * - 密碼: 9VHGCq
 */

/*
  ✅ Giveme 電子發票測試 API 路徑

  基礎路徑

  http://ods.dtstw.test/api/posv2/sales/invoice-issue/giveme/test

  可用端點

  | 方法   | 路徑         | 說明                                  |
  |------|------------|-------------------------------------|
  | GET  | /config    | 查看當前環境設定                            |
  | GET  | /signature | 測試簽名算法                              |
  | POST | /b2c       | 測試 B2C 發票開立                         |
  | POST | /b2b       | 測試 B2B 發票開立                         |
  | POST | /query     | 測試發票查詢（需提供 invoice_number）          |
  | POST | /cancel    | 測試發票作廢（需提供 invoice_number 和 reason） |

  完整 URL 範例

  # 1. 查看設定
  GET http://ods.dtstw.test/api/posv2/sales/invoice-issue/giveme/test/config

  # 2. 測試簽名算法
  GET http://ods.dtstw.test/api/posv2/sales/invoice-issue/giveme/test/signature

  # 3. 測試 B2C 發票開立
  POST http://ods.dtstw.test/api/posv2/sales/invoice-issue/giveme/test/b2c

  # 4. 測試 B2B 發票開立
  POST http://ods.dtstw.test/api/posv2/sales/invoice-issue/giveme/test/b2b

  # 5. 測試發票查詢
  POST http://ods.dtstw.test/api/posv2/sales/invoice-issue/giveme/test/query
  Body: { "invoice_number": "AB12345678" }

  # 6. 測試發票作廢
  POST http://ods.dtstw.test/api/posv2/sales/invoice-issue/giveme/test/cancel
  Body: { "invoice_number": "AB12345678", "reason": "測試作廢" }
*/

class InvoiceGivemeTestController extends ApiPosController
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
