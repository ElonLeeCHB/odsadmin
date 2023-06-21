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
				<a href="{{ route('lang.admin.member.organizations.form') }}" data-bs-toggle="tooltip" title="Add New" class="btn btn-primary"><i class="fas fa-plus"></i></a>
				<?php /*<button type="submit" form="form-member" formaction="{{ route('lang.admin.member.organizations.massdelete') }}" data-bs-toggle="tooltip" title="Delete" onclick="return confirm('Are you sure?');" class="btn btn-danger"><i class="fas fa-trash-alt"></i></button> */ ?>
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
						<input type="text" name="filter_name" value="" placeholder="{{ $lang->entry_name_or_short }}" id="input-name" list="list-name" class="form-control"/>
						<datalist id="list-name"></datalist>
					</div>
					<div class="mb-3">
						<label class="form-label">{{ $lang->column_contact }}</label>
						<input type="text" name="filter_contact" value="" placeholder="{{ $lang->placeholder_contact }}" id="input-contact" list="list-contact" class="form-control"/>
						<datalist id="list-contact"></datalist>
					</div>
					<div class="mb-3">
						<label class="form-label">{{ $lang->entry_contact_phone }}</label>
						<input type="text" name="filter_contact_phone" value="" placeholder="{{ $lang->placeholder_phone }}" id="input-contact_phone" list="list-contact_phone" class="form-control"/>
						<datalist id="list-contact_phone"></datalist>
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
		  <div id="member" class="card-body">{!! $list !!}</div>
		</div>
	  </div>
	</div>
  </div>
</div>

<!-- 下載 excel 彈出視窗 -->
<div id="modal-option" class="modal fade">
	<form id="excel-form">
		@csrf
		@method('post')
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title"><i class="fas fa-file-excel"></i> Export</h5> <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
					<div class="mb-3">
						<label for="input-format" class="form-label">Format</label>
						<select name="format" id="input-excel-format" class="form-select">
							<option value="xlsx" selected="selected">xlsx</option>
							<option value="csv">csv</option>
							<?php /*
							<option value="csv">csv</option>
							<option value="pdf">pdf</option>
							*/ ?>
						</select>
					</div>
					<div class="mb-3">
						<label for="input-template" class="form-label">Template</label>
						<select name="template" id="input-template" class="form-select">
							<option value="">{{ $lang->text_none }}</option>
							<option value="BasicTableLaravelExcel">Basic Table with freeze panes, memory maybe not enough.</option>
						</select>
					</div>
					<div class="modal-footer">
						<div class="loadingdiv" id="loading" style="display: none;">
							<img src="{{ asset('media/ajax-loader.gif') }}" width="50"/>     
						</div>
						<button type="button" id="button-export-save" class="btn btn-primary">Save</button>
						<button type="button" id="button-export-cancel" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
					</div>


				</div>
			</div>
		</div>
	</form>
</div>
@endsection

@section('buttom')
<script type="text/javascript"><!--
//Export: Show window
$('#button-export').on('click', function () {
	$('#modal-option').modal('show');
	
});


//Export: Download
$('#button-export-save').on('click', function () {
	//var dataString = $('#excel-form').serialize();
	var dataString = $('form').serialize();
	var ext = $('#input-excel-format').val();

    $.ajax
    ({
        type: "POST",
        url: "{{ route('lang.admin.member.organizations.index') }}",
        data: dataString,
        cache: false,
        xhrFields:{
            responseType: 'blob'
        },
		beforeSend: function () {
			console.log('beforeSend');
            $('#loading').css("display", "");
            $('#button-export-save').attr("disabled", true);
		},
        success: function(data)
        {
			console.log('success');
            var link = document.createElement('a');
            link.href = window.URL.createObjectURL(data);
			link.download = 'members.' + ext;

            link.click();
			$('#modal-option').modal('hide');
        },
		complete: function () {
			console.log('complete');
			$('#loading').css("display", "none");
            $('#button-export-save').attr("disabled", false);
		},
        fail: function(data) {
			console.log('fail');
            alert('Not downloaded');
        }
    });
});


$('#button-export-cancel').on('click', function () {
	$('#loading').css("display", "none");
	$('#button-export-save').attr("disabled", false);
});

$('#member').on('click', 'thead a, .pagination a', function(e) {
	e.preventDefault();

	$('#member').load(this.href);
});

$('#button-filter').on('click', function() {
	url = '';

	var filter_name = $('#input-name').val();

	if (filter_name) {
		url += '&filter_mixed_name=' + encodeURIComponent(filter_name);
	}

	var filter_email = $('#input-contact').val();

	if (filter_email) {
		url += '&filter_contact=' + encodeURIComponent(filter_contact);
	}

	var filter_mobile = $('#input-phone').val();

	if (filter_mobile) {
		url += '&filter_phone=' + encodeURIComponent(filter_phone);
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

	url = "{{ route('lang.admin.member.organizations.list') }}?" + url;

	$('#member').load(url);
});

/* 考量效能，先不使用
$('#input-name').autocomplete({
	'source': function(request, response) {
		$.ajax({
			url: "{{ route('lang.admin.member.organizations.autocomplete') }}?filter_mix_name=" + encodeURIComponent(request),
			dataType: 'json',
			success: function(json) {
				response($.map(json, function(item) {
					return {
						label: item['name'],
						value: item['id']
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