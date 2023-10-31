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
        <button type="button" data-bs-toggle="tooltip" title="{{ $lang->button_filter }}" onclick="$('#filter-unit').toggleClass('d-none');" class="btn btn-light d-md-none d-lg-none"><i class="fa-solid fa-filter"></i></button>
        <a href="{{ $add_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_add }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i></a>
        <button type="submit" form="form-unit" formaction="{{ $delete_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_delete }}" onclick="return confirm('{{ $lang->text_confirm }}');" class="btn btn-danger"><i class="fa-regular fa-trash-can"></i></button>
      </div>
      <h1>{{ $lang->heading_title }}</h1>
      @include('admin.common.breadcumb')
    </div>
  </div>
  <div class="container-fluid">
    <div class="row">
      <div id="filter-unit" class="col-lg-3 col-md-12 order-lg-last d-none d-lg-block mb-3">
        <form>
          <div class="card">
            <div class="card-header"><i class="fa-solid fa-filter"></i> {{ $lang->text_filter }}</div>
            <div class="card-body">

              <div class="mb-3">
                <label class="form-label">{{ $lang->column_task_date }}</label>
                <input type="text" id="input-filter_task_date" name="filter_task_date" value="{{ $filter_code ?? '' }}"  class="form-control" autocomplete="off"/>
              </div>

              <div class="mb-3">
                <label class="form-label">{{ $lang->column_product_name }}</label>
                <input type="text" id="input-filter_product_name" name="filter_product_name" value="{{ $filter_product_name ?? '' }}"  data-oc-target="autocomplete-filter_product_name" class="form-control" autocomplete="off"/>
              </div>

              <div class="mb-3">
                <label class="form-label">{{ $lang->column_status_code }}</label>
                <select name="equal_status_code" id="input-equal_status_code" class="form-select">
                  <option value="*"> -- </option>
                  <option value="Y" selected>{{ $lang->text_status_confirmed }}</option>
                  <option value="N">{{ $lang->text_status_unconfirmed }}</option>
                  <option value="V">{{ $lang->text_status_voided }}</option>
                </select>
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
          <div id="unit" class="card-body">{!! $list !!}</div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('buttom')
<script type="text/javascript"><!--
$('#counting').on('click', 'thead a, .pagination a', function(e) {
	e.preventDefault();

	$('#counting').load(this.href);
});

$('#button-filter').on('click', function() {
	url = '';

  var filter_code = $('#input-code').val();

  if (filter_code) {
    url += '&filter_code=' + encodeURIComponent(filter_code);
  }

	var filter_name = $('#input-name').val();

	if (filter_name) {
		url += '&filter_name=' + encodeURIComponent(filter_name);
	}

  var equal_status_code = $('#input-equal_status_code').val();

  if (equal_status_code) {
    url += '&equal_status_code=' + encodeURIComponent(equal_status_code);
  }

	url = "{{ $list_url }}?" + url;

	$('#counting').load(url);
});
//--></script>
@endsection