<?php

namespace App\Helpers\Classes;

class TwAddress
{
    /**
     * 參考：https://dotblogs.com.tw/hatelove/2012/06/05/parse-taiwan-address-with-regex
     * https://medium.com/@khalid.hazmi/台灣戶政編釘及地址解析-ii-4d57abca6283
     */
    function __construct(Request $request)
    {
        mb_internal_encoding('UTF-8');
    }

    public static function parseGovProvidedAddress($address = '')
    {
        //$address = '123　新北縣新北市萬華區新起里56鄰新北縣新北市市民大道貴陽街2段115之17號民生大樓15樓2-1房3-2室公有市場攤販臨時集中場'; //測試   

        // 全形空白、半形空白
        $address = preg_replace('/　| /','',$address);

        // 橫線。第二個橫線在資料庫會顯示成上橫線
        $address = preg_replace('/－|―/','',$address);

        $result['address'] = $address;
        
        // 郵遞區號
        $pattern = '/^(?<zipcode>(^\d{5}|^\d{3})?)/';
        preg_match($pattern, $address,$matches);
        if(!empty($matches['zipcode'])){
            $zipcode = $result['zipcode'] = $matches['zipcode'];
            $address = str_replace($zipcode,'',$address);
        }

        $pattern = '/([^縣]+)縣/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['county'] = $matches[1];
            $result['full_county'] = $result['divsionL1'] = $matches[0];
            $address = str_replace($result['full_county'],'',$address);

            $result['divsionL1'] = $result['full_county'];
        }

        $pattern = '/([^市]+)市/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['city'] = $matches[1];
            $result['full_city'] = $matches[0];
            $address = str_replace($result['full_city'],'',$address);
            
            if(!empty($result['divsionL1'])){ //有縣有市
                $result['divsionL2'] = $result['full_city'];
            }else{
                $result['divsionL1'] = $result['full_city'];
            }
        }

        $pattern = '/([^區]+)區/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['district'] = $matches[1];
            $result['full_district'] = $matches[0];
            $address = str_replace($result['full_district'],'',$address);
            
            $result['divsionL2'] = $result['full_district'];
        }

        $pattern = '/([^鄉]+)鄉/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['townshipX'] = $matches[1];
            $result['full_townshipX'] = $matches[0];
            $address = str_replace($result['full_townshipX'],'',$address);
            
            $result['divsionL2'] = $result['full_townshipX'];
        }

        $pattern = '/([^鎮]+)鎮/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['townshipZ'] = $matches[1];
            $result['full_townshipZ'] = $matches[0];
            $address = str_replace($result['full_townshipZ'],'',$address);
            
            $result['divsionL2'] = $result['full_townshipZ'];
        }

        $pattern = '/([^里]+)里/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['viliageL'] = $matches[1];
            $result['full_viliageL'] = $matches[0];
            $address = str_replace($result['full_viliageL'],'',$address);
        }

        $pattern = '/([^村]+)村/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['viliageC'] = $matches[1];
            $result['full_viliageC'] = $matches[0];
            $address = str_replace($result['full_viliageC'],'',$address);
        }

        $pattern = '/([^鄰]+)鄰/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['neighborhood'] = $matches[1];
            $result['full_neighborhood'] = $matches[0];
            $address = str_replace($result['full_neighborhood'],'',$address);
        }

        $pattern = '/([^道]+)大道/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['boulevard'] = $matches[1];
            $result['full_boulevard'] = $matches[0];
            $address = str_replace($result['full_boulevard'],'',$address);
        }

        $pattern = '/([^路]+)路/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['road'] = $matches[1];
            $result['full_road'] = $matches[0];
            $address = str_replace($result['full_road'],'',$address);
        }

        $pattern = '/([^街]+)街/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['street'] = $matches[1];
            $result['full_street'] = $matches[0];
            $address = str_replace($result['full_street'],'',$address);
        }

        $pattern = '/([^段]+)段/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['section'] = $matches[1];
            $result['full_section'] = $matches[0];
            $address = str_replace($result['full_section'],'',$address);

            //路街與段合併。中華郵政的路名是放在一起的。例如"忠孝東路一段" "忠孝東路二段"
            if(!empty($result['road']) && empty($result['street'])){// 有路無街
                $result['full_road_section'] = $result['full_road'] . $result['full_section'];
            }
            // 無路有街
            else if(empty($result['road']) && !empty($result['street'])){
                $result['full_road_section'] = $result['full_street'] . $result['full_section'];
            }
        }else{
            $result['full_road_section'] = $result['full_road'] ?? $result['full_street'] ?? '';
        }

        $result['after_road_section'] = $address;

        $pattern = '/([^巷]+)巷/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['lane'] = $matches[1];
            $result['full_lane'] = $matches[0];
            $address = str_replace($result['full_lane'],'',$address);
        }

        $pattern = '/([^弄]+)弄/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['alley'] = $matches[1];
            $result['full_alley'] = $matches[0];
            $address = str_replace($result['full_alley'],'',$address);
        }

        $pattern = '/([^號]+)號/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['no'] = $matches[1];
            $result['full_no'] = $matches[0];
            $address = str_replace($result['full_no'],'',$address);
        }

        $pattern = '/([^棟]+)棟/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['building'] = $matches[1];
            $result['full_building'] = $matches[0];
            $address = str_replace($result['full_building'],'',$address);
        }

        $pattern = '/([^樓]+)大樓/u'; // 群光大樓、新光大樓 ...
        if(preg_match($pattern, $address,$matches)) {
            $result['floorB'] = $matches[1];
            $result['full_floorB'] = $matches[0];
            $address = str_replace($result['full_floorB'],'',$address);
        }

        $pattern = '/([^樓]+)樓/u'; // 1樓, 2樓 ... 
        if(preg_match($pattern, $address,$matches)) {
            $result['floor'] = $matches[1];
            $result['full_floor'] = $matches[0];
            $address = str_replace($result['full_floor'],'',$address);
        }

        $pattern = '/([^房]+)房/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['roomF'] = $matches[1];
            $result['full_roomF'] = $matches[0];
            $address = str_replace($result['full_roomF'],'',$address);
        }

        $pattern = '/([^室]+)室/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['roomS'] = $matches[1];
            $result['full_roomS'] = $matches[0];
            $address = str_replace($result['full_roomS'],'',$address);
        }

        if(!empty($address)){
            $result['other'] = $address;
        }

        if(!empty($result)){
            return $result;
        }

        return false;
    }
}

