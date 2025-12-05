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
        <button type="submit" form="form-user" data-bs-toggle="tooltip" title="{{ $lang->save }}" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="{{ $back_url }}" data-bs-toggle="tooltip" title="Back" class="btn btn-light"><i class="fas fa-reply"></i></a>
      </div>
      <h1>{{ $lang->heading_title }}</h1>
      @include('admin.common.breadcumb')
    </div>
  </div>
    <div class="container-fluid">
      <div class="card">
        <div class="card-header"><i class="fas fa-pencil-alt"></i> {{ $lang->text_form }}</div>
        <div class="card-body">
          <form id="form-user" action="{{ $save_url }}" method="post" data-oc-toggle="ajax">
            @csrf
            @method('POST')

            <fieldset>
              <legend>使用者資料 <small class="text-muted fw-normal">(串接帳號中心時，基本資料及密碼請到帳號中心修改。)</small></legend>

              <div class="row mb-3">
                <label class="col-sm-2 col-form-label">{{ $lang->column_username }}</label>
                <div class="col-sm-10">
                  <input type="text" value="{{ $user->username }}" class="form-control" disabled/>
                </div>
              </div>

              <div class="row mb-3">
                <label class="col-sm-2 col-form-label">{{ $lang->column_name }}</label>
                <div class="col-sm-10">
                  <input type="text" value="{{ $user->name }}" class="form-control" disabled/>
                </div>
              </div>

              <div class="row mb-3">
                <label class="col-sm-2 col-form-label">{{ $lang->column_email }}</label>
                <div class="col-sm-10">
                  <input type="text" value="{{ $user->email }}" class="form-control" disabled/>
                </div>
              </div>

              <div class="row mb-3">
                <label class="col-sm-2 col-form-label">{{ $lang->column_mobile }}</label>
                <div class="col-sm-10">
                  <input type="text" value="{{ $user->mobile }}" class="form-control" disabled/>
                </div>
              </div>

              @if($user->code)
              <div class="row mb-3">
                <label class="col-sm-2 col-form-label">會員編號</label>
                <div class="col-sm-10">
                  <input type="text" value="{{ $user->code }}" class="form-control" disabled/>
                </div>
              </div>
              @endif
            </fieldset>

            <fieldset>
              <legend>修改密碼 <small class="text-muted fw-normal">(留空則不修改密碼)</small></legend>

              <div class="row mb-3">
                <label class="col-sm-2 col-form-label">新密碼</label>
                <div class="col-sm-10">
                  <input type="password" name="password" id="input-password" placeholder="輸入新密碼" class="form-control" autocomplete="new-password"/>
                </div>
              </div>

              <div class="row mb-3">
                <label class="col-sm-2 col-form-label">確認密碼</label>
                <div class="col-sm-10">
                  <input type="password" name="password_confirmation" id="input-password-confirmation" placeholder="再次輸入新密碼" class="form-control" autocomplete="new-password"/>
                </div>
              </div>
            </fieldset>

            <fieldset>
              <legend>訪問控制設定</legend>

              <div class="row mb-3">
                <label class="col-sm-2 col-form-label">{{ $lang->column_is_active }}</label>
                <div class="col-sm-10">
                  <select name="is_active" id="input-is_active" class="form-select">
                    <option value="1" {{ ($system_user->is_active ?? true) ? 'selected' : '' }}>{{ $lang->text_yes }}</option>
                    <option value="0" {{ !($system_user->is_active ?? true) ? 'selected' : '' }}>{{ $lang->text_no }}</option>
                  </select>
                </div>
              </div>

              <div class="row mb-3">
                <label class="col-sm-2 col-form-label">角色</label>
                <div class="col-sm-10">
                  <div style="max-height: 200px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 4px; padding: 8px;">
                  @foreach($roles as $role)
                    <div class="form-check">
                      <input type="checkbox" name="user_role[]" value="{{ $role->id }}" id="role-{{ $role->id }}" class="form-check-input" {{ in_array($role->id, $user_role_ids) ? 'checked' : '' }}/>
                      <label for="role-{{ $role->id }}" class="form-check-label">{{ $role->title ?: $role->name }}</label>
                    </div>
                  @endforeach
                  </div>
                </div>
              </div>
            </fieldset>

            <input type="hidden" name="user_id" value="{{ $user_id }}" id="input-user_id"/>
          </form>
        </div>
      </div>
    </div>
</div>
@endsection
