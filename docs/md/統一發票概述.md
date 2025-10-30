# 發票作業系統設計文件

## 目錄

1. [業務需求](#業務需求)
   - 開票方式
   - 核心原則
2. [資料表設計](#資料表設計)
   - 開票群組表
   - 群組-訂單關聯表
   - 群組-發票關聯表
   - 訂單表
   - 發票表
   - 發票項目表
   - 載具類型表
3. [設計理念](#設計理念)
4. [核心業務邏輯](#核心業務邏輯)
5. [開票流程](#開票流程)
6. [發票作廢流程](#發票作廢流程)
7. [發票重開流程](#發票重開流程)
8. [查詢範例](#查詢範例)
9. [Migration 檔案](#migration-檔案)
10. [資料完整性保證](#資料完整性保證)
11. [Model 查詢建議](#model-查詢建議)
12. [API 串接說明](#api-串接說明)
13. [總結](#總結)

---

## 業務需求

### 開票方式

系統需支援四種訂單與發票的對應方式，**所有方式都必須建立群組**：

| 類型 | 說明 | 範例 | 群組結構 |
|------|------|------|----------|
| **1. 標準（一對一）** | 1張訂單開1張發票 | 訂單#001 ($1000) → 發票#A001 ($1000) | 1群組, 1訂單, 1發票 |
| **2. 拆單** | 1張訂單拆成多張發票 | 訂單#001 ($1000) → 發票#A001 ($600) + #A002 ($400) | 1群組, 1訂單, N發票 |
| **3. 合併** | 多張訂單合開1張發票 | 訂單#001 ($1000) + #002 ($2000) → 發票#A001 ($3000) | 1群組, N訂單, 1發票 |
| **4. 混合** | 多張訂單開多張發票<br>（訂單與發票金額不直接對應） | 訂單#001 ($1000) + #002 ($2000)<br>→ 發票#A001 ($1500) + #A002 ($1500) | 1群組, N訂單, N發票 |

**重要：** 即使是標準一對一開票，也必須建立群組。這樣可以：
- 統一所有開票方式的處理邏輯
- 方便追蹤和查詢所有發票記錄
- 保留完整的作廢/重開歷史

### 核心原則

1. **強制群組**：所有開票方式（包括標準一對一）都必須建立群組
2. **群組關聯**：訂單和發票透過「群組」關聯，而非直接一對一對應
3. **總額平衡**：群組內「訂單總額」必須等於「發票總額」
4. **不追蹤精確分配**：不記錄「訂單A的$X分配到發票B」，只知道「這批訂單對應這批發票」
5. **保留歷史記錄**：發票作廢後重開，原有的群組關聯仍保留
6. **狀態標記**：使用 `status` 欄位標記作廢，不使用 Laravel SoftDelete
7. **判斷新增/修改**：有 `group_no` 就是修改，沒有就是新增

---

## 資料表設計

### 核心三表

```
1. invoice_groups          - 開票群組表
2. invoice_group_orders    - 群組-訂單關聯表
3. invoice_group_invoices  - 群組-發票關聯表
```

---

### 1. 開票群組表 (invoice_groups)

```sql
CREATE TABLE invoice_groups (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_no            VARCHAR(50) UNIQUE NOT NULL COMMENT '群組編號',
    status              ENUM('active', 'voided') NOT NULL DEFAULT 'active' COMMENT '狀態: active/voided',
    void_reason         TEXT NULL COMMENT '作廢原因',
    voided_by           BIGINT UNSIGNED NULL COMMENT '作廢人ID',
    voided_at           TIMESTAMP NULL COMMENT '作廢時間',
    created_by          BIGINT UNSIGNED NULL COMMENT '建立人ID',
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,

    -- 冗餘欄位（選用，方便查詢）
    order_count         INT UNSIGNED DEFAULT 0 COMMENT '包含訂單數',
    invoice_count       INT UNSIGNED DEFAULT 0 COMMENT '包含發票數',
    total_amount        DECIMAL(10,2) DEFAULT 0.00 COMMENT '群組總金額',

    INDEX idx_group_no (group_no),
    INDEX idx_status (status)
) COMMENT='開票群組表';
```

**欄位說明：**
- `group_no`: 業務用群組編號（如：IG20250130001）
- `status`:
  - `active`: 有效
  - `voided`: 已作廢
- `void_reason`: 作廢原因（如：客戶要求重開、發票號碼錯誤等）
- `voided_by`: 作廢操作人ID
- `voided_at`: 作廢時間
- `order_count`, `invoice_count`, `total_amount`: 冗餘欄位，建立群組時寫入，方便查詢

---

### 2. 群組-訂單關聯表 (invoice_group_orders)

```sql
CREATE TABLE invoice_group_orders (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id            BIGINT UNSIGNED NOT NULL COMMENT '群組ID',
    order_id            BIGINT UNSIGNED NOT NULL COMMENT '訂單ID',
    order_amount        DECIMAL(10,2) NOT NULL COMMENT '訂單金額（冗餘）',
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,

    FOREIGN KEY (group_id) REFERENCES invoice_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT,

    INDEX idx_group (group_id),
    INDEX idx_order (order_id),
    UNIQUE KEY uk_group_order (group_id, order_id)
) COMMENT='群組-訂單關聯表';
```

**欄位說明：**
- `order_amount`: 冗餘訂單金額，方便查詢和驗證（避免 JOIN 回 orders 表）
- `UNIQUE KEY (group_id, order_id)`: 防止同一訂單在同一群組重複加入
- `ON DELETE CASCADE`: 刪除群組時，自動刪除關聯
- `ON DELETE RESTRICT`: 禁止刪除已開票的訂單

---

### 3. 群組-發票關聯表 (invoice_group_invoices)

```sql
CREATE TABLE invoice_group_invoices (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id            BIGINT UNSIGNED NOT NULL COMMENT '群組ID',
    invoice_id          BIGINT UNSIGNED NOT NULL COMMENT '發票ID',
    invoice_amount      DECIMAL(10,2) NOT NULL COMMENT '發票金額（冗餘）',
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,

    FOREIGN KEY (group_id) REFERENCES invoice_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE RESTRICT,

    INDEX idx_group (group_id),
    INDEX idx_invoice (invoice_id),
    UNIQUE KEY uk_group_invoice (group_id, invoice_id)
) COMMENT='群組-發票關聯表';
```

**欄位說明：**
- `invoice_amount`: 冗餘發票金額，方便查詢和驗證
- `UNIQUE KEY (group_id, invoice_id)`: 防止同一發票在同一群組重複加入
- `ON DELETE RESTRICT`: 禁止刪除已建立的發票

---

### 4. 訂單表 (orders) - 無需修改

原有的訂單表**不需要**加 `invoice_group_id` 欄位。

```sql
-- 原有結構保持不變
orders:
    - id
    - order_no
    - customer_id
    - amount
    - status
    - created_at
    - ...
```

---

### 5. 發票表 (invoices) - 完整結構

#### 基礎欄位

```sql
CREATE TABLE invoices (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number      VARCHAR(50) UNIQUE NOT NULL COMMENT '發票號碼',
    invoice_type        VARCHAR(20) NOT NULL COMMENT '發票類型: duplicate/triplicate',
    invoice_date        DATE NOT NULL COMMENT '發票日期',

    -- 買賣雙方資訊
    buyer_name          VARCHAR(255) NULL COMMENT '買受人名稱（B2C個人/B2B公司）',
    seller_name         VARCHAR(255) NULL COMMENT '賣方名稱',
    tax_id_number       VARCHAR(20) NULL COMMENT '買方統一編號（B2B必填）',
    customer_id         BIGINT UNSIGNED NULL COMMENT '客戶ID',

    -- 金額與稅額
    tax_type            ENUM('taxable', 'exempt', 'zero_rate', 'mixed', 'special')
                        DEFAULT 'taxable' NULL COMMENT '課稅類別',
    tax_state           TINYINT DEFAULT 0 COMMENT '單價是否含稅（0-含稅, 1-未稅）',
    tax_amount          DECIMAL(10,2) NOT NULL COMMENT '稅額',
    net_amount          DECIMAL(10,2) NULL COMMENT '未稅金額（淨額）',
    total_amount        DECIMAL(10,2) NOT NULL COMMENT '發票總額（含稅）',

    -- 發票基本資訊
    random_code         VARCHAR(4) NULL COMMENT '4位隨機碼（API 回傳）',
    content             TEXT NULL COMMENT '總備註（顯示於發票上）',
    email               VARCHAR(255) NULL COMMENT '客戶 Email',

    -- 載具資訊（B2C）
    carrier_type        ENUM('none', 'phone_barcode', 'citizen_cert', 'member_card',
                             'credit_card', 'icash', 'easycard', 'ipass', 'email', 'donation')
                        DEFAULT 'none' COMMENT '載具類型',
    carrier_number      VARCHAR(255) NULL COMMENT '載具號碼/條碼',
    donation_code       VARCHAR(20) NULL COMMENT '捐贈碼',

    -- 零稅率、混合稅專用（目前用不到）
    -- customs_mark     ENUM('0', '1') NULL COMMENT '通關方式（0-非海關, 1-經海關）',
    -- free_amount      INT NULL COMMENT '免稅銷售額合計',
    -- zero_amount      INT NULL COMMENT '零稅率銷售額合計',
    -- zero_remark      VARCHAR(2) NULL COMMENT '零稅率原因代碼（71-79）',

    -- API 串接記錄
    api_request_data    JSON NULL COMMENT '呼叫 API 的請求資料',
    api_response_data   JSON NULL COMMENT 'API 的回應資料',
    api_error           TEXT NULL COMMENT 'API 錯誤訊息',

    -- 發票狀態
    status              ENUM('pending', 'issued', 'voided') DEFAULT 'pending' COMMENT '狀態: pending/issued/voided',
    void_reason         TEXT NULL COMMENT '作廢原因',
    voided_by           BIGINT UNSIGNED NULL COMMENT '作廢人ID',
    voided_at           TIMESTAMP NULL COMMENT '作廢時間',
    canceled_at         TIMESTAMP NULL COMMENT '作廢時間（API）',
    cancel_reason       TEXT NULL COMMENT '作廢原因（API）',

    -- 系統欄位
    created_by          BIGINT UNSIGNED NULL COMMENT '建立人使用者ID',
    updated_by          BIGINT UNSIGNED NULL COMMENT '修改人使用者ID',
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,

    -- 索引
    INDEX idx_customer_id (customer_id),
    INDEX idx_tax_id_number (tax_id_number),
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_invoice_date (invoice_date),
    INDEX idx_status (status)
) COMMENT='發票表';
```

#### 欄位說明

**invoice_type - 發票類型**：
- `duplicate`: 二聯式
- `triplicate`: 三聯式

**tax_type - 課稅類別**（5種）：
- `taxable`: 應稅
- `exempt`: 免稅
- `zero_rate`: 零稅率
- `mixed`: 混合稅
- `special`: 特種稅

**carrier_type - 載具類型**（10種）：

| 類型 | 說明 | carrier_number 範例 |
|------|------|---------------------|
| `none` | 無載具（紙本） | NULL |
| `phone_barcode` | 手機條碼 | `/ABC1234` |
| `citizen_cert` | 自然人憑證 | `AA12345678` |
| `member_card` | 會員卡 | `M123456` |
| `credit_card` | 信用卡載具 | `CC1234567890` |
| `icash` | icash | `IC1234567890` |
| `easycard` | 悠遊卡 | `EC1234567890` |
| `ipass` | 一卡通 | `IP1234567890` |
| `email` | 電子郵件 | `user@example.com` |
| `donation` | 捐贈 | NULL |

**手機條碼格式規則**：
- 第 1 碼為 `/`
- 其餘 7 碼由數字 `0-9`、大寫英文 `A-Z`、特殊符號 `+`, `-`, `.` 組成
- 範例：`/ABC1234`, `/1234567`, `/AB+CD-E`

**金額關係公式**：
```
tax_amount + net_amount = total_amount
稅額 + 未稅金額 = 總金額
```

**零稅率原因代碼（zero_remark）**：
- `71`: 外銷貨物
- `72`: 與外銷有關之勞務
- `73`: 免稅商店銷售
- `74`: 銷售與保稅區營業人
- `75`: 國際間之運輸
- `76`: 國際運輸用之船舶、航空器
- `77`: 國際運輸用船舶、航空器之修繕勞務
- `78`: 保稅區營業人直接出口
- `79`: 保稅區營業人存入自由港區

**status - 發票狀態**：
- `issued`: 已開立
- `voided`: 已作廢

**設計說明**：
- 透過 `tax_id_number` 有無判斷 B2C/B2B（無需 invoice_type 欄位）
- 支援電子發票 API 完整欄位
- 記錄完整的 API 請求/回應（api_request_data, api_response_data）

---

### 6. 發票項目表 (invoice_items)

```sql
CREATE TABLE invoice_items (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id          BIGINT UNSIGNED NOT NULL COMMENT '發票ID',
    sort_order          UNSIGNED INT DEFAULT 0 COMMENT '排序',
    name                VARCHAR(255) NOT NULL COMMENT '商品名稱（可自訂）',
    is_tax_included     BOOLEAN DEFAULT TRUE COMMENT '是否含稅',
    quantity            DECIMAL(12,3) DEFAULT 1 COMMENT '數量',
    price               DECIMAL(12,3) NOT NULL COMMENT '單價',
    subtotal            DECIMAL(12,3) NOT NULL COMMENT '小計（price × quantity）',

    -- API 相關欄位
    remark              VARCHAR(255) NULL COMMENT '商品備註',
    item_tax_type       TINYINT NULL COMMENT '商品課稅類型（0-應稅, 1-零稅率, 2-免稅）'
) COMMENT='發票項目表';
```

**欄位說明**：
- `name`: 商品名稱，可自訂（與訂單商品名稱可不同）
- `is_tax_included`: 是否含稅價（方便稅額推算）
- `subtotal`: 小計 = price × quantity
- `item_tax_type`: 混合稅必填，標示該商品的課稅類型

---

### 7. 載具類型表 (invoice_carrier_types)

```sql
CREATE TABLE invoice_carrier_types (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code                VARCHAR(20) UNIQUE NOT NULL COMMENT '載具代碼（對應 invoices.carrier_type）',
    name                VARCHAR(50) NOT NULL COMMENT '中文名稱',
    description         VARCHAR(255) NULL COMMENT '說明',
    giveme_param        VARCHAR(20) NULL COMMENT '對應 API 參數名稱（phone/orderCode/donationCode）',
    sort_order          TINYINT UNSIGNED DEFAULT 0 COMMENT '排序',
    is_active           BOOLEAN DEFAULT TRUE COMMENT '是否啟用',
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,

    INDEX idx_code (code),
    INDEX idx_sort_order (sort_order)
) COMMENT='載具類型查詢表';
```

**預設載具類型資料**：

| code | name | giveme_param | sort_order |
|------|------|--------------|------------|
| `none` | 無載具（列印紙本） | `null` | 1 |
| `phone_barcode` | 手機條碼 | `phone` | 2 |
| `citizen_cert` | 自然人憑證 | `orderCode` | 3 |
| `member_card` | 會員卡 | `orderCode` | 4 |
| `credit_card` | 信用卡載具 | `orderCode` | 5 |
| `icash` | icash | `orderCode` | 6 |
| `easycard` | 悠遊卡 | `orderCode` | 7 |
| `ipass` | 一卡通 | `orderCode` | 8 |
| `email` | 電子郵件載具 | `orderCode` | 9 |
| `donation` | 捐贈 | `donationCode` | 10 |

**使用說明**：
- 此表提供前端下拉選單資料來源
- `giveme_param` 欄位指示該載具類型對應到 API 的哪個參數
- 可透過 `is_active` 控制是否顯示特定載具類型

---

## 設計理念

### 為什麼用中間表？

#### ✅ 原因1：支援歷史記錄

**場景：發票作廢後重開**
```
時間點1：群組#101 開票
- 訂單#001, #002
- 發票#A001, #A002

時間點2：發票作廢，群組#102 重開
- 同樣的訂單#001, #002
- 新發票#A003, #A004

查詢結果：
- 訂單#001 曾在群組#101（status='voided'）
- 訂單#001 現在群組#102（status='active'）
```

如果用 `orders.invoice_group_id`，只能記錄最新的群組，無法保留歷史。

#### ✅ 原因2：支援部分開票（未來擴充）

雖然目前不需要，但如果未來要支援「訂單分批開票」：
```
訂單#001 ($1000)
- 第一批：群組#101 開 $600
- 第二批：群組#102 開 $400
```

中間表可以記錄：
```
invoice_group_orders:
- (group_id=101, order_id=1, order_amount=600)
- (group_id=102, order_id=1, order_amount=400)
```

#### ✅ 原因3：清晰的語義

```
「群組包含訂單」 - invoice_group_orders
「群組包含發票」 - invoice_group_invoices
```

這比 `orders.invoice_group_id` 更能表達「多對多」的關係。

---

## 核心業務邏輯

### 開票前驗證規則

1. **訂單金額驗證**
   - 查詢 `invoice_group_orders` 表，計算訂單在所有有效群組（status='active'）中的已開票金額
   - 確保訂單的「已開票金額」不超過訂單總額

2. **群組總額平衡驗證**
   - 計算群組內所有訂單金額總和（從 `invoice_group_orders.order_amount` 加總）
   - 計算群組內所有發票金額總和（從 `invoice_group_invoices.invoice_amount` 加總）
   - 兩者必須相等

3. **訂單重複檢查**
   - 檢查訂單是否已在其他有效群組中
   - 如果已存在，需要決定是否允許（目前設計：不允許）

---

## 開票流程

**重要說明：**
- 所有開票方式都必須建立群組（包括標準一對一）
- 建立發票資料時，狀態為 `pending`（尚未開立）
- 串接機迷坊 API 開立後，狀態變更為 `issued`

### 1. 標準開票（一對一）

**場景：** 訂單#001 ($1000) → 發票#A001 ($1000)

**步驟：**
1. 建立群組記錄（group_no, status='active', total_amount=1000）
2. 建立發票記錄（invoice_number暫空, total_amount=1000, status='pending'）
3. 建立群組-訂單關聯（group_id, order_id=1, order_amount=1000）
4. 建立群組-發票關聯（group_id, invoice_id, invoice_amount=1000）
5. 後續開立：呼叫機迷坊 API，取得 invoice_number，狀態變更為 'issued'

**交易：** 以上操作需在同一個資料庫交易中完成

**群組結構：** 1群組, 1訂單, 1發票

---

### 2. 拆單開票（一對多）

**場景：** 訂單#001 ($1000) → 發票#A001 ($600) + #A002 ($400)

**步驟：**
1. 建立群組記錄（group_no, status='active', total_amount=1000）
2. 建立群組-訂單關聯（order_id=1, order_amount=1000）
3. 迴圈建立發票（status='pending'）：
   - 發票#A001（amount=600）→ 建立群組-發票關聯（invoice_amount=600）
   - 發票#A002（amount=400）→ 建立群組-發票關聯（invoice_amount=400）
4. 後續開立：呼叫機迷坊 API，分別開立兩張發票，狀態變更為 'issued'

**驗證：** 拆分金額總和 (600+400) 必須等於訂單金額 (1000)

**群組結構：** 1群組, 1訂單, N發票

---

### 3. 合併開票（多對一）

**場景：** 訂單#001 ($1000) + #002 ($2000) → 發票#A001 ($3000)

**步驟：**
1. 建立群組記錄（group_no, status='active', total_amount=3000）
2. 建立群組-訂單關聯：
   - order_id=1, order_amount=1000
   - order_id=2, order_amount=2000
3. 建立發票記錄（amount=3000, status='pending'）
4. 建立群組-發票關聯（invoice_amount=3000）
5. 後續開立：呼叫機迷坊 API，取得 invoice_number，狀態變更為 'issued'

**驗證：** 訂單金額總和 (1000+2000) 必須等於發票金額 (3000)

**群組結構：** 1群組, N訂單, 1發票

---

### 4. 混合開票（多對多）

**場景：** 訂單#001 ($1000) + #002 ($2000) → 發票#A001 ($1500) + #A002 ($1500)

**步驟：**
1. 驗證：訂單總額 (3000) = 發票總額 (1500+1500)
2. 建立群組記錄（group_no, status='active', total_amount=3000）
3. 建立群組-訂單關聯：
   - order_id=1, order_amount=1000
   - order_id=2, order_amount=2000
4. 迴圈建立發票（status='pending'）和關聯：
   - 發票#A001 ($1500) → 群組-發票關聯
   - 發票#A002 ($1500) → 群組-發票關聯
5. 後續開立：呼叫機迷坊 API，分別開立兩張發票，狀態變更為 'issued'

**重點：** 不記錄「訂單A的$500分配到發票B」，只確保群組內總額平衡

**群組結構：** 1群組, N訂單, N發票

---

## 發票作廢流程

### 軟刪除（Status 標記）

**原則：** 標記為 `voided`，保留所有資料，不實體刪除

**步驟：**
1. 更新群組記錄：
   - status = 'voided'
   - void_reason = [原因]
   - voided_by = [操作人ID]
   - voided_at = [當前時間]

2. 更新群組內的所有發票：
   - 從 `invoice_group_invoices` 查詢該群組的所有發票ID
   - 批次更新發票記錄：
     - status = 'voided'
     - void_reason = [原因]
     - voided_by = [操作人ID]
     - voided_at = [當前時間]

**中間表記錄：** 不刪除，保持原樣（用於歷史查詢）

**優點：**
- ✅ 保留完整歷史
- ✅ 可查詢作廢記錄
- ✅ 可追蹤訂單的開票歷程
- ✅ 符合會計作業習慣（作廢 ≠ 刪除）

---

## 發票重開流程

**場景：** 客戶要求重新開立發票（使用相同訂單，但發票金額不同）

**步驟：**
1. 作廢舊群組（標記 status='voided'）
2. 從 `invoice_group_orders` 查詢舊群組的訂單列表
3. 計算訂單總額
4. 驗證新發票金額總和 = 訂單總額
5. 建立新群組（status='active'）
6. 使用相同的訂單建立新的群組-訂單關聯
7. 建立新發票和群組-發票關聯

**結果：**
- 舊群組：status='voided'（保留歷史）
- 新群組：status='active'（目前有效）
- 訂單在兩個群組都有記錄

---

## 查詢範例

### 1. 查詢群組的訂單和發票

**查詢群組的所有訂單：**
```sql
SELECT o.*, igo.order_amount
FROM orders AS o
JOIN invoice_group_orders AS igo ON igo.order_id = o.id
WHERE igo.group_id = 101;
```

**查詢群組的所有發票：**
```sql
SELECT i.*, igi.invoice_amount
FROM invoices AS i
JOIN invoice_group_invoices AS igi ON igi.invoice_id = i.id
WHERE igi.group_id = 101;
```

---

### 2. 查詢訂單的開票歷史

**訂單的所有開票記錄（包含已作廢）：**
```sql
SELECT ig.*, igo.order_amount
FROM invoice_groups AS ig
JOIN invoice_group_orders AS igo ON igo.group_id = ig.id
WHERE igo.order_id = 1
ORDER BY ig.created_at DESC;
```

**訂單的有效群組（未作廢）：**
```sql
SELECT ig.*
FROM invoice_groups AS ig
JOIN invoice_group_orders AS igo ON igo.group_id = ig.id
WHERE igo.order_id = 1 AND ig.status = 'active'
LIMIT 1;
```

---

### 3. 查詢發票的訂單來源

**發票#A001 對應的所有訂單：**
```sql
SELECT DISTINCT o.*
FROM orders AS o
JOIN invoice_group_orders AS igo ON igo.order_id = o.id
JOIN invoice_group_invoices AS igi ON igi.group_id = igo.group_id
JOIN invoices AS i ON i.id = igi.invoice_id
WHERE i.invoice_no = 'A001';
```

---

### 4. 查詢未開票訂單

**找出沒有在任何有效群組中的訂單：**
```sql
SELECT o.*
FROM orders AS o
LEFT JOIN (
    SELECT igo.order_id
    FROM invoice_group_orders AS igo
    JOIN invoice_groups AS ig ON ig.id = igo.group_id
    WHERE ig.status = 'active'
) AS active_orders ON active_orders.order_id = o.id
WHERE active_orders.order_id IS NULL;
```

---

### 5. 驗證群組總額平衡

**查詢群組的訂單總額和發票總額：**
```sql
SELECT
    (SELECT SUM(order_amount) FROM invoice_group_orders WHERE group_id = 101) AS order_total,
    (SELECT SUM(invoice_amount) FROM invoice_group_invoices WHERE group_id = 101) AS invoice_total;
```

**驗證：** order_total 必須等於 invoice_total

---

### 6. 統計報表

**某期間的開票統計：**
```sql
SELECT
    COUNT(DISTINCT ig.id) AS group_count,
    COUNT(DISTINCT igo.order_id) AS order_count,
    COUNT(DISTINCT igi.invoice_id) AS invoice_count,
    SUM(ig.total_amount) AS total_amount
FROM invoice_groups AS ig
LEFT JOIN invoice_group_orders AS igo ON igo.group_id = ig.id
LEFT JOIN invoice_group_invoices AS igi ON igi.group_id = ig.id
WHERE ig.created_at BETWEEN '2024-01-01' AND '2024-01-31'
  AND ig.status = 'active';
```

---

## Migration 檔案

Migration 檔案位置：
```
database/migrations/2025_10_30_182219_create_invoice_tables.php
```

此 migration 建立以下資料表：
1. `invoice_groups` - 開票群組表
2. `invoices` - 發票主表
3. `invoice_items` - 發票項目表
4. `invoice_group_orders` - 群組-訂單關聯表
5. `invoice_group_invoices` - 群組-發票關聯表
6. `invoice_carrier_types` - 載具類型表

**執行 Migration**：
```bash
./php.bat artisan migrate --path=database/migrations/2025_10_30_182219_create_invoice_tables.php
```

---

## 資料完整性保證

### Foreign Key 約束

1. **CASCADE 刪除**
   - 刪除群組時，自動刪除 `invoice_group_orders` 和 `invoice_group_invoices` 的關聯記錄
   - 用途：清理測試資料或錯誤建立的群組

2. **RESTRICT 刪除**
   - 禁止刪除已有群組關聯的訂單
   - 禁止刪除已有群組關聯的發票
   - 保護：防止誤刪重要資料

### Unique Key 約束

1. **invoice_groups.group_no**
   - 群組編號不可重複

2. **invoices.invoice_no**
   - 發票號碼不可重複

3. **invoice_group_orders (group_id, order_id)**
   - 同一訂單不可在同一群組重複加入

4. **invoice_group_invoices (group_id, invoice_id)**
   - 同一發票不可在同一群組重複加入

---

## Model 查詢建議

### 使用 Global Scope 預設過濾作廢記錄

建議在 `InvoiceGroup` Model 加入 Global Scope，讓預設查詢只抓取 `status='active'` 的記錄：

**效果：**
```
InvoiceGroup::all()               // 只查 active
InvoiceGroup::withVoided()->get() // 查全部（包含 voided）
InvoiceGroup::onlyVoided()->get() // 只查 voided
```

**優點：**
- 防止誤查到已作廢的群組
- 符合業務邏輯（一般情況下只需要有效的群組）
- 需要查歷史時，明確使用 `withVoided()`

---

## API 串接說明

發票的開立、作廢相關串接，使用**機迷坊**廠商，縮寫 **Giveme**。如果欄位或是代碼有看到此字樣，代表跟機迷坊有關。

詳細的 API 串接文件請參考：`統一發票串接.md`

---

## 總結

### 核心設計原則

1. **群組概念** - 訂單和發票透過「群組」關聯，不直接對應
2. **總額平衡** - 群組內訂單總額 = 發票總額
3. **中間表** - 使用兩張中間表保留完整歷史記錄
4. **狀態標記** - 使用 `status` 欄位標記作廢，不使用 Laravel SoftDelete

### 資料表總覽

| 資料表 | 用途 | 關鍵欄位 |
|--------|------|----------|
| `invoice_groups` | 開票群組 | group_no, status, total_amount, voided_at |
| `invoice_group_orders` | 群組-訂單關聯 | group_id, order_id, order_amount |
| `invoice_group_invoices` | 群組-發票關聯 | group_id, invoice_id, invoice_amount |
| `invoices` | 發票主表 | invoice_number, total_amount, status, carrier_type |
| `invoice_items` | 發票項目 | invoice_id, name, price, quantity, subtotal |
| `invoice_carrier_types` | 載具類型 | code, name, giveme_param |
| `orders` | 訂單（原有） | id, amount |

### 支援場景

- ✅ 一對一開票
- ✅ 拆單開票
- ✅ 合併開票
- ✅ 混合開票
- ✅ 發票作廢
- ✅ 發票重開
- ✅ 歷史記錄查詢
- ✅ 未來擴充部分開票

### 作廢機制

- 使用 `status` 欄位（active/voided）
- 記錄作廢時間 `voided_at`
- 記錄作廢人 `voided_by`
- 記錄作廢原因 `void_reason`
- 不使用 Laravel SoftDelete（deleted_at）

---

**文件版本**: 2.0
**最後更新**: 2025-10-30
**相關文件**: `統一發票串接.md`, `CLAUDE.md`, `Reports.md`

**文件說明**:
本文件為發票系統業務設計文件，包含：
1. **業務需求**：四種開票方式（標準、拆單、合併、混合）
2. **資料表設計**：群組開票架構（invoice_groups, invoice_group_orders, invoice_group_invoices）
3. **欄位定義**：支援電子發票 API 的完整欄位
4. **開票流程**：本地資料庫操作邏輯
5. **查詢範例**：常用業務查詢 SQL

如需了解與第三方電子發票平台的 API 串接，請參考 `統一發票串接.md`。
