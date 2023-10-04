<?php

if(!function_exists('zhChtToChs')){
    function zhChtToChs($input){
        if(trim($input)==''){ //输入为空则返回空字符串
            return ''; 
        }

        $array = include_once(base_path() .'/resources/others/zhCharChtToChsArray.php');

        $output = ''; 
        $count = mb_strlen($input,'utf-8'); //按照utf-8字符计数
        for($i = 0; $i <= $count; $i++){ //逐个字符处理
            $jchar = mb_substr($input,$i,1,'utf-8'); //分离出一个需要处理的字符
            $fchar = isset($array[$jchar])?$array[$jchar]:$jchar; //如果在上面的对照数组中就转换，否则原样不变
            $output .= $fchar; //逐个字符添加到输出
        } 
        return $output;//返回输出
    }
}

if(!function_exists('zhChsToCht')){
    function zhChsToCht($input){
        //$array = array_flip($array); //如果需要繁体到简体的转换，只需要用一个array_flip函数来对调key和value
        if(trim($input)==''){ //输入为空则返回空字符串
            return ''; 
        }
    
        $array = include_once(base_path() .'/resources/others/zhCharChsToChtArray.php');
    
        $output = ''; 
        $count = mb_strlen($input,'utf-8'); //按照utf-8字符计数
        for($i = 0; $i <= $count; $i++){ //逐个字符处理
            $jchar = mb_substr($input,$i,1,'utf-8'); //分离出一个需要处理的字符
            $fchar = isset($array[$jchar])?$array[$jchar]:$jchar; //如果在上面的对照数组中就转换，否则原样不变
            $output .= $fchar; //逐个字符添加到输出
        } 
        return $output;//返回输出
    }
}



/**
 * $dateString = '2023-05-01', '20230501', '23-05-01', '230501'
 * Return yyyy-mm-dd
 * From 1971-0101 to 2070-12-31
 * if 710101, means 1971-01-01, if 700101 means 2070-01-01
 */
if(!function_exists('parseDate')){
    function parseDate(String $dateString)
    {
        // normal date
        $pattern = "/^\d{4}[-\/]\d{2}[-\/]\d{2}$/";
        if (preg_match($pattern, $dateString)) {
            $strYmd = $dateString;
        }
        // 20230501
        else if (preg_match('/\d{8}/', $dateString, $matches)) {
            $strYmd = substr($matches[0],0,4) . '-' . substr($matches[0],4,2) . '-' . substr($matches[0],6,2);
        }
        // 230501
        else if (preg_match('/\d{6}/', $dateString, $matches)) {
            $strYearLast2 = substr($dateString,0,2);
            $strYear = ($strYearLast2 > 70) ? (1900+$strYearLast2) : (2000+$strYearLast2);
            $strYmd = $strYear . '-' . substr($dateString,2,2) . '-' . substr($dateString,4,2);
        }else{
            $strYmd = false;
        }

        return $strYmd;
    }
}

// '2023-03-27' or '2023-03-27 00:00:00' to 230327
if(!function_exists('parseDateStringTo6d')){
    function parseDateStringTo6d($dateString)
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
}

if(!function_exists('parseDateToSqlWhere')){
    function parseDateToSqlWhere($column, $dateString)
    {
        $dateString = trim($dateString);
    
        // Only allow numbers and - and / and :
        if(!preg_match('/^[0-9\-\/:]+$/', $dateString, $matches)){
            return false;
        }
    
        $date1 = null;
        $date2 = null;
    
        // 日期區間
        if(strlen($dateString) > 12){
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
    
                $date1 = $date1_year . '-' . substr($arr[0], -4, -2) . '-' . substr($arr[0], -2);
                $date2 = $date2_year . '-' . substr($arr[1], -4, -2) . '-' . substr($arr[1], -2);
    
            }else{
                $date1_year = $arr[0] < 2000 ? $arr[0]+2000 : $arr[0];
                $date1 = $date1_year . '-' . $arr[1] . '-' . $arr[2];
    
                $date2_year = $arr[0] < 2000 ? $arr[0]+2000 : $arr[0];
                $date2= $date2_year . '-' . $arr[4] . '-' . $arr[5];
            }
    
            $sql = "DATE($column) BETWEEN '$date1' AND '$date2'";
        }
        //單一日期
        else{
            //開頭字元是比較符號 (不是數字開頭)
            if(preg_match('/^([^\d]+)\d+.*/', $dateString, $matches)){
                $operator = $matches[1];
                $dateString = str_replace($operator, '', $dateString); //remove operator
                //$symbles = ['>','<','=','>=', '<='];
            }else if(preg_match('/(^\d+.*)/', $dateString, $matches)){
                $operator = '=';
            }            
    
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

            $date1 = date_create($date1String);            
            $date2 = date_add($date1, date_interval_create_from_date_string("1 days"));
            $date2String = $date2->format('Y-m-d');

            if($operator == '='){
                $sql = "$column >= '$date1String' AND $column < '$date2String'";
            }else{
                $sql = "DATE($column) $operator '$date1'";
            }
        }
    
        if($sql){
            return $sql;
        }
    
        return false;
    }
}

if(!function_exists('getSqlWithBindings')){
    function getSqlWithBindings($query)
    {
        $sql = $query->getQuery()->toSql();
        $bindings = $query->getQuery()->getBindings();

        $filledSql = vsprintf(str_replace('?', "'%s'", $sql), $bindings);

        //return $filledSql;
    
        //return $filledSql;
        echo '<pre>', print_r($filledSql, 1), "</pre>"; exit;
    }
}

/**
 * Only compare the day part
 */
if(!function_exists('parseDiffDays')){
    function parseDiffDays($start, $end){

        $end = time();

        //start
        $start_date = parseDate($start);

        if($start_date == false){
            $start_date = date('Y-m-d', $start); //timestamp

            if($start_date == false){
                return false;
            }
        }

        //end
        $end_date = parseDate($end);

        if($end_date == false){
            $end_date = date('Y-m-d', $end); //timestamp

            if($end_date == false){
                return false;
            }
        }

        $date1 = strtotime($start_date);
        $date2 = strtotime($end_date);
        
        $days_diff = floor(($date2 - $date1) / (60 * 60 * 24));

        return $days_diff;
    }
}
