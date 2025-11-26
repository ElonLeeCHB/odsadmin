@extends('admin.app')

@section('pageJsCss')
@endsection

@section('columnLeft')
  @include('admin.common.column_left')
@endsection

@section('content')
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="float-end">
        <button type="submit" form="form-permission" data-bs-toggle="tooltip" title="儲存" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="{{ $back }}" data-bs-toggle="tooltip" title="{{ $lang->button_back ?? '返回' }}" class="btn btn-light"><i class="fas fa-reply"></i></a>
      </div>
      <h1>權限管理</h1>
      @include('admin.common.breadcumb')
    </div>
  </div>
    <div class="container-fluid">
      <div class="card">
        <div class="card-header"><i class="fas fa-pencil-alt"></i> {{ $lang->text_form ?? '表單' }}</div>
        <div class="card-body">
          <form id="form-permission" action="{{ $save }}" method="post" data-oc-toggle="ajax">
            @csrf
            @method('POST')

            <div class="tab-content">
              <div id="tab-data" class="tab-pane active">

                {{-- Type 類型 --}}
                <div class="row mb-3 required">
                  <label for="input-type" class="col-sm-2 col-form-label">類型</label>
                  <div class="col-sm-10">
                    <select id="input-type" name="type" class="form-select">
                      <option value="menu" @if(($permission->type ?? 'menu') == 'menu') selected @endif>選單項目 (menu)</option>
                      <option value="action" @if(($permission->type ?? '') == 'action') selected @endif>功能權限 (action)</option>
                    </select>
                    <div class="form-text">menu: 顯示於選單 | action: 功能按鈕/操作權限</div>
                    <div id="error-type" class="invalid-feedback"></div>
                  </div>
                </div>

                {{-- 父層選單 --}}
                <div class="row mb-3" id="row-parent">
                  <label for="input-parent_id" class="col-sm-2 col-form-label">父層選單</label>
                  <div class="col-sm-10">
                    <select id="input-parent_id" name="parent_id" class="form-select">
                      <option value="">-- 無（頂層） --</option>
                      @if(isset($parent_permissions))
                        @foreach($parent_permissions as $parent)
                          <option value="{{ $parent->id }}" @if(($permission->parent_id ?? '') == $parent->id) selected @endif>
                            {{ str_repeat('　', $parent->getLevel()) }}{{ $parent->title }} ({{ $parent->name }})
                          </option>
                        @endforeach
                      @endif
                    </select>
                    <div class="form-text">選擇此權限的父層選單（選填）</div>
                    <div id="error-parent_id" class="invalid-feedback"></div>
                  </div>
                </div>

                {{-- 權限代碼 --}}
                <div class="row mb-3 required">
                  <label for="input-name" class="col-sm-2 col-form-label">權限代碼</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <input type="text" id="input-name" name="name" value="{{ $permission->name ?? '' }}" class="form-control" placeholder="例如：admin.sales.order, admin.order.export"/>
                    </div>
                    <div class="form-text">格式：系統.模組.作業.功能（例如：admin.sales.order.list, pos.dashboard）</div>
                    <div id="error-name" class="invalid-feedback"></div>
                  </div>
                </div>

                {{-- 顯示名稱 --}}
                <div class="row mb-3 required" id="row-title">
                  <label for="input-title" class="col-sm-2 col-form-label">顯示名稱</label>
                  <div class="col-sm-10">
                    <input type="text" id="input-title" name="title" value="{{ $permission->title ?? '' }}" class="form-control" placeholder="例如：訂單管理、使用者列表"/>
                    <div class="form-text">顯示於選單上的文字</div>
                    <div id="error-title" class="invalid-feedback"></div>
                  </div>
                </div>

                {{-- 權限說明 --}}
                <div class="row mb-3">
                  <label for="input-description" class="col-sm-2 col-form-label">權限說明</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <textarea id="input-description" name="description" class="form-control" rows="3" placeholder="請輸入權限說明">{{ $permission->description ?? '' }}</textarea>
                    </div>
                    <div class="form-text">描述此權限的用途（選填）</div>
                    <div id="error-description" class="invalid-feedback"></div>
                  </div>
                </div>

                {{-- 圖示 --}}
                <div class="row mb-3" id="row-icon">
                  <label for="input-icon" class="col-sm-2 col-form-label">圖示</label>
                  <div class="col-sm-10">
                    <input type="text" id="input-icon" name="icon" value="{{ $permission->icon ?? '' }}" class="form-control" placeholder="例如：fas fa-shopping-cart"/>
                    <div class="form-text">FontAwesome 圖示 class（選填，僅選單項目需要）</div>
                    <div id="error-icon" class="invalid-feedback"></div>
                  </div>
                </div>

                {{-- 排序 --}}
                <div class="row mb-3" id="row-sort">
                  <label for="input-sort_order" class="col-sm-2 col-form-label">排序</label>
                  <div class="col-sm-10">
                    <input type="number" id="input-sort_order" name="sort_order" value="{{ $permission->sort_order ?? 0 }}" class="form-control"/>
                    <div class="form-text">數字越小越前面</div>
                    <div id="error-sort_order" class="invalid-feedback"></div>
                  </div>
                </div>

                {{-- Guard Name --}}
                <div class="row mb-3">
                  <label for="input-guard_name" class="col-sm-2 col-form-label">Guard Name</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <select id="input-guard_name" name="guard_name" class="form-select">
                        <option value="web" @if(($permission->guard_name ?? 'web') == 'web') selected @endif>web</option>
                        <option value="api" @if(($permission->guard_name ?? '') == 'api') selected @endif>api</option>
                      </select>
                    </div>
                    <div class="form-text">一般使用 web，API 使用 api</div>
                    <div id="error-guard_name" class="invalid-feedback"></div>
                  </div>
                </div>

                @if($permission_id)
                {{-- 建立時間 --}}
                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label">建立時間</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <input type="text" value="{{ $permission->created_at }}" class="form-control" disabled/>
                    </div>
                  </div>
                </div>

                {{-- 更新時間 --}}
                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label">更新時間</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <input type="text" value="{{ $permission->updated_at }}" class="form-control" disabled/>
                    </div>
                  </div>
                </div>
                @endif

              </div>
              <input type="hidden" id="input-permission_id" name="permission_id" value="{{ $permission_id }}"/>
            </div>
          </form>
          </div>
          </div>
        </div>
    </div>
</div>
@endsection

@section('buttom')
<script type="text/javascript">
// 根據 type 動態顯示/隱藏欄位
document.addEventListener('DOMContentLoaded', function() {
  const typeSelect = document.getElementById('input-type');
  const rowParent = document.getElementById('row-parent');
  const rowTitle = document.getElementById('row-title');
  const rowIcon = document.getElementById('row-icon');
  const rowSort = document.getElementById('row-sort');

  function toggleFields() {
    const isMenu = typeSelect.value === 'menu';

    // menu 類型才需要這些欄位
    rowParent.style.display = isMenu ? '' : 'none';
    rowTitle.style.display = isMenu ? '' : 'none';
    rowIcon.style.display = isMenu ? '' : 'none';
    rowSort.style.display = isMenu ? '' : 'none';

    // action 類型時清空這些欄位
    if (!isMenu) {
      document.getElementById('input-parent_id').value = '';
      document.getElementById('input-icon').value = '';
    }
  }

  // 初始化
  toggleFields();

  // 監聽變更
  typeSelect.addEventListener('change', toggleFields);
});
</script>
@endsection
