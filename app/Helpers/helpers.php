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
     * return yyyy-mm-dd
     */
if(!function_exists('parseDate')){
    function parseDate(String $dateString, $type = 'string')
    {        
        // Only allow numbers and - and / and :
        if(!preg_match('/^[0-9\-\/:]+$/', $dateString, $matches)){
            return false;
        }

        // Only get numbers
        $dateString = preg_replace('#-|\/#','',$dateString);

        // Get full string
        $year = (int)substr($dateString, 0, -4);
        $year = $year < 2000 ? $year+2000 : $year;
        $dateString = $year . '-' . substr($dateString, -4, -2) . '-' . substr($dateString, -2);

        $validDateString = date('Y-m-d', strtotime($dateString));

        if($validDateString == $dateString){
            return $validDateString;
        }else{
			return false;
		}
    }
}

// '2023-03-27' or '2023-03-27 00:00:00' to 230327
if(!function_exists('parseDateTo6d')){
    function parseDateStringTo6d($dateString)
    {
        preg_match_all('/\d+/', $dateString, $matches);
        $dateString = implode('', $matches[0]);
        $dateString = substr($dateString,2);
        return $dateString;
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