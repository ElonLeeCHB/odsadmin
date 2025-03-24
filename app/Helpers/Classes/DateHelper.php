<?php

namespace App\Helpers\Classes;

class DateHelper
{
    /**
     * 解析日期，取得合法的日期字串 yyyy-mm-dd
     * 可以輸入兩碼西元年加上月日，可以有橫線或斜線
     * 例如 20240101, 240101, 2024-01-01, 24-01-01
     *
     * @param string $dateStr
     * @return string
     */
    public static function parse($dateStr)
    {
        //連續數字: 20231201, 231201
        if(is_numeric($dateStr)){
            if(strlen($dateStr) == 6){
                $date = \DateTime::createFromFormat('ymd', $dateStr);
                $dateYmd = $date->format('Y-m-d');
            }
            else if(strlen($dateStr) == 8){
                $date = \DateTime::createFromFormat('Ymd', $dateStr);
                $dateYmd = $date->format('Y-m-d');
            }
        }

        //有斜線或橫線: 2023-12-01, 23-12-01
        else{
            $dateStr = str_replace('/', '',$dateStr);
            $dateStr = str_replace('-', '',$dateStr);

            //2023-12-01, 23-12-01
            if(strlen($dateStr) == 6){
                $date = \DateTime::createFromFormat('ymd', $dateStr);
            }
            else if(strlen($dateStr) == 8){
                $date = \DateTime::createFromFormat('Ymd', $dateStr);
            }
            else if(strlen($dateStr) == 10){
                // do nothing.
            }

            $dateYmd = $date->format('Y-m-d');
        }

        $validDateString = date('Y-m-d', strtotime($dateYmd));

        if($validDateString == $dateYmd){
            return $dateYmd;
        }

        return false;
    }

    /**
     * 2023-11-13
     */
    public static function parseDate($dateString)
    {
        if(strlen($dateString) > 10){
            return ['error' => 'datestring too long!'];
        }

        if(preg_match('/(^\d{2,4}-\d{2}-\d{2}$)/', $dateString, $matches)){ //2023-05-01
            $arr = explode('-', $dateString);
            $date_year = $arr[0] < 2000 ? $arr[0]+2000 : $arr[0];
            $dateString = $date_year . '-' . $arr[1] . '-' . $arr[2];
        }else if(preg_match('/(^\d{6,8}$)/', $dateString, $matches)){ //230501, 0230501, 20230501
            $date_year = substr($dateString, 0, -4);
            $date_year = $date_year < 2000 ? $date_year+2000 : $date_year;
            $dateString = $date_year . '-' . substr($dateString, -4, -2) . '-' . substr($dateString, -2);
        }

        $validDateString = date('Y-m-d', strtotime($dateString));

        if($validDateString != $dateString){
            return false;
        }

        return $dateString;
    }

    /**
     * 2023-11-08
     * $data: array or string
     */
    public static function parseDateOrPeriod($dateString)
    {
        $dateString = trim($dateString);

        // 只允許數字或-或/或:
        if(!preg_match('/^[0-9\-\/:]+$/', $dateString, $matches)){
            return false;
        }

        $date1 = null;
        $date2 = null;

        // 日期區間
        if(strlen($dateString) > 10){
            $dateString = str_replace('/','-',$dateString); //"2023/05/01:2023/05/31" change to "2023-05-01-2023-05-31"
            $dateString = str_replace(':','-',$dateString); //"2023-05-01:2023-05-31" change to "2023-05-01-2023-05-31"
            $count = substr_count($dateString, '-');

            $arr = explode('-', $dateString);

            // 整串只有1個橫線作為兩個日期的分隔
            if($count == 1){

                $date1_year = substr($arr[0], 0, -4);
                if($date1_year < 2000){
                    $date1_year += 2000;
                }

                $date2_year = substr($arr[1], 0, -4);
                if($date2_year < 2000){
                    $date2_year += 2000;
                }

                $date1String = $date1_year . '-' . substr($arr[0], -4, -2) . '-' . substr($arr[0], -2);
                $date2String = $date2_year . '-' . substr($arr[1], -4, -2) . '-' . substr($arr[1], -2);

            }else{
                $date1_year = $arr[0] < 2000 ? $arr[0]+2000 : $arr[0];
                $date1String = $date1_year . '-' . $arr[1] . '-' . $arr[2];

                $date2_year = $arr[0] < 2000 ? $arr[0]+2000 : $arr[0];
                $date2String = $date2_year . '-' . $arr[4] . '-' . $arr[5];
            }

            // validate date1
            $validDateString = date('Y-m-d', strtotime($date1String));

            if($validDateString != $date1String){
                return ['error' => 'parse error!'];
            }

            // validate date2
            $validDateString = date('Y-m-d', strtotime($date2String));

            if($validDateString != $date2String){
                return ['error' => 'parse error!'];
            }

            return ['data' => [$date1String, $date2String]];

        }
        //單一日期
        else{
            $date1String = self::parseDate($dateString);
            return ['data' => [$date1String, ]];
        }



    }

