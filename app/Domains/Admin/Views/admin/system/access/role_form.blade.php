@extends('admin.app')

@section('pageJsCss')
<style>
.permission-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 10px;
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    padding: 15px;
    border-radius: 5px;
    background-color: #f8f9fa;
}
.permission-item {
    padding: 5px;
}
</style>
@endsection

@section('columnLeft')
  @include('admin.common.column_left')
@endsection

@section('content')
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="float-end">
        <button type="submit" form="form-role" data-bs-toggle="tooltip" title="儲存" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="{{ $back }}" data-bs-toggle="tooltip" title="返回" class="btn btn-light"><i class="fas fa-reply"></i></a>
      </div>
      <h1>角色管理</h1>
      @include('admin.common.breadcumb')
    </div>
  </div>
    <div class="container-fluid">
      <div class="card">
        <div class="card-header"><i class="fas fa-pencil-alt"></i> {{ $lang->text_form ?? '編輯' }}</div>
        <div class="card-body">
          <ul class="nav nav-tabs">
            <li class="nav-item"><a href="#tab-general" data-bs-toggle="tab" class="nav-link active">基本資料</a></li>
            <li class="nav-item"><a href="#tab-permissions" data-bs-toggle="tab" class="nav-link">權限設定</a></li>
          </ul>
          <form id="form-role" action="{{ $save }}" method="post" data-oc-toggle="ajax">
            @csrf
            @method('POST')

            <div class="tab-content">
              <div id="tab-general" class="tab-pane active">

                {{-- 角色代碼 --}}
                <div class="row mb-3 required">
                  <label for="input-name" class="col-sm-2 col-form-label">角色代碼</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <input type="text" id="input-name" name="name" value="{{ $role->name ?? '' }}" class="form-control" placeholder="例如：admin、editor"/>
                    </div>
                    <div class="form-text">系統內部使用的唯一識別碼（英文小寫）</div>
                    <div id="error-name" class="invalid-feedback"></div>
                  </div>
                </div>

                {{-- 顯示名稱 --}}
                <div class="row mb-3">
                  <label for="input-title" class="col-sm-2 col-form-label">顯示名稱</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <input type="text" id="input-title" name="title" value="{{ $role->title ?? '' }}" class="form-control" placeholder="例如：管理員、編輯者"/>
                    </div>
                    <div class="form-text">使用者介面顯示的名稱</div>
                    <div id="error-title" class="invalid-feedback"></div>
                  </div>
                </div>

                {{-- 角色說明 --}}
                <div class="row mb-3">
                  <label for="input-description" class="col-sm-2 col-form-label">角色說明</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <textarea id="input-description" name="description" class="form-control" rows="3" placeholder="請輸入角色說明">{{ $role->description ?? '' }}</textarea>
                    </div>
                    <div class="form-text">描述此角色的用途（選填）</div>
                    <div id="error-description" class="invalid-feedback"></div>
                  </div>
                </div>

                {{-- Guard Name --}}
                <div class="row mb-3">
                  <label for="input-guard_name" class="col-sm-2 col-form-label">Guard Name</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <select id="input-guard_name" name="guard_name" class="form-select">
                        <option value="web" @if(($role->guard_name ?? 'web') == 'web') selected @endif>web</option>
                        <option value="api" @if(($role->guard_name ?? '') == 'api') selected @endif>api</option>
                      </select>
                    </div>
                    <div class="form-text">一般使用 web，API 使用 api</div>
                    <div id="error-guard_name" class="invalid-feedback"></div>
                  </div>
                </div>

                @if($role_id)
                {{-- 建立時間 --}}
                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label">建立時間</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <input type="text" value="{{ $role->created_at }}" class="form-control" disabled/>
                    </div>
                  </div>
                </div>

                {{-- 更新時間 --}}
                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label">更新時間</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <input type="text" value="{{ $role->updated_at }}" class="form-control" disabled/>
                    </div>
                  </div>
                </div>
                @endif

              </div>

              <div id="tab-permissions" class="tab-pane">
                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label">權限設定</label>
                  <div class="col-sm-10">
                    <div class="mb-3">
                      <button type="button" class="btn btn-sm btn-secondary" onclick="selectAllPermissions()">全選</button>
                      <button type="button" class="btn btn-sm btn-secondary" onclick="deselectAllPermissions()">全不選</button>
                    </div>
                    <div class="permission-grid">
                      @foreach($permissions as $permission)
                      <div class="permission-item">
                        <div class="form-check">
                          <input type="checkbox"
                                 class="form-check-input permission-checkbox"
                                 name="permissions[]"
                                 value="{{ $permission->id }}"
                                 id="permission-{{ $permission->id }}"
                                 @if(in_array($permission->id, $role_permissions)) checked @endif>
                          <label class="form-check-label" for="permission-{{ $permission->id }}">
                            {{ $permission->name }}
                          </label>
                        </div>
                      </div>
                      @endforeach
                    </div>
                    @if(count($permissions) == 0)
                    <div class="alert alert-warning">目前沒有任何權限，請先建立權限。</div>
                    @endif
                  </div>
                </div>
              </div>

              <input type="hidden" id="input-role_id" name="role_id" value="{{ $role_id }}"/>
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
function selectAllPermissions() {
    $('.permission-checkbox').prop('checked', true);
}

function deselectAllPermissions() {
    $('.permission-checkbox').prop('checked', false);
}
</script>
@endsection
