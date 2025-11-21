# Controller 範例說明

## getList() 標準結構

```php
protected function getList(Request $request): string
{
    // ========== 第一段：Query 與網址參數 ==========
    // 使用 OrmHelper 自動解析前端參數
    // 注意：查詢參數必須是 filter_* 或 equal_* 開頭
    $query = Role::query()->with(['permissions']);
    $filter_data = $request->all();
    OrmHelper::prepare($query, $filter_data);

    // ========== 第二段：search 關鍵字查詢（通用做法）==========
    // search 參數不會被 OrmHelper 自動處理，需手動指定查詢欄位
    if ($request->has('search') && $request->search) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            OrmHelper::filterOrEqualColumn($q, 'filter_name', $search);

            $q->orWhere(function ($subQ) use ($search) {
                OrmHelper::filterOrEqualColumn($subQ, 'filter_description', $search);
            });
        });
    }

    // ========== 第三段：額外定義查詢關聯（需要時）==========
    // OrmHelper 無法自動處理的關聯查詢
    if ($request->has('filter_permission_id') && $request->filter_permission_id) {
        $query->whereHas('permissions', function($q) use ($request) {
            $q->where('id', $request->filter_permission_id);
        });
    }

    // ========== 第四段：預設 sort, order ==========
    $filter_data['sort'] = $request->get('sort', 'created_at');
    $filter_data['order'] = $request->get('order', 'desc');

    // ========== 第五段：使用 OrmHelper 獲取結果（自動處理分頁）==========
    $rows = OrmHelper::getResult($query, $filter_data);

    // ========== 第六段：buildUrlParams（通用）==========
    $url = $this->buildUrlParams($request);

    // ========== 第七段：準備資料 ==========
    $data['rows'] = $rows;
    $data['action'] = route('lang.admin.system.access.roles.list') . $url;
    $data['url_params'] = $url;

    // ========== 返回 ==========
    return view('admin.system.access.role_list', $data)->render();
}
```

---

## 各段說明

### 第一段：Query 與網址參數

```php
$query = Role::query()->with(['permissions']);
$filter_data = $request->all();
OrmHelper::prepare($query, $filter_data);
```

- 直接使用 Model 建立 Query，不經過 Service 或 Repository
- `with()` 預載入需要的關聯
- `OrmHelper::prepare()` 自動處理：
  - `filter_*` 參數 → LIKE 查詢
  - `equal_*` 參數 → 精確查詢
  - `sort` / `order` → 排序

### 第二段：search 關鍵字查詢

```php
if ($request->has('search') && $request->search) {
    $search = $request->search;
    $query->where(function ($q) use ($search) {
        OrmHelper::filterOrEqualColumn($q, 'filter_name', $search);
        $q->orWhere(function ($subQ) use ($search) {
            OrmHelper::filterOrEqualColumn($subQ, 'filter_email', $search);
        });
    });
}
```

- `search` 是通用關鍵字，會查詢多個欄位
- 每個 Controller 需自行定義要查詢哪些欄位
- 使用 `orWhere` 組合多欄位查詢

### 第三段：額外關聯查詢

```php
if ($request->has('filter_permission_id') && $request->filter_permission_id) {
    $query->whereHas('permissions', function($q) use ($request) {
        $q->where('id', $request->filter_permission_id);
    });
}
```

- OrmHelper 無法自動處理關聯查詢
- 需要時手動加入 `whereHas`

### 第四段：預設排序

```php
$filter_data['sort'] = $request->get('sort', 'created_at');
$filter_data['order'] = $request->get('order', 'desc');
```

- 設定預設排序欄位和方向
- 如果 URL 有帶參數則使用 URL 參數

### 第五段：獲取結果

```php
$rows = OrmHelper::getResult($query, $filter_data);
```

- 自動處理分頁
- 返回 `LengthAwarePaginator` 或 `Collection`

### 第六段：buildUrlParams

