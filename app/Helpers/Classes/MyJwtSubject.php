<?php

namespace App\Helpers\Classes;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Carbon\Carbon;

class MyJwtSubject implements JWTSubject
{
    private $identifier;

    public function __construct($identifier = '')
    {
        $this->identifier = $identifier;
    }

    /**
     * 獲取唯一識別符號
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->identifier;  // 返回用戶的唯一標識符（例如，用戶的 ID）
    }

    /**
     * 獲取 JWT 自定義 claims
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'exp' => Carbon::now()->addYears(10)->timestamp,
        ];
    }
}
