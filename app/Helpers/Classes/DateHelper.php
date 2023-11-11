<?php

namespace App\Helpers\Classes;

class DateHelper
{

    /**
     * 2023-11-08
     * $data: array or string
     */
    public static function parseDate($dateString)
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

            $validDateString = date('Y-m-d', strtotime($date1String));

            if($validDateString != $date1String){
                return false;
            }

            $validDateString = date('Y-m-d', strtotime($date2String));

            if($validDateString != $date2String){
                return false;
            }

            return ['data' => [$date1String, $date2String]];

        }
        //單一日期
        else{
    
            if(preg_match('/(^\d{2,4}-\d{2}-\d{2}$)/', $dateString, $matches)){ //2023-05-01
                $arr = explode('-', $dateString);
                $date1_year = $arr[0] < 2000 ? $arr[0]+2000 : $arr[0];
                $date1String = $date1_year . '-' . $arr[1] . '-' . $arr[2];
            }else if(preg_match('/(^\d{6,8}$)/', $dateString, $matches)){ //230501, 0230501, 20230501
                $date1_year = substr($dateString, 0, -4);
                $date1_year = $date1_year < 2000 ? $date1_year+2000 : $date1_year;
                $date1String = $date1_year . '-' . substr($dateString, -4, -2) . '-' . substr($dateString, -2);
            }

            $validDateString = date('Y-m-d', strtotime($date1String));

            if($validDateString != $date1String){
                return false;
            }

            return ['data' => [$date1String]];
        }



    }

}