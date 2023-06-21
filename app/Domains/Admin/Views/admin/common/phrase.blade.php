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
				<button type="button" data-bs-toggle="tooltip" title="Filter" onclick="$('#filter-phrase').toggleClass('d-none');" class="btn btn-light d-md-none d-lg-none"><i class="fas fa-filter" style="font-size:18px"></i></button>
				<a href="{{ route('lang.admin.common.phrases.form') }}" data-bs-toggle="tooltip" title="Add New" class="btn btn-primary"><i class="fas fa-plus"></i></a>
			</div>
			<h1>{{ $lang->heading_title }}</h1>
			@include('admin.common.breadcumb')
		</div>
	</div>
  <div class="container-fluid">
	<div class="row">
	  <div id="filter-phrase" class="col-lg-3 col-md-12 order-lg-last d-none d-lg-block mb-3">
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
						<label class="form-label">{{ $lang->column_taxonomy }}</label>
						<select name="filter_taxonomy" id="input-filter_taxonomy" class="form-select">
						<option value=""> -- </option>
						<option value="phrase_order_comment">phrase_order_comment</option>
						<option value="phrase_order_extra_comment">phrase_order_extra_comment</option>
						</select>
					</div>
					<div class="mb-3">
						<label class="form-label">{{ $lang->column_is_active }}</label>
						<select name="filter_is_active" id="input-filter_is_active" class="form-select">
              <option value=""> -- </option>
              <option value="1">{{ $lang->text_yes }}</option>
              <option value="0">{{ $lang->text_no }}</option>
            </select>
					</div>
			<div class="text-end">
			  <button type="button" id="button-filter" class="btn btn-light"><i class="fas fa-filter"></i> Filter</button>
			</div>
		  </div>
		</div>
		</form>
	  </div>
	  <div class="col-lg-9 col-md-12">
		<div class="card">
		  <div class="card-header"><i class="fas fa-list"></i> {{ $lang->text_list }}</div>
		  <div id="phrase" class="card-body">{!! $list !!}</div>
		</div>
	  </div>
	</div>
  </div>
</div>
@endsection

@section('buttom')
<script type="text/javascript"><!--
$('#phrase').on('click', 'thead a, .pagination a', function(e) {
	e.preventDefault();

	$('#phrase').load(this.href);
});

$('#button-filter').on('click', function() {
	url = '';

	var filter_name = $('#input-name').val();

	if (filter_name) {
		url += '&filter_name=' + encodeURIComponent(filter_name);
	}

  var filter_taxonomy = $('#input-filter_taxonomy').val();

  if (filter_taxonomy) {
    url += '&filter_taxonomy=' + encodeURIComponent(filter_taxonomy);
  }

	var filter_is_active = $('#input-filter_is_active').val();

	if (filter_is_active) {
		url += '&filter_is_active=' + encodeURIComponent(filter_is_active);
	}

	url = "{{ route('lang.admin.common.phrases.list') }}?" + url;

	$('#phrase').load(url);
});
//--></script>
@endsection