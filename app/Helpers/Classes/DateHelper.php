<?php

namespace App\Helpers\Classes;

class DateHelper
{

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

    function isValidDate($dateString, $format = 'Y-m-d') {
        $dateTime = \DateTime::createFromFormat($format, $dateString);
        return $dateTime && $dateTime->format($format) === $dateString;
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

    public static function xxparseDiffDays($start, $end){

        $start_timestamp = strtotime($start);
        $end_timestamp = strtotime($end);

        $days_diff = ceil(($start_timestamp - $end_timestamp) / (60 * 60 * 24));

        return $days_diff;
    }
}