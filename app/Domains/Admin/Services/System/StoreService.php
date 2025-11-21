<?php

namespace App\Domains\Admin\Services\System;

use App\Models\Store;
use App\Services\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StoreService extends Service
{
    protected $modelName = "App\\Models\\Store";

    public function __construct()
    {
        $this->model = new Store();
    }

    /**
     * 取得門市列表
     */
    public function getStores($data = [])
    {
        $query = Store::query();

        // 載入關聯以避免 N+1 query
        $query->with(['state', 'city', 'manager']);

        // 搜尋過濾
        if (!empty($data['filter_name'])) {
            $query->where('name', 'like', '%' . $data['filter_name'] . '%');
        }

        if (!empty($data['filter_code'])) {
            $query->where('code', 'like', '%' . $data['filter_code'] . '%');
        }

        if (isset($data['filter_is_active'])) {
            $query->where('is_active', $data['filter_is_active']);
        }

        // 排序
        $sort = $data['sort'] ?? 'id';
        $order = $data['order'] ?? 'ASC';
        $query->orderBy($sort, $order);

        // 分頁
        $limit = $data['limit'] ?? 20;

        return $query->paginate($limit);
    }

    /**
     * 查找或創建新記錄
     */
    public function findOrFailOrNew($id = null)
    {
        if ($id) {
            return Store::findOrFail($id);
        }

        return new Store();
    }

    /**
     * 驗證器
     */
    public function validator($data)
    {
        $rules = [
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
        ];

        // 如果提供了代碼，檢查唯一性
        if (!empty($data['code'])) {
            if (!empty($data['store_id'])) {
                $rules['code'] .= '|unique:stores,code,' . $data['store_id'];
            } else {
                $rules['code'] .= '|unique:stores,code';
            }
        }

        $messages = [
            'code.unique' => '門市代碼已存在',
            'name.required' => '門市名稱為必填',
        ];

        return Validator::make($data, $rules, $messages);
    }

    /**
     * 建立或更新門市
     */
    public function updateOrCreate($data)
    {
        DB::beginTransaction();

        try {
            $store_id = $data['store_id'] ?? null;

            if ($store_id) {
                $store = Store::findOrFail($store_id);
            } else {
                $store = new Store();
            }

            $store->code = $data['code'];
            $store->name = $data['name'];
            $store->state_id = $data['state_id'] ?? null;
            $store->city_id = $data['city_id'] ?? null;
            $store->address = $data['address'] ?? null;
            $store->phone = $data['phone'] ?? null;
            $store->manager_id = $data['manager_id'] ?? null;
            $store->is_active = $data['is_active'] ?? true;

            $store->save();

            $result['data']['store_id'] = $store->id;

            DB::commit();

            return $result;

        } catch (\Exception $e) {
            DB::rollBack();

            $result['error'] = $e->getMessage();

            return $result;
        }
    }

    /**
     * 刪除門市
     */
    public function destroy($ids)
    {
        DB::beginTransaction();

        try {
            if (!is_array($ids)) {
                $ids = [$ids];
            }

            foreach ($ids as $id) {
                $store = Store::findOrFail($id);
                $store->delete();
            }

            DB::commit();

            return ['success' => true];

        } catch (\Exception $e) {
            DB::rollBack();

            return ['error' => $e->getMessage()];
        }
    }
}
