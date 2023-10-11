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
            $result['short_city'] = $matches[1];
            $result['city'] = $matches[0];
            $address = str_replace($result['city'],'',$address);
            
            if(!empty($result['divsionL1'])){ //有縣有市
                $result['divsionL2'] = $result['city'];
            }else{
                $result['divsionL1'] = $result['city'];
            }
        }

        $pattern = '/([^區]+)區/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['short_district'] = $matches[1];
            $result['district'] = $matches[0];
            $address = str_replace($result['district'],'',$address);
            
            $result['divsionL2'] = $result['district'];
        }

        $pattern = '/([^鄉]+)鄉/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['short_townshipX'] = $matches[1];
            $result['townshipX'] = $matches[0];
            $address = str_replace($result['townshipX'],'',$address);
            
            $result['divsionL2'] = $result['townshipX'];
        }

        $pattern = '/([^鎮]+)鎮/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['short_townshipZ'] = $matches[1];
            $result['townshipZ'] = $matches[0];
            $address = str_replace($result['townshipZ'],'',$address);
            
            $result['divsionL2'] = $result['townshipZ'];
        }

        $pattern = '/([^里]+)里/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['short_viliageL'] = $matches[1];
            $result['viliageL'] = $matches[0];
            $address = str_replace($result['viliageL'],'',$address);
        }

        $pattern = '/([^村]+)村/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['short_viliageC'] = $matches[1];
            $result['viliageC'] = $matches[0];
            $address = str_replace($result['viliageC'],'',$address);
        }

        $pattern = '/([^鄰]+)鄰/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['short_neighborhood'] = $matches[1];
            $result['neighborhood'] = $matches[0];
            $address = str_replace($result['neighborhood'],'',$address);
        }

        $pattern = '/([^道]+)大道/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['short_boulevard'] = $matches[1];
            $result['boulevard'] = $matches[0];
            $address = str_replace($result['boulevard'],'',$address);
        }

        $pattern = '/([^路]+)路/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['short_road'] = $matches[1];
            $result['road'] = $matches[0];
            $address = str_replace($result['road'],'',$address);
        }

        $pattern = '/([^街]+)街/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['short_street'] = $matches[1];
            $result['street'] = $matches[0];
            $address = str_replace($result['street'],'',$address);
        }

        $pattern = '/([^段]+)段/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['short_section'] = $matches[1];
            $result['section'] = $matches[0];
            $address = str_replace($result['section'],'',$address);

            //路街與段合併。中華郵政的路名是放在一起的。例如"忠孝東路一段" "忠孝東路二段"
            if(!empty($result['road']) && empty($result['street'])){// 有路無街
                $result['road_section'] = $result['road'] . $result['section'];
            }
            // 無路有街
            else if(empty($result['road']) && !empty($result['street'])){
                $result['road_section'] = $result['street'] . $result['section'];
            }
        }else{
            $result['road_section'] = $result['road'] ?? $result['street'] ?? '';
        }

        $result['after_road_section'] = $address;

        $pattern = '/([^巷]+)巷/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['short_lane'] = $matches[1];
            $result['lane'] = $matches[0];
            $address = str_replace($result['lane'],'',$address);
        }

        $pattern = '/([^弄]+)弄/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['short_alley'] = $matches[1];
            $result['alley'] = $matches[0];
            $address = str_replace($result['alley'],'',$address);
        }

        $pattern = '/([^號]+)號/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['short_no'] = $matches[1];
            $result['no'] = $matches[0];
            $address = str_replace($result['no'],'',$address);
        }

        $pattern = '/([^棟]+)棟/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['short_building'] = $matches[1];
            $result['building'] = $matches[0];
            $address = str_replace($result['building'],'',$address);
        }

        $pattern = '/([^樓]+)大樓/u'; // 群光大樓、新光大樓 ...
        if(preg_match($pattern, $address,$matches)) {
            $result['short_floorB'] = $matches[1];
            $result['floorB'] = $matches[0];
            $address = str_replace($result['floorB'],'',$address);
        }

        $pattern = '/([^樓]+)樓/u'; // 1樓, 2樓 ... 
        if(preg_match($pattern, $address,$matches)) {
            $result['short_floor'] = $matches[1];
            $result['floor'] = $matches[0];
            $address = str_replace($result['floor'],'',$address);
        }

        $pattern = '/([^房]+)房/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['short_roomF'] = $matches[1];
            $result['roomF'] = $matches[0];
            $address = str_replace($result['roomF'],'',$address);
        }

        $pattern = '/([^室]+)室/u';
        if(preg_match($pattern, $address,$matches)) {
            $result['short_roomS'] = $matches[1];
            $result['roomS'] = $matches[0];
            $address = str_replace($result['roomS'],'',$address);
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

