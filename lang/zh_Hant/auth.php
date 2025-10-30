<?php

return array (
  'failed' => '帳號或密碼錯誤.',
  'password' => 'The provided password is incorrect.',
  'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

  // OAuth / API 認證錯誤碼
  'error_codes' => [
      'TOKEN_MISSING' => '未提供授權 Token',
      'TOKEN_INVALID' => 'Token 驗證失敗，請重新登入',
      'USER_DISABLED' => '使用者已停用',
      'USER_NOT_FOUND' => '使用者不存在於本地系統',
      'OAUTH_SERVICE_UNAVAILABLE' => '無法連線至帳號管理中心，請稍後再試',
  ],
);
