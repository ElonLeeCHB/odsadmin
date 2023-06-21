<?php

namespace App\Faker\zh_Hant;

class Department extends \Faker\Provider\Base
{
    protected static $names = [
        '董事長室',
        '總經理室',
        '稽核室',
        '收發室',
        '警衛室',
        '行政管理處',
        '行政部',
        '總務處',
        '總務部',
        '總務科',
        '人力資源部',
        '資訊處',
        '資訊部',
        '資訊科',
        '業務處',
        '業務部',
        '行銷部',
        '企劃部',
        '採購處',
        '採購部',
        '研究開發處',
        '研究開發部',  
        '品管部',   
        '客戶服務部',      
        '訓導處',
        '教務處',
        '歷史系辦公室',
        '中文系辦公室',
        '哲學系辦公室',
        '物理系辦公室',
        '電腦中心',
        '計算機中心',
    ];

  public function department()
  {
    return static::randomElement(static::$names);
  }
}