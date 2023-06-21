@extends('admin.app')

@section('pageJsCss')
<script src="{{ asset('admin-asset/javascript/jquery/datetimepicker/moment.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('admin-asset/javascript/jquery/datetimepicker/moment-with-locales.min.js') }}" type="text/javascript" ></script>
<script src="{{ asset('admin-asset/javascript/jquery/datetimepicker/daterangepicker.js') }}" type="text/javascript" ></script>
<link	href="{{ asset('admin-asset/javascript/jquery/datetimepicker/daterangepicker.css') }}" rel="stylesheet" type="text/css"/>
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
				<button type="button" data-bs-toggle="tooltip" title="Export" class="btn btn-light" data-bs-original-title="Edit" aria-label="Edit" id="button-export"><i class="fa fa-file-export" style="font-size:20px"></i></button>
				<a href="{{ route('lang.admin.member.members.form') }}" data-bs-toggle="tooltip" title="Add New" class="btn btn-primary"><i class="fas fa-plus"></i></a>
				<?php /*<button type="submit" form="form-member" formaction="{{ route('lang.admin.member.members.massdelete') }}" data-bs-toggle="tooltip" title="Delete" onclick="return confirm('Are you sure?');" class="btn btn-danger"><i class="fas fa-trash-alt"></i></button> */ ?>
			</div>
			<h1>{{ $lang->heading_title }}</h1>
			<ol class="breadcrumb">
				@foreach($breadcumbs as $breadcumb)
					@if(!empty($breadcumb->href))
					<li class="breadcrumb-item"><a href="{{ $breadcumb->href }}">{{ $breadcumb->text }}</a></li>
					@else
					<li class="breadcrumb-item"><a href="#" οnclick="return false">{{ $breadcumb->text }}</a></li>
					@endif
				@endforeach
			</ol>
		</div>
	</div>

	
	<div class="container-fluid">
		<div class="card">
			<div class="card-header"><i class="fa-solid fa-list"></i> {{ $lang->text_list }}</div>
			<div id="store" class="card-body">{!! $list !!}</div>
		</div>
	</div>
</div>
@endsection

@section('buttom')
<script type="text/javascript"><!--
$('#store').on('click', 'thead a, .pagination a', function (e) {
    e.preventDefault();

    $('#store').load(this.href);
});
//--></script>
@endsection