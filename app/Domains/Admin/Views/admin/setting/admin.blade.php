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
          <label class="form-label">{{ $lang->column_name }}</label>
          <input type="text" name="filter_name" value="" placeholder="{{ $lang->column_name }}" id="input-name" list="list-name" class="form-control"/>
          <datalist id="list-name"></datalist>
        </div>

        <div class="mb-3">
          <label class="form-label">{{ $lang->column_phone }}</label>
          <input type="text" name="filter_phone" value="" placeholder="{{ $lang->placeholder_phone }}" id="input-phone" list="list-phone" class="form-control"/>
          <datalist id="list-phone"></datalist>
        </div>

        <div class="mb-3">
          <label class="form-label">{{ $lang->column_email }}</label>
          <input type="text" name="filter_email" value="" placeholder="{{ $lang->column_email }}" id="input-email" list="list-email" class="form-control"/>
          <datalist id="list-email"></datalist>
        </div>

        <div class="text-end">
          <button type="reset" id="button-clear" class="btn btn-light"><i class="fa fa-refresh" aria-hidden="true"></i> {{ $lang->button_reset }}</button>
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

  var filter_name = $('#input-name').val();

  if (filter_name) {
    url += '&filter_name=' + encodeURIComponent(filter_name);
  }

  var filter_mobile = $('#input-phone').val();

  if (filter_mobile) {
    url += '&filter_phone=' + encodeURIComponent(filter_mobile);
  }

  var filter_email = $('#input-email').val();

  if (filter_email) {
    url += '&filter_email=' + encodeURIComponent(filter_email);
  }

  var filter_location_id = $('#input-location_id').val();

  if (filter_company) {
    url += '&filter_location_id=' + encodeURIComponent(filter_location_id);
  }

  url = "{{ $list_url }}?" + url;

  $('#user').load(url);
});

//--></script>
@endsection