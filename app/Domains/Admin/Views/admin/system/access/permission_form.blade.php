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
      <h1>{{ $lang->heading_title ?? '權限管理' }}</h1>
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

                {{-- 權限名稱 --}}
                <div class="row mb-3 required">
                  <label for="input-name" class="col-sm-2 col-form-label">權限名稱</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <input type="text" id="input-name" name="name" value="{{ $permission->name ?? '' }}" class="form-control" placeholder="例如：pos.MainPage, admin.users.view"/>
                    </div>
                    <div class="form-text">建議格式：系統前綴.功能名稱（例如：pos.MainPage, admin.users.view）</div>
                    <div id="error-name" class="invalid-feedback"></div>
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
// 可以在此加入額外的 JavaScript
</script>
@endsection
