# 為何使用 system_users 資料表

> 將來資料表名稱 `system_users` 改為 `admin_users`

**文件日期**: 2026-01-02
**相關路徑**: `database/migrations/2025_11_19_190019_create_system_users_table.php`

---

## 背景

本系統後台需要管理使用者的訪問權限，同時整合帳號中心 (accounts.huabing.tw) 進行統一登入驗證。

### 系統架構

```
┌─────────────────────────────────────────────────────────┐
│  帳號中心 accounts.huabing.tw                            │
│  ├── 統一管理所有帳號密碼（類似 id.atlassian.com）        │
│  ├── user_oauth_clients（用戶可用哪些系統）              │
│  └── OAuth 登入驗證                                      │
└─────────────────────────────────────────────────────────┘
                    │
                    │ 第一層：能否登入此系統
                    ▼
┌─────────────────────────────────────────────────────────┐
│  子系統 pos.huabing.tw                                   │
│  ├── system_users（本系統使用者狀態與訪問記錄）          │
│  └── roles/permissions（本系統角色權限）                 │
└─────────────────────────────────────────────────────────┘
                    │
                    │ 第二層：系統內權限控制
```

---

## 為何需要 system_users 資料表

### 1. 保留歷史使用者記錄

如果只使用 `users.is_admin` 或角色來篩選後台使用者：
- 員工離職後移除角色 → 列表頁看不到此人
- 無法追蹤「誰曾經有過後台權限」

使用 `system_users` 可以：
- 在「系統管理 / 訪問控制 / 使用者」看到所有歷史使用者
- 即使停用權限，仍保留記錄供審計

### 2. 區分全域帳號與後台權限

| 欄位 | 用途 | 範圍 |
|------|------|------|
| `users.is_active` | 帳號是否啟用 | 全域（影響所有系統） |
| `system_users.is_active` | 後台權限是否啟用 | 僅本系統後台 |

**情境範例**：
- 員工 A 調離後台職務：`system_users.is_active = 0`，但帳號仍可用於其他系統
- 員工 B 離職：帳號中心移除系統權限，無法登入

### 3. 追蹤訪問記錄

`system_users` 記錄：
- `first_access_at` - 首次訪問後台時間
- `last_access_at` - 最後訪問時間
- `access_count` - 訪問次數

這些資訊對於審計和使用分析很有價值。

---

## 資料表結構

```sql
CREATE TABLE system_users (
    user_id         BIGINT UNSIGNED PRIMARY KEY,  -- users.id
    user_code       VARCHAR(20),                   -- users.code
    first_access_at TIMESTAMP,                     -- 首次訪問時間
    last_access_at  TIMESTAMP,                     -- 最後訪問時間
    access_count    INT UNSIGNED DEFAULT 0,        -- 訪問次數
    is_active       BOOLEAN DEFAULT 0,             -- 後台權限是否啟用
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## 曾考慮但未採用的方案

### 方案 A：只用 users.is_admin
- 已標記為 DEPRECATED（廢棄）
- 改由帳號中心的 user_oauth_clients 管理系統權限

### 方案 B：只用角色（如 basic_admin）
- 無法保留歷史記錄
- 移除角色後列表頁看不到該使用者

### 方案 C：移除 system_users.is_active，只保留訪問追蹤
- 可行，但減少了細粒度控制
- 無法在子系統層面暫停特定用戶

---

## 權限檢查流程

1. **帳號中心登入** → 檢查 `user_oauth_clients` 是否有本系統權限
2. **進入後台** → 檢查 `system_users.is_active` 是否啟用
3. **操作功能** → 檢查角色權限 (Spatie Permission)

---

## 相關檔案

- Model: `app/Models/Access/SystemUser.php`
- Repository: `app/Repositories/Access/SystemUserRepository.php`
- Middleware: `app/Http/Middleware/TrackSystemAccess.php`
- Controller: `app/Domains/Admin/Http/Controllers/System/Access/UserController.php`
- View: `app/Domains/Admin/Views/admin/system/access/user_form.blade.php`

---

## 結論

保留 `system_users` 資料表的設計，因為：

1. **歷史記錄**：可查看所有曾使用過後台的人員
2. **層級分離**：全域帳號狀態與後台權限分開管理
3. **訪問追蹤**：記錄首次/最後訪問時間及次數
4. **彈性控制**：可在不影響全域帳號的情況下停用後台權限