    /**
     * 創建日期: 2024-12-17
     * 驗證日期與時間是否合法
     * 驗證字串 "2024-12-25" 或 "2024-12-25 13:00:00"
     */
    public static function isValid($inputString)
    {
        // 定義允許的格式
        $formats = [
            'Y-m-d',
            'Y-m-d H:i',
            'Y-m-d H:i:s'
        ];
    
        foreach ($formats as $format) {
            $dateTime = \DateTime::createFromFormat($format, $inputString);
            if ($dateTime && $dateTime->format($format) === $inputString) {
                return true;
            }
        }
    
        return false;
    }
    

    /**
     * 2023-11-13
     * Y-m-d
     */
    public static function parseDiffDays($startString, $endString)
    {
        $date1 = new \DateTime($startString);
        $date2 = new \DateTime($endString);

        $interval = $date1->diff($date2);

        $daysDifference = $interval->days;

        if ($date1 < $date2) {
            $daysDifference *= -1;
        }

        return $daysDifference;
    }


    public static function parseDateStringTo6d($dateString)
    {
        $dateYmd = parseDate($dateString); // yyyy-mm-dd

        if($dateYmd){
            preg_match_all('/\d+/', $dateString, $matches);
            $dateString = implode('', $matches[0]);
            $date2ymd = substr($dateString, -6);
        }

        if(!empty($date2ymd)){
            return $date2ymd;
        }else{
            return false;
        }
    }

    /**
     * 20231201-20231231
     * 231201-231231
     * 2023-12-01-2023-12-31
     * 23-05-01-23-12-31
     *
     * 20231201-
     * -231231
     * 2023-12-01-
     * -23-12-31
     */
    public static function parseDateToSqlWhere($column, $dateStr)
    {
        $dateStr = trim($dateStr);

        $arr = explode('-', $dateStr);

        //20231201-20231231, 231201-231231
        if(count($arr) == 2 && !empty($arr[0]) && !empty($arr[1])){
            $startYmd = self::parse($arr[0]);
            $endYmd = self::parse($arr[1]);
        }
        //2023-12-01-2023-12-31, 23-05-01-23-12-31
        else if(count($arr) == 6){
            $startYmd = $arr[0] . $arr[1] . $arr[2];
            $startYmd = self::parse($startYmd);

            $endYmd = $arr[3] . $arr[4] . $arr[5];
            $endYmd = self::parse($endYmd);
        }
        //2023-12-01-, 23-05-01-, 20231201-, 231201-
        else if (substr($dateStr, -1) === '-') {
            $startYmd = rtrim($dateStr, '-');
            $startYmd = self::parse($startYmd);
        }
        //-2023-12-01, -23-05-01, -20231201, -231201
        else if(str_starts_with($dateStr, '-')){
            $endYmd = ltrim($dateStr, '-');
            $endYmd = self::parse($endYmd);
        }
        //2023-12-01, 23-05-01
        else if(count($arr) == 3){
            $singleYmd = $arr[0] . $arr[1] . $arr[2];
            $singleYmd = self::parse($singleYmd);
        }
        //20231201, 230501
        else if(is_numeric($dateStr)){
            $singleYmd = self::parse($dateStr);
        }

        // 日期區間
        if(!empty($startYmd) && !empty($endYmd)){
            return "`$column` BETWEEN '$startYmd' AND '$endYmd'";
        }
        // 只有開始日期
        else if(!empty($startYmd) && empty($endYmd)){
            return "`$column` >= '$startYmd'";
        }
        // 只有結束日期
        else if(empty($startYmd) && !empty($endYmd)){
            return "`$column` <= '$endYmd'";
        }
        // 只有單一日期
        else if(!empty($singleYmd)){
            $newDate = new \DateTime($singleYmd);
            $newDateYmd = $newDate->modify('+1 day')->format('Y-m-d');
            return "$column >= '$singleYmd' AND $column < '$newDateYmd'";
        }

        return false;
    }

    public static function addColon($str)
    {
        // 移除空格，並將 ~ 轉換為 -
        $str = str_replace([' ', '~'], '-', $str);
    
        // 分割起始和結束時間
        $times = explode('-', $str);
    
        // 檢查格式是否正確
        if (count($times) == 2) {
            // 檢查每個時間字串的長度
            $start_time = $times[0];
            $end_time = $times[1];
    
            // 格式化時間
            if (strlen($start_time) == 4 && strlen($end_time) == 4) {
                // 若格式是 1100 - 1200，則轉換為 11:00 - 12:00
                $start_time = substr($start_time, 0, 2) . ':' . substr($start_time, 2, 2);
                $end_time = substr($end_time, 0, 2) . ':' . substr($end_time, 2, 2);
            } elseif (strlen($start_time) == 6 && strlen($end_time) == 6) {
                // 若格式是 110000 - 120000，則轉換為 11:00:00 - 12:00:00
                $start_time = substr($start_time, 0, 2) . ':' . substr($start_time, 2, 2) . ':' . substr($start_time, 4, 2);
                $end_time = substr($end_time, 0, 2) . ':' . substr($end_time, 2, 2) . ':' . substr($end_time, 4, 2);
            }
    
            // 回傳格式化的時間範圍
            return $start_time . '-' . $end_time;
        }
    
        // 若時間格式不正確，回傳 null 或其他錯誤處理
        return null;
    }
}