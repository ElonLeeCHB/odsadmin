<?php

namespace App\Domains\Admin\Http\Controllers\Tools;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\SysData\GovUniformInvoiceNumbers;

/**
 * 資料來源：https://data.gov.tw/dataset/9400
 * 下載後是 zip 壓縮檔，解壓縮之後得到 csv 檔。目前有160萬筆(1600,419)
 * 要全部匯入資料庫。
 * 打算：按最後兩碼，用cache儲存。
 * 目前第二行的字串是 10-APR-23，而今天是4月11日，剛好昨天更新？
 *
 */

 /* 解壓縮之後拿到的 csv, 前兩行是欄位，及更新日期
營業地址,統一編號,總機構統一編號,營業人名稱,資本額,設立日期,組織別名稱,使用統一發票,行業代號,名稱,行業代號1,名稱1,行業代號2,名稱2,行業代號3,名稱3
10-APR-23,,,,,,,,,,,,,,,
"南投縣中寮鄉中寮村永平路３７１號一樓",38965019,,"原味商行",100000,1040413,獨資,N,472927,豆類製品零售,,,,,,
"南投縣中寮鄉中寮村鄉林巷４３號",61194605,,"和興商店",1000,0400711,獨資,N,472913,菸酒零售,471913,雜貨店,,,,
 */

class UniformInvoiceNumberController extends Controller
{
    public function getForm()
    {
        return view('admin.tools.uniform_invoice_number');
    }

    public function parse(Request $request)
    {
        //D:\Codes\PHP\BGMOPEN1.csv

        ini_set('max_execution_time', 30000);

        // 設定原始 CSV 檔案的路徑和檔名
        $csvFilePath = $request->post('path');
        $csvFilePath = str_replace('\\', '/', $csvFilePath);

        // 建立 SplFileObject 實例
        $csvFile = new \SplFileObject($csvFilePath, 'r');
        $csvFile->setCsvControl(',', '"');

        //欄位名稱
        $csvFile->seek(0);
        $headerCht = $csvFile->fgetcsv();
        $headerCht[] = '更新時間';

        //更新日期
        // 使用 seek 方法移動到指定的行數
        $csvFile->seek(1);
        // 取得指定行數的資料
        $rowData = $csvFile->fgetcsv();
        $dateString  = $rowData[0];
        $updateDate = \DateTime::createFromFormat('d-M-y', $dateString);

        //重要！！ csv檔第一行的欄位名稱與資料庫欄位名稱的對應
        $column_map = [
            '營業地址' => 'address',
            '統一編號' => 'tax_id_num',
            '總機構統一編號' => 'headquarter_uin',
            '營業人名稱' => 'name',
            '資本額' => 'capital_amount',
            '設立日期' => 'incorporation_date',
            '組織別名稱' => 'type_name',
            '使用統一發票' => 'is_invoice',
            '行業代號' => 'industry1_code',
            '名稱' => 'industry1_name',
            '行業代號1' => 'industry2_code',
            '名稱1' => 'industry2_name',
            '行業代號2' => 'industry3_code',
            '名稱2' => 'industry3_name',
            '行業代號3' => 'industry4_code',
            '名稱3' => 'industry4_name',
            '更新時間' => 'source_update_date'
        ];

        foreach ($column_map as $key => $value) {
            $columns[] = [
                0 => $key,
                1 => $value,
            ];
            $header[] = $value;
        }

        // 每個回合包含的最大筆數。 1000筆會卡住，50筆跟100好像差不多。
        $maxRowsPerRound = 100;

        // 計數器，用於計算目前已經處理的行數
        $rowCount = 0; //分割檔裡面的筆數
        $totalRowProcessed = 0; //全部已處理的筆數
        $roundCount = 0;

        $skip = 1600000; //開頭非資料行要略過。至少=1
        $toMax = $skip+100002; //一次十萬筆可跑完。二十萬筆以上會變成白頁。

        echo "從第 $skip 開始<BR>\r\n";

        // 從第3行開始，迴圈讀取 CSV 檔案的每一行
        $csvFile->seek($skip);
        while (!$csvFile->eof()) {

            // 計數器增加 行號從1開始
            $rowCount++;
            $totalRowProcessed++;

            //跳過
            if($totalRowProcessed <= $skip ){
                continue;
            }

            // 強制中斷
            if($totalRowProcessed > $toMax){
                break;
            }

            // 依序取得每一筆資料，key由0遞增
            $row = $csvFile->fgetcsv();

            if(empty($row[0])){
                break;
            }
            $row[0] = mb_convert_kana($row[0], "n"); //地址

            //設立日期 民國年轉換西元年
            if(!empty($row[5])){
                $incorporation_date = $row[5];
                $year = substr($incorporation_date,0,3);
                if(is_numeric($year)){
                    $row[5] = $year+1911 . '-' . substr($incorporation_date,3,2) . '-' . substr($incorporation_date,5,2);
                }

            }

            $row[] = $updateDate; //最後一個欄位塞入原始檔第2行的日期

            // 使用欄位名稱和索引結合，取得每一個欄位的值。key=欄位名稱
            $rowData = array_combine($header, $row);

            $insertData[] = $rowData;

            if (!empty($insertData) && count($insertData) >= $maxRowsPerRound) {
                $roundCount++;
                GovUniformInvoiceNumbers::upsert($insertData, ['tax_id_num']);
                $insertData = [];
            }
        }

        //剩餘筆數，不足最大，未被清空
        if (!empty($insertData)) {
           GovUniformInvoiceNumbers::upsert($insertData, ['tax_id_num']);
           $insertData = [];
        }

    }
}