```php
$url = $this->buildUrlParams($request);
```

- 建構 URL 參數字串
- 用於編輯連結、分頁連結
- **此方法應定義在 BackendController**

### 第七段：準備資料

```php
$data['rows'] = $rows;
$data['action'] = route('...') . $url;
$data['url_params'] = $url;
```

- 準備傳給 View 的資料
- `action` 用於表單提交
- `url_params` 用於編輯連結

---

## 錯誤處理

**不使用 try-catch**，全部由 `app/Exceptions/Handler.php` 統一處理。

```php
// ❌ 不要這樣寫
try {
    $result = $this->Service->doSomething();
} catch (\Exception $e) {
    return response()->json(['error' => $e->getMessage()]);
}

// ✅ 直接寫，讓 Handler 處理
$result = $this->Service->doSomething();
```

---

## buildUrlParams 方法（定義在 BackendController）

```php
protected function buildUrlParams(Request $request): string
{
    $params = [];

    // 允許的欄位列表
    $allowedFields = ['name', 'email', 'is_active'];

    // 支援的前綴
    $prefixes = ['filter_', 'equal_'];

    foreach ($allowedFields as $field) {
        foreach ($prefixes as $prefix) {
            $key = $prefix . $field;
            if ($request->filled($key)) {
                $params[] = $key . '=' . urlencode($request->get($key));
            }
        }
    }

    // 分頁參數
    if ($request->filled('limit')) {
        $params[] = 'limit=' . $request->limit;
    }
    if ($request->filled('page')) {
        $params[] = 'page=' . $request->page;
    }

    // 排序參數
    if ($request->filled('sort')) {
        $params[] = 'sort=' . urlencode($request->sort);
    }
    if ($request->filled('order')) {
        $params[] = 'order=' . urlencode($request->order);
    }

    return $params ? '?' . implode('&', $params) : '';
}
```

---

## index() 與 list() 結構

```php
public function index(Request $request)
{
    $data['lang'] = $this->lang;
    $data['list'] = $this->getList($request);

    // ... breadcrumbs, urls ...

    return view('admin.xxx.index', $data);
}

public function list(Request $request)
{
    // AJAX 請求，僅返回表格 HTML
    return $this->getList($request);
}
```

---

---

## 架構層級

```
Controller
    ↓ 引用
Repository ($this->repo->query())
    ↓ 操作
Model
```

### 設計原則

- **Controller 不直接操作 Model**
- Controller 引用 Repository（如 RoleRepository, UserRepository）
- 後台、API、前台的 Controller 都引用同一個 Repository
- `$model` 用於實例，查詢使用 `$repo->query()`

### Repository 基類

```php
// app/Repositories/Repository.php
abstract class Repository
{
    protected Model $model;

    public function __construct()
    {
        $this->model = app($this->model());
    }

    abstract protected function model(): string;

    public function query()
    {
        return $this->model->newQuery();
    }

    public function getModel(): Model
    {
        return $this->model;
    }
}
```

### 使用方式

```php
// 查詢
$role = $this->roleRepo->query()->find($id);
$role = $this->roleRepo->query()->findOrFail($id);
$roles = $this->roleRepo->query()->where('is_active', 1)->get();

// 列表（配合 OrmHelper）
$query = $this->roleRepo->query()->with(['permissions']);
OrmHelper::prepare($query, $filter_data);
$rows = OrmHelper::getResult($query, $filter_data);

// 新增
$newRole = $this->roleRepo->query()->create($data);

// 更新
$this->roleRepo->query()->find($id)->update($data);

// 刪除
$this->roleRepo->query()->find($id)->delete();
```

---

## 參考

- 帳號中心：`portals/TailwindAdmin/Http/Controllers/System/UserController.php`
- OrmHelper：`app/Helpers/Classes/OrmHelper.php`
- Handler：`app/Exceptions/Handler.php`
- Repository 基類：`app/Repositories/Repository.php`
