# POS 系統 - OAuth 驗證架構設計文件

## 目錄
1. [系統概述](#系統概述)
2. [現況分析](#現況分析)
3. [新架構設計](#新架構設計)
4. [OAuth 整合方案](#oauth-整合方案)
5. [與舊系統共存](#與舊系統共存)
6. [安全性設計](#安全性設計)
7. [錯誤處理](#錯誤處理)
8. [實作規劃](#實作規劃)

---

## 系統概述

### 系統資訊
- **POS 前端**: Quasar (Vue.js)
- **POS 後端**: Laravel (ODS 系統)
- **帳號中心**: https://accounts.huabing.tw

### 架構特性
- 前後端分離 (SPA)
- OAuth 2.0 統一身份驗證
- JWT Token 機制
- 2FA (雙因素驗證) 由 Accounts 中心處理
- 與舊登入系統並存

---

## 現況分析

### 當前驗證機制 (舊)

**登入流程**:
```
POS 前端 → POS 後端 (JWT Auth)
     ↓
直接驗證本地 users 表
     ↓
回傳 JWT Token
```

**現有檔案**:
- `app/Domains/ApiPosV2/Http/Controllers/Auth/LoginController.php` - 現有登入控制器
- 本地 users 資料表驗證

**問題點**:
1. 帳號密碼分散管理
2. 無法與其他系統（HRM）共用帳號
3. 缺乏 2FA 等進階安全功能
4. 密碼變更需要同步多個系統

---

## 新架構設計

### 整體架構圖

```
┌─────────────┐
│  POS 前端   │
│ (Quasar)    │
└──────┬──────┘
       │ 1. 使用者輸入帳密
       │    POST /api/pos/v2/oauth/login
       ↓
┌─────────────┐
│  POS 後端   │
│ (Laravel)   │
│ OAuthController
└──────┬──────┘
       │ 2. 轉發認證請求
       │    POST /api/oauth/login
       ↓
┌─────────────────┐
│ Accounts 中心   │
│ (OAuth Server)  │
│ - 帳密驗證      │
│ - 2FA 驗證      │
│ - 裝置管理      │
└─────────────────┘
       │ 3. 回傳 OAuth Token
       ↓
┌─────────────┐
│  POS 後端   │
│ - 同步使用者│
│ - 產生 JWT  │
└──────┬──────┘
       │ 4. 回傳 JWT Token
       ↓
┌─────────────┐
│  POS 前端   │
│ - 儲存 JWT  │
└─────────────┘
```

### 核心設計原則

1. **統一認證入口**: 透過 Accounts 中心驗證
2. **簡化 Token 設計**:
   - POS 後端接收 OAuth Token 後立即轉換為 JWT
   - POS 前端只使用 JWT Token
   - 不儲存 OAuth Token（無狀態設計）
3. **向後相容**: 保留舊的 LoginController，新增 OAuthController
4. **責任分離**: 裝置管理、2FA 由 Accounts 中心處理

---

## OAuth 整合方案

### 1. 登入流程 (OAuth Login)

#### Step 1: 前端發起 OAuth 登入請求

**新端點**: `POST /api/pos/v2/oauth/login`

**Request Body**:
```json
{
  "account": "admin@example.com",
  "password": "123456"
}
```

**說明**:
- 新增專門的 OAuth 登入端點
- 與舊的 `/api/pos/v2/login` 並存
- 欄位名稱改為 `account`（與 Accounts 中心一致）

---

#### Step 2: POS 後端轉發至 Accounts 中心

**Target**: `POST https://accounts.huabing.tw/api/login`

**Request Body**:
```json
{
  "account": "admin@example.com",
  "password": "123456",
  "system_code": "pos",
  "client_code": "pos-system"
}
```

**參數說明**:
- `account`: 使用者帳號
- `password`: 使用者密碼
- `system_code`: 固定值 `"pos"`
- `client_code`: 固定值 `"pos-system"`

**處理類別**: `OAuthController@login`

---

#### Step 3: Accounts 中心回應

##### Case A: 驗證成功

**Response (200 OK)**:
```json
{
  "success": true,
  "message": "登入成功，當前系統: POS 系統",
  "data": {
    "user": {
      "id": 1,
      "code": "10000011185",
      "name": "Administrator",
      "email": "admin@example.com",
      "roles": ["sys_admin"]
    },
    "roles": ["sys_admin"],
    "current_system": {
      "id": 4,
      "name": "POS 系統",
      "code": "pos"
    },
    "client": {
      "id": "01999a4e-558b-73f7-bd55-568fe78a73f5",
      "code": "pos-system",
      "name": "POS System API"
    },
    "device": {
      "id": 6,
      "name": "💻 Chrome on Windows 10",
      "is_new_device": false,
      "is_trusted": true,
      "requires_verification": false
    },
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "expires_at": "2025-10-14T16:55:34.000000Z"
  }
}
```

**核心欄位**:
- `user.code`: 使用者代碼，跨系統唯一識別碼（用於同步本地使用者）
- `token`: OAuth Access Token（不儲存，僅用於驗證）
- `expires_at`: Token 過期時間
- `device`: 裝置資訊（由 Accounts 中心管理）

---

##### Case B: 需要 2FA 驗證

**Response (403 Forbidden)**:
```json
{
  "success": false,
  "message": "此裝置需要進行雙因素驗證，驗證信已發送至您的信箱",
  "data": {
    "requires_2fa": true,
    "device_id": 7,
    "verification_method": "email"
  }
}
```

**處理流程**:
1. Accounts 中心自動寄送驗證信
2. 使用者點擊信箱驗證連結
3. 驗證連結導向 Accounts 中心完成驗證
4. 使用者返回 POS 前端重新登入
5. 裝置已信任，直接完成登入

---

##### Case C: 驗證失敗

**Response (401 Unauthorized)**:
```json
{
  "success": false,
  "message": "帳號或密碼錯誤",
  "data": null
}
```

---

#### Step 4: POS 後端處理 OAuth 回應

**成功時的處理邏輯**:

1. **同步或建立本地使用者**
   - 使用 `code` 作為唯一識別
   - 如果使用者不存在，建立新使用者
   - 如果使用者存在，更新基本資料

2. **生成 POS 系統的 JWT Token**
   - 使用 POS 現有的 JWT 機制
   - Payload 包含必要的使用者資訊
   - 不儲存 OAuth Token

3. **回傳給前端**
   - 回傳 JWT Token
   - 回傳使用者資料

**關鍵點**:
- ✅ 不建立 Session 表
- ✅ 不儲存 OAuth Token
- ✅ 完全依賴 JWT 的過期機制
- ✅ 簡化設計，無狀態

---

#### Step 5: POS 後端回應前端

**Response to Frontend**:
```json
{
  "success": true,
  "data": {
    "access_token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...",
    "token_type": "bearer",
    "expires_in": 86400,
    "user": {
      "id": 1,
      "code": "10000011185",
      "username": "admin",
      "email": "admin@example.com",
      "name": "Administrator"
    }
  }
}
```

**前端處理**:
- 儲存 JWT Token 至 localStorage
- 後續 API 請求帶上 Authorization Header
- 與現有的登入流程相同

---

### 2. API 請求驗證流程

**不需要改變現有的驗證邏輯**：

```
前端 API 請求
     ↓
帶上 JWT Token (Authorization: Bearer xxx)
     ↓
POS 後端 Middleware 驗證 JWT
     ↓
驗證通過，執行業務邏輯
```

**說明**:
- OAuth 登入和傳統登入產生的 JWT Token 格式相同
- 使用相同的 Middleware 驗證
- 對業務邏輯透明，無感知

---

### 3. 登出流程 (Logout)

**前端處理**:
```
刪除本地的 JWT Token
localStorage.removeItem('access_token')
導向登入頁
```

**後端處理（可選）**:
- 如果有實作 JWT 黑名單，將 Token 加入黑名單
- 如果沒有，前端刪除即可（依賴 Token 自然過期）

**不需要通知 Accounts 中心**:
- 因為我們沒有儲存 OAuth Token
- 裝置管理由 Accounts 中心自行處理

---

## 與舊系統共存

### 並存策略

**保留舊端點**:
- `POST /api/pos/v2/login` - 舊的本地登入（LoginController）
- 繼續支援現有客戶端

**新增 OAuth 端點**:
- `POST /api/pos/v2/oauth/login` - OAuth 登入（OAuthController）
- 新客戶端或升級後使用

### 使用者資料同步

**code 欄位**:
- `users` 表的 `code` 欄位用於識別使用者
- OAuth 登入的使用者有 code（來自 Accounts 中心）
- 舊的本地使用者 code 為 null 或本地生成

**識別方式**:
```
有 code（來自 Accounts）→ 來自 Accounts 中心
無 code 或本地 code → 本地建立的使用者
```

### 漸進式遷移

**階段 1**: 並存期（1-3 個月）
- 舊登入和 OAuth 登入同時可用
- 鼓勵使用者使用 OAuth 登入
- 監控 OAuth 登入使用率

**階段 2**: 遷移期（3-6 個月）
- 提示使用者切換到 OAuth 登入
- 舊帳號可以轉換為 OAuth 帳號

**階段 3**: 完全遷移（6 個月後）
- 評估是否停用舊登入端點
- 保留向後相容或完全移除

---

## 安全性設計

### 1. Token 安全

**OAuth Token**:
- ✅ 不儲存於 POS 系統
- ✅ 僅在登入時使用，驗證後即丟棄
- ✅ 降低 Token 洩漏風險

**JWT Token**:
- 儲存於前端 localStorage
- 設定合理的過期時間（建議與 Accounts OAuth Token 一致）
- 僅透過 HTTPS 傳輸

### 2. CORS 設定

**允許的來源**:
- POS 前端網域
- 開發環境網域

**必要的 Header**:
```
Access-Control-Allow-Origin: https://pos.example.com
Access-Control-Allow-Credentials: true
Access-Control-Allow-Methods: POST, GET, OPTIONS
Access-Control-Allow-Headers: Authorization, Content-Type
```

### 3. 速率限制

**登入端點**:
- 限制：5 次/分鐘/IP
- 防止暴力破解

**一般 API**:
- 限制：60 次/分鐘/使用者

### 4. 環境變數安全

**敏感資訊**:
```env
ACCOUNTS_CENTER_URL=https://accounts.huabing.tw
ACCOUNTS_CLIENT_CODE=pos-system
ACCOUNTS_SYSTEM_CODE=pos
ACCOUNTS_TIMEOUT=10
```

**不要提交到版本控制**:
- 使用 `.env` 檔案
- `.env.example` 只包含範例值

---

## 錯誤處理

### 1. Accounts 中心連線失敗

**Fallback 策略**:

**選項 A: 回退到本地驗證（建議）**
```
OAuth 登入失敗
    ↓
檢查 Accounts 中心是否無法連線
    ↓
自動使用本地 users 表驗證
    ↓
記錄事件日誌
```

**選項 B: 直接返回錯誤**
```
OAuth 登入失敗
    ↓
返回錯誤訊息給前端
    ↓
提示使用者稍後再試或使用舊登入
```

### 2. 2FA 驗證流程

**前端處理**:
```
收到 403 錯誤且 requires_2fa = true
    ↓
顯示提示訊息：
「請至您的信箱完成裝置驗證，驗證完成後請重新登入」
    ↓
停留在登入頁面
```

### 3. Token 過期處理

**策略**: 依賴 JWT 過期機制

**前端攔截器**:
```
API 請求返回 401
    ↓
清除本地 Token
    ↓
導向登入頁
    ↓
提示：「登入已過期，請重新登入」
```

### 4. 使用者同步失敗

**錯誤情境**:
- Accounts 驗證成功
- 但本地建立使用者失敗（資料庫錯誤等）

**處理**:
- 返回 500 錯誤
- 記錄詳細日誌
- 提示使用者聯絡管理員

---

## 實作規劃

### Phase 1: 基礎建設 (Week 1)

#### 1.1 建立 OAuth 服務類別
- [ ] 建立 `app/Libraries/AccountsOAuthLibrary.php`
- [ ] 實作 `login()` 方法
- [ ] 加入連線逾時與重試機制
- [ ] 實作錯誤處理與日誌記錄

#### 1.2 資料庫確認
- [x] `users` 表已有 `code` 欄位（已處理）
- [ ] 確認 `code` 欄位的索引設定

#### 1.3 環境設定
- [ ] `.env` 新增 Accounts 中心設定
- [ ] `config/services.php` 新增 accounts 設定
- [ ] 確認 JWT 設定正確

---

### Phase 2: 核心功能實作 (Week 2)

#### 2.1 建立 OAuth 控制器
- [ ] 建立 `app/Domains/ApiPosV2/Http/Controllers/Auth/OAuthController.php`
- [ ] 實作 `login()` 方法
- [ ] 實作 `syncUserFromOAuth()` 方法
- [ ] 處理 2FA 回應
- [ ] 處理各種錯誤情況

#### 2.2 路由設定
- [ ] 新增 OAuth 登入路由 `POST /api/pos/v2/oauth/login`
- [ ] 設定速率限制
- [ ] 設定 CORS

#### 2.3 Fallback 機制
- [ ] 實作 Accounts 中心連線失敗的 Fallback 邏輯
- [ ] 測試 Fallback 是否正常運作

---

### Phase 3: 前端整合 (Week 3)

#### 3.1 前端登入頁面調整
- [ ] 新增 OAuth 登入選項
- [ ] 或直接替換為 OAuth 登入
- [ ] 處理 2FA 提示訊息
- [ ] 處理錯誤訊息顯示

#### 3.2 前端錯誤處理
- [ ] 攔截器處理 401 錯誤
- [ ] 攔截器處理 403 (2FA) 錯誤
- [ ] Token 過期自動導向登入

#### 3.3 使用者體驗優化
- [ ] 登入中載入動畫
- [ ] 錯誤訊息友善提示
- [ ] 記住帳號功能（可選）

---

### Phase 4: 測試 (Week 4)

#### 4.1 單元測試
- [ ] `AccountsOAuthLibrary` 測試
- [ ] `OAuthController` 測試
- [ ] 使用者同步邏輯測試

#### 4.2 整合測試
- [ ] 完整 OAuth 登入流程測試
- [ ] 2FA 流程測試
- [ ] Token 過期處理測試
- [ ] Fallback 機制測試

#### 4.3 相容性測試
- [ ] 舊登入端點仍正常運作
- [ ] OAuth 登入與舊登入產生的 JWT 可互通
- [ ] 現有 API 端點對兩種登入方式透明

#### 4.4 壓力測試
- [ ] 高併發登入測試
- [ ] Accounts 中心斷線測試
- [ ] 資料庫連線異常測試

---

### Phase 5: 部署 (Week 5)

#### 5.1 部署前準備
- [ ] 環境變數設定（測試環境）
- [ ] 資料庫遷移
- [ ] 與 Accounts 團隊協調
- [ ] 編寫部署文件

#### 5.2 測試環境部署
- [ ] 部署到測試環境
- [ ] 內部測試
- [ ] 修正發現的問題

#### 5.3 生產環境部署
- [ ] 生產環境環境變數設定
- [ ] 資料庫遷移（線上）
- [ ] 灰度發布（部分使用者）
- [ ] 監控錯誤率
- [ ] 全量發布

#### 5.4 監控與維護
- [ ] OAuth API 呼叫成功率監控
- [ ] 登入失敗率監控
- [ ] 錯誤日誌告警設定
- [ ] 效能監控

---

## 附錄

### A. API 端點清單

#### Accounts 中心 (accounts.huabing.tw)
| Method | Endpoint | 說明 |
|--------|----------|------|
| POST | `/api/oauth/login` | OAuth 登入 |
| GET | `/api/oauth/user` | 取得使用者資訊 |

#### POS 系統
| Method | Endpoint | 說明 | 狀態 |
|--------|----------|------|------|
| POST | `/api/pos/v2/login` | 舊的本地登入 | 保留 |
| POST | `/api/pos/v2/oauth/login` | OAuth 登入 | **新增** |
| POST | `/api/pos/v2/logout` | 登出 | 現有 |

---

### B. 環境變數範例

```env
# Accounts OAuth Settings
ACCOUNTS_CENTER_URL=https://accounts.huabing.tw
ACCOUNTS_CLIENT_CODE=pos-system
ACCOUNTS_SYSTEM_CODE=pos
ACCOUNTS_TIMEOUT=10

# JWT Settings (現有設定)
JWT_SECRET=your-jwt-secret-key
JWT_TTL=1440
```

---

### C. 關鍵檔案列表

#### 新建檔案
- `app/Libraries/AccountsOAuthLibrary.php` - OAuth 服務類別（可共用 HRM 的）
- `app/Domains/ApiPosV2/Http/Controllers/Auth/OAuthController.php` - OAuth 控制器

#### 保留檔案（不修改）
- `app/Domains/ApiPosV2/Http/Controllers/Auth/LoginController.php` - 舊登入控制器
- `app/Models/User.php` - User 模型（已有 code 欄位）

#### 需修改檔案
- `routes/api.php` - 新增 OAuth 路由
- `config/services.php` - 新增 Accounts 設定

---

### D. 與 HRM 系統的差異

| 項目 | HRM 系統 | POS 系統 |
|------|----------|----------|
| Session 管理 | 無（移除設計） | 無 |
| OAuth Token 儲存 | 不儲存 | 不儲存 |
| JWT Token | 使用 | 使用 |
| 舊系統相容 | 保留 | 保留 |
| Fallback 機制 | 有 | 有 |
| 裝置管理 | Accounts 中心 | Accounts 中心 |
| 實作複雜度 | 簡單 | 簡單 |

**結論**: 兩個系統採用相同的簡化設計，可共用 `AccountsOAuthLibrary`。

---

### E. 待確認事項

#### 與 Accounts 團隊確認
- [ ] OAuth API 完整端點與參數格式
- [ ] OAuth Token 有效期限設定（建議與 JWT TTL 一致）
- [ ] 2FA 驗證的完整流程
- [ ] 錯誤代碼與訊息格式
- [ ] API 速率限制設定
- [ ] `system_code` 和 `client_code` 的正確值

#### 產品需求確認
- [ ] 是否強制使用 OAuth 登入，還是可選
- [ ] 舊登入何時停用（時程規劃）
- [ ] 前端如何處理 2FA 流程（UI/UX 設計）
- [ ] JWT TTL 設定（建議 24 小時）
- [ ] 是否需要「記住我」功能

#### 技術確認
- [ ] POS 系統的 JWT 實作細節
- [ ] 現有的 Middleware 是否需要調整
- [ ] 資料庫 users 表結構
- [ ] 現有的錯誤處理機制

---

## 總結

### ✅ 核心原則

1. **簡化設計**
   - 不建立 Session 表
   - 不儲存 OAuth Token
   - 完全依賴 JWT 機制

2. **向後相容**
   - 保留舊登入端點
   - 新增 OAuth 登入端點
   - 漸進式遷移

3. **責任分離**
   - 帳號驗證：Accounts 中心
   - 裝置管理：Accounts 中心
   - 2FA 驗證：Accounts 中心
   - JWT 管理：POS 系統

4. **容錯設計**
   - Accounts 中心連線失敗有 Fallback
   - 錯誤訊息清晰友善
   - 日誌記錄完整

### 🎯 實作要點

1. **新建 OAuthController**
   - 處理 OAuth 登入邏輯
   - 呼叫 AccountsOAuthLibrary
   - 同步使用者資料
   - 生成 JWT Token

2. **使用者同步**
   - 使用 code 作為唯一識別
   - firstOrCreate 模式
   - 更新基本資料

3. **前端調整**
   - 新增 OAuth 登入選項
   - 處理 2FA 提示
   - 錯誤訊息顯示

### ⚠️ 注意事項

- JWT TTL 應與 Accounts OAuth Token 有效期一致
- 測試 Fallback 機制是否正常
- 監控 OAuth API 呼叫失敗率
- 使用者教育（如何使用新登入）

### 🔄 下一步

1. 與 Accounts 團隊確認 API 規格
2. 開始 Phase 1 實作（基礎建設）
3. 建立測試環境
4. 前後端協調整合

---

**文件版本**: v1.0
**最後更新**: 2025-10-14
**系統**: POS (ODS)
**負責人**: Development Team
**審核狀態**: 待審核
