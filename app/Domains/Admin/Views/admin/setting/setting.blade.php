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
        <button type="button" data-bs-toggle="tooltip" title="{{ $lang->button_filter }}" onclick="$('#filter-setting').toggleClass('d-none');" class="btn btn-light d-md-none d-lg-none"><i class="fa-solid fa-filter"></i></button>
        <a href="{{ $add_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_add }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i></a>
        <button type="submit" form="form-setting" formaction="{{ $delete_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_delete }}" onclick="return confirm('{{ $lang->text_confirm }}');" class="btn btn-danger"><i class="fa-regular fa-trash-can"></i></button>
      </div>
      <h1>{{ $lang->heading_title }}</h1>
      @include('admin.common.breadcumb')
    </div>
  </div>
  <div class="container-fluid">
    <div class="row">
      <div id="filter-setting" class="col-lg-3 col-md-12 order-lg-last d-none d-lg-block mb-3">
        <form>
          <div class="card">
            <div class="card-header"><i class="fa-solid fa-filter"></i> {{ $lang->text_filter }}</div>
            <div class="card-body">
              <div class="mb-3">
                <label class="form-label">{{ $lang->column_group }}</label>
                <input type="text" id="input-group" name="filter_group" value="{{ $filter_group ?? '' }}" data-oc-target="autocomplete-group" class="form-control" autocomplete="off"/>
                <ul id="autocomplete-group" class="dropdown-menu"></ul>
              </div>
              <div class="mb-3">
                <label class="form-label">{{ $lang->column_setting_key }}</label>
                <input type="text" id="input-setting_key" name="filter_setting_key" value="{{ $filter_setting_key ?? '' }}" data-oc-target="autocomplete-setting_key" class="form-control" autocomplete="off"/>
                <ul id="autocomplete-setting_key" class="dropdown-menu"></ul>
              </div>
              <div class="text-end">
                <button type="reset" id="button-clear" class="btn btn-light"><i class="fa fa-refresh" aria-hidden="true"></i> {{ $lang->button_reset }}</button>
                <button type="button" id="button-filter" class="btn btn-light"><i class="fa-solid fa-filter"></i> {{ $lang->button_filter }}</button>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="col-lg-9 col-md-12">
        <div class="card">
          <div class="card-header"><i class="fa-solid fa-list"></i> {{ $lang->text_list }}</div>
          <div id="setting" class="card-body">{!! $list !!}</div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('buttom')
<script type="text/javascript"><!--
$('#setting').on('click', 'thead a, .pagination a', function(e) {
	e.preventDefault();

	$('#setting').load(this.href);
});

$('#button-filter').on('click', function() {
	url = '';

  var filter_group = $('#input-group').val();

  if (filter_group) {
    url += '&filter_group=' + encodeURIComponent(filter_group);
  }

	var filter_name = $('#input-name').val();

	if (filter_name) {
		url += '&filter_name=' + encodeURIComponent(filter_name);
	}

  var filter_setting_key = $('#input-setting_key').val();

  if (filter_setting_key) {
    url += '&filter_setting_key=' + encodeURIComponent(filter_setting_key);
  }

	url = "{{ $list_url }}?" + url;

	$('#setting').load(url);
});
//--></script>
@endsection