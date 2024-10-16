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
				<button type="button" data-bs-toggle="tooltip" title="Filter" onclick="$('#filter-member').toggleClass('d-none');" class="btn btn-light d-md-none d-lg-none"><i class="fas fa-filter" style="font-size:18px"></i></button>
				<a href="{{ $add_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_add }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i></a>
			</div>
			<h1>{{ $lang->heading_title }}</h1>
			@include('admin.common.breadcumb')
		</div>
	</div>
  <div class="container-fluid">
	<div class="row">
	  <div id="filter-member" class="col-lg-3 col-md-12 order-lg-last d-none d-lg-block mb-3">
		<form id="filter-form">
		<div class="card">
		  <div class="card-header"><i class="fas fa-filter"></i> Filter</div>
		  <div class="card-body">

        <div class="mb-3">
          <label class="form-label">{{ $lang->entry_name }}</label>
          <input type="text" name="filter_name" value="" placeholder="{{ $lang->entry_name }}" id="input-name" list="list-name" class="form-control"/>
          <datalist id="list-name"></datalist>
        </div>
        <div class="mb-3">
          <label class="form-label">{{ $lang->column_company }}</label>
          <input type="text" name="filter_company" value="" placeholder="{{ $lang->plcaeholder_company }}" id="input-company" list="list-company" class="form-control"/>
          <datalist id="list-company"></datalist>
        </div>
        <div class="mb-3">
          <label class="form-label">{{ $lang->entry_phone }}</label>
          <input type="text" name="filter_phone" value="" placeholder="{{ $lang->placeholder_phone }}" id="input-phone" list="list-phone" class="form-control"/>
          <datalist id="list-phone"></datalist>
        </div>
        <div class="mb-3">
          <label class="form-label">{{ $lang->entry_email }}</label>
          <input type="text" name="filter_email" value="" placeholder="{{ $lang->entry_email }}" id="input-email" list="list-email" class="form-control"/>
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
		  <div id="member" class="card-body">{!! $list !!}</div>
		</div>
	  </div>
	</div>
  </div>
</div>
@endsection

@section('buttom')
<script type="text/javascript"><!--

$('#member').on('click', 'thead a, .pagination a', function(e) {
	e.preventDefault();

	$('#member').load(this.href);
});

$('#button-filter').on('click', function() {
	url = '';

	var filter_name = $('#input-name').val();

	if (filter_name) {
		url += '&filter_name=' + encodeURIComponent(filter_name);
	}

	var filter_company = $('#input-company').val();

	if (filter_company) {
		url += '&filter_company=' + encodeURIComponent(filter_company);
	}

	var filter_mobile = $('#input-mobile').val();

	if (filter_mobile) {
		url += '&filter_mobile=' + encodeURIComponent(filter_mobile);
	}

	var filter_mobile = $('#input-telephone').val();

	if (filter_mobile) {
		url += '&filter_telephone=' + encodeURIComponent(filter_mobile);
	}

	var filter_mobile = $('#input-phone').val();

	if (filter_mobile) {
		url += '&filter_phone=' + encodeURIComponent(filter_mobile);
	}

	var filter_email = $('#input-email').val();

	if (filter_email) {
		url += '&filter_email=' + encodeURIComponent(filter_email);
	}

	/*
	var filter_status = $('#input-status').val();

	if (filter_status) {
		url += '&filter_status=' + filter_status;
	}

	var filter_ip = $('#input-ip').val();

	if (filter_ip) {
		url += '&filter_ip=' + encodeURIComponent(filter_ip);
	}

	var filter_date_added = $('#input-date-added').val();

	if (filter_date_added) {
		url += '&filter_date_added=' + encodeURIComponent(filter_date_added);
	}
	*/

	url = "{{ $list_url }}?" + url;

	$('#member').load(url);
});

/*
$('#input-name').autocomplete({
	'source': function(request, response) {
		$.ajax({
			url: "{{ route('lang.admin.member.members.autocomplete') }}?filter_last_name=" + encodeURIComponent(request),
			dataType: 'json',
			success: function(json) {
				response($.map(json, function(item) {
					return {
						label: item['last_name'],
						value: item['member_id']
					}
				}));
			}
		});
	},
	'select': function(item) {}
});

$('#input-phone').autocomplete({
	'source': function(request, response) {
		$.ajax({
			//url: 'index.php?route=customer/customer|autocomplete&user_token=5bb02794973e438e69f86e04c7730815&filter_name=' + encodeURIComponent(request),
			url: "{{ route('lang.admin.member.members.autocomplete') }}?filter_mobile=" + encodeURIComponent(request),
			dataType: 'json',
			success: function(json) {
				response($.map(json, function(item) {
					return {
						label: item['last_name'],
						value: item['member_id']
					}
				}));
			}
		});
	},
	'select': function(item) {}
});
*/

//--></script>
@endsection