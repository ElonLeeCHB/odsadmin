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
        <button type="button" data-bs-toggle="tooltip" title="Filter" onclick="$('#filter-user').toggleClass('d-none');" class="btn btn-light d-md-none d-lg-none"><i class="fas fa-filter" style="font-size:18px"></i></button>
        <a href="{{ $add_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_add }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i></a>
        <button type="submit" form="form-user" formaction="{{ $delete_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_delete }}" onclick="return confirm('只會移除管理者身份，不會完全刪除資料。是否移除？');" class="btn btn-danger"><i class="fa-regular fa-trash-can"></i></button>
      </div>
      <h1>{{ $lang->heading_title }}</h1>
      @include('admin.common.breadcumb')
    </div>
  </div>
  <div class="container-fluid">
  <div class="row">
    <div id="filter-user" class="col-lg-3 col-md-12 order-lg-last d-none d-lg-block mb-3">
    <form id="filter-form">
    <div class="card">
      <div class="card-header"><i class="fas fa-filter"></i> Filter</div>
      <div class="card-body">

        <div class="mb-3">
          <label class="form-label">{{ $lang->column_keyword }}</label>
          <input type="text" id="input-filter_keyword"  name="filter_keyword" value="{{ $filter_keyword ?? '' }}" placeholder="{{ $lang->column_keyword }}" list="list-keyword" class="form-control"/>
          <datalist id="list-keyword"></datalist>
        </div>

        <div class="mb-3">
          <label class="form-label">{{ $lang->column_phone }}</label>
          <input type="text" name="filter_phone" value="{{ $filter_phone ?? '' }}" placeholder="{{ $lang->placeholder_phone }}" id="input-phone" list="list-phone" class="form-control"/>
          <datalist id="list-phone"></datalist>
        </div>

        <div class="mb-3">
          <label class="form-label">{{ $lang->column_is_admin }}</label>
          <select name="equal_is_admin" id="input-equal_is_admin" class="form-select">
          <option value="*">{{ $lang->text_select }}</option>
            <option value="1"@if($equal_is_admin==1) selected @endif>{{ $lang->text_yes }}</option>
            <option value="0"@if($equal_is_admin==0) selected @endif>{{ $lang->text_no }}</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">{{ $lang->column_is_active }}</label>
          <select name="equal_is_active" id="input-equal_is_active" class="form-select">
          <option value="*">{{ $lang->text_select }}</option>
            <option value="1" @if($equal_is_active==1) selected @endif>{{ $lang->text_yes }}</option>
            <option value="0" @if($equal_is_active==0) selected @endif>{{ $lang->text_no }}</option>
          </select>
        </div>

        <div class="text-end">
          <button type="reset" id="button-reset" class="btn btn-light"><i class="fa fa-refresh" aria-hidden="true"></i> {{ $lang->button_reset }}</button>
          <button type="button" id="button-filter" class="btn btn-light"><i class="fas fa-filter"></i> {{ $lang->button_filter }}</button>
      </div>

      </div>
    </div>
    </form>
    </div>
    <div class="col-lg-9 col-md-12">
    <div class="card">
      <div class="card-header"><i class="fas fa-list"></i> {{ $lang->text_list }}</div>
      <div id="user" class="card-body">{!! $list !!}</div>
    </div>
    </div>
  </div>
  </div>
</div>
@endsection

@section('buttom')
<script type="text/javascript"><!--

$('#user').on('click', 'thead a, .pagination a', function(e) {
  e.preventDefault();

  $('#user').load(this.href);
});

$('#button-filter').on('click', function() {
  url = '';

  var filter_keyword = $('#input-filter_keyword').val();

  if (filter_keyword) {
    url += '&filter_keyword=' + encodeURIComponent(filter_keyword);
  }

  var filter_phone = $('#input-phone').val();

  if (filter_phone) {
    url += '&filter_phone=' + encodeURIComponent(filter_phone);
  }

  var equal_is_admin = $('#input-equal_is_admin').val();

  if (equal_is_admin) {
    url += '&equal_is_admin=' + encodeURIComponent(equal_is_admin);
  }

  var equal_is_active = $('#input-equal_is_active').val();

  if (equal_is_active) {
    url += '&equal_is_active=' + encodeURIComponent(equal_is_active);
  }
  

  url = "{{ $list_url }}?" + url;

  $('#user').load(url);
});
//--></script>
@endsection