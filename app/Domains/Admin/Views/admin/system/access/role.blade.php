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
        <button type="button" data-bs-toggle="tooltip" title="篩選" onclick="$('#filter-role').toggleClass('d-none');" class="btn btn-light d-md-none d-lg-none"><i class="fa-solid fa-filter"></i></button>
        <a id="button-add" href="{{ $add_url }}" data-bs-toggle="tooltip" title="新增" class="btn btn-primary"><i class="fa-solid fa-plus"></i></a>
        <button type="submit" form="form-role" formaction="{{ $delete_url }}" data-bs-toggle="tooltip" title="刪除" onclick="return confirm('確定要刪除選取的角色嗎？');" class="btn btn-danger"><i class="fa-regular fa-trash-can"></i></button>
      </div>
      <h1>角色管理</h1>
      @include('admin.common.breadcumb')
    </div>
  </div>
  <div class="container-fluid">
    <div class="row">
      <div id="filter-role" class="col-lg-3 col-md-12 order-lg-last d-none d-lg-block mb-3">
        <form>
          <div class="card">
            <div class="card-header"><i class="fa-solid fa-filter"></i> 篩選</div>
            <div class="card-body">

              <div class="mb-3">
                <label class="form-label">角色代碼</label>
                <input type="text" id="input-name" name="filter_name" value="{{ $filter_name ?? '' }}" class="form-control" autocomplete="off"/>
              </div>

              <div class="mb-3">
                <label class="form-label">關鍵字</label>
                <input type="text" id="input-search" name="search" value="{{ $search ?? '' }}" class="form-control" autocomplete="off"/>
              </div>

              <div class="mb-3">
                <label class="form-label">狀態</label>
                <select id="input-is_active" name="equal_is_active" class="form-select">
                  <option value="">全部</option>
                  <option value="1" @if(($equal_is_active ?? '') === '1') selected @endif>啟用</option>
                  <option value="0" @if(($equal_is_active ?? '') === '0') selected @endif>停用</option>
                </select>
              </div>

              <div class="text-end">
                <button type="reset" id="button-clear" class="btn btn-light"><i class="fa fa-refresh" aria-hidden="true"></i> 重置</button>
                <button type="button" id="button-filter" class="btn btn-light"><i class="fa-solid fa-filter"></i> 篩選</button>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="col-lg-9 col-md-12">
        <div class="card">
          <div class="card-header"><i class="fa-solid fa-list"></i> 角色列表</div>
          <div id="role" class="card-body">{!! $list !!}</div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('buttom')
<script type="text/javascript"><!--
$('#role').on('click', 'thead a, .pagination a', function(e) {
  e.preventDefault();

  $('#role').load(this.href);
});

$('#button-filter').on('click', function() {
  var params = [];

  var filter_name = $('#input-name').val();
  var search = $('#input-search').val();
  var equal_is_active = $('#input-is_active').val();

  if (filter_name) {
    params.push('filter_name=' + encodeURIComponent(filter_name));
  }
  if (search) {
    params.push('search=' + encodeURIComponent(search));
  }
  if (equal_is_active !== '') {
    params.push('equal_is_active=' + encodeURIComponent(equal_is_active));
  }

  var url = params.length ? '?' + params.join('&') : '';
  var list_url = "{{ $list_url }}" + url;

  $('#role').load(list_url);

  var add_url = "{{ $add_url }}" + url;
  $("#button-add").attr("href", add_url);
});
//--></script>
@endsection
