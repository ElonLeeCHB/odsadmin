<?php

namespace App\Helpers\Classes;

class Curl
{

    function curlget($url,$header=array()){
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 10);
        if($header){
            $header_ary=array();
            foreach ($header as $k=>$v){
                $header_ary[]="$k: $v";
            }
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $header_ary );
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $res = curl_exec($ch);
        curl_close($ch);
        $json=json_decode($res,true);
        return $json?:$res;
    }


    function curlpost($url,$post,$headers=array(),$type='json'){
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        if($headers){
            $header_ary=array();
            foreach ($headers as $k=>$v){
                $header_ary[]="$k: $v";
            }
        }
        if($post){
            if($type=='json'){
                $header_ary[]='Content-Type: application/json';
                $header_ary[]='Content-Length: ' . strlen ( json_encode($post) );
                curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
                curl_setopt($ch, CURLOPT_POST,count($post));
                curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($post));
            }else if($type=='xml'){
                $header_ary[] = "Content-type: text/xml";
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch,CURLOPT_POSTFIELDS, $post);
            }else{
                $post_str=http_build_query($post);
                curl_setopt($ch, CURLOPT_POST,count($post)) ;
                curl_setopt($ch, CURLOPT_POSTFIELDS,$post_str) ;
            }
        }
        if($header_ary){
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $header_ary );
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $res = curl_exec($ch);
        curl_close($ch);
        $json=json_decode($res,true);
        return $json?:$res;
    }


    function curloutput($url,$fields,$opt='content',$header=array()){
        $post_data=json_encode($fields);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60*30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 100);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $header_post=array(
            'Content-Type: application/json; charset=utf-8',
            'accept: application/json; charset=utf-8',
        );
        if($header){
            foreach ($header as $key=>$val){
                $header_post[]="$key: $val";
            }
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header_post);
        $result = curl_exec($ch);//获得返回值
        $status = curl_getinfo($ch);
        if(curl_errno($ch)){
            die(curl_error($ch));
        }
    //  if(substr($url, -9)=='Order/Add'){
    // var_dump($result);
    // var_dump($status);exit;
    //  }
        curl_close($ch);
        //echo ($result."\r\n\r\n\r\n\r\n");var_dump($header_post);exit;
        if(strpos(' '.$opt, 'content')>0){
            return $result;
        }else{
            $status['http_code'];
        }
    }
}