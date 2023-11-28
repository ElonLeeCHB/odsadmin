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
        <button type="button" id="btn-export_daily_list" data-bs-toggle="tooltip" data-loading-text="Loading..." title="下載備料表" class="btn btn-info" aria-label="下載備料表"><i class="fas fa-file-export"></i></button>
        <button type="button" id="btn-export_matrix_list" data-bs-toggle="tooltip" data-loading-text="Loading..." title="下載備料表(距陣式)" class="btn btn-info" aria-label="下載備料表(距陣式)"><i class="fas fa-file-export"></i></button>
        <a href="{{ $add_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_add }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i></a>
        <button type="button" data-bs-toggle="tooltip" title="{{ $lang->button_filter }}" onclick="$('#filter-form').toggleClass('d-none');" class="btn btn-light d-md-none d-lg-none"><i class="fa-solid fa-filter"></i></button>

      </div>
      <h1>{{ $lang->heading_title }}</h1>
      @include('admin.common.breadcumb')
    </div>
  </div>
  <div class="container-fluid">
    <div class="row">
    <div id="filter-form" class="col-lg-3 col-md-12 order-lg-last d-none d-lg-block mb-3">
        <form id="filter-form_content">
          <div class="card">
            <div class="card-header"><i class="fa-solid fa-filter"></i> {{ $lang->text_filter }}</div>
            <div class="card-body">

              <div class="mb-3">
                  <label data-bs-toggle="tooltip" title="例如：2023-02-20 或不加橫線 20230220 或範圍 20230301-20230331 或大於某日 >20230101 或小於某日 <20230101" style="font-weight: bolder;" >{{ $lang->column_required_date }} <i class="fa fa-question-circle" aria-hidden="true"></i></label>
                  <input type="text" id="input-filter_required_date" name="filter_required_date" value="" placeholder="例如 2023-02-20" class="form-control"/>
              </div>
              
              <div class="mb-3">
                <label class="form-label">未來七天</label>
                <select name="equal_within7days" id="input-equal_within7days" class="form-select">
                  <option value="*" > -- </option>
                  <option value="1" selected>是</option>
                  <option value="0" >否</option>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label">{{ $lang->column_product_id }}</label>
                <input type="text" id="input-equal_product_id" name="equal_product_id" value="{{ $equal_product_id ?? '' }}"  class="form-control" autocomplete="off"/>
              </div>

              <div class="mb-3">
                <label class="form-label">{{ $lang->column_product_name }}</label>
                <input type="text" id="input-filter_product_name" name="filter_product_name" value="{{ $filter_product_name ?? '' }}"  class="form-control" autocomplete="off"/>
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
          <div id="ingredient" class="card-body">{!! $list !!}</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="modal-export-loading" class="modal fade">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-file-excel"></i> Export</h5> <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="loadingdiv" id="loading" style="display: block;">
          <img src="{{ asset('image/ajax-loader.gif') }}" width="50"/>     
        </div>
        
      </div>
    </div>
  </div>
</div>
@endsection

@section('buttom')
<script type="text/javascript">
$('#ingredient').on('click', 'thead a, .pagination a', function(e) {
	e.preventDefault();

	$('#ingredient').load(this.href);
});

$('#button-filter').on('click', function() {
	url = '?';

  var filter_required_date = $('#input-filter_required_date').val();
  if (filter_required_date) {
    url += '&filter_required_date=' + encodeURIComponent(filter_required_date);
  }

  var equal_within7days = $('#input-equal_within7days').val();
  if (equal_within7days) {
    url += '&equal_within7days=' + encodeURIComponent(equal_within7days);
  }
  
  var equal_product_id = $('#input-equal_product_id').val();
  if (equal_product_id) {
    url += '&equal_product_id=' + encodeURIComponent(equal_product_id);
  }

	var filter_product_name = $('#input-filter_product_name').val();
	if (filter_product_name) {
		url += '&filter_product_name=' + encodeURIComponent(filter_product_name);
	}

	var equal_days_before = $('#input-equal_days_before').val();
	if (equal_days_before) {
		url += '&equal_days_before=' + encodeURIComponent(equal_days_before);
	}
  
	list_url = "{{ $list_url }}" + url;

	$('#ingredient').load(list_url);

  add_url = $("#button-add").attr("href") + url
  $("#button-add").attr("href", add_url);

});

//下載備料表
$('#btn-export_daily_list').on('click', function () {
  $('#modal-export-loading').modal('show');
  var dataString = $('#filter-form_content').serialize();

  $.ajax({
      type: "POST",
      url: "{{ $export_daily_list_url }}",
      data: dataString,
      cache: false,
      xhrFields:{
          responseType: 'blob'
      },
      beforeSend: function () {
        console.log('beforeSend');
       // $('#btn-export_daily_list').attr("disabled", true);
      },
      success: function(data)
      {
        console.log('success');
        let link = document.createElement('a');
        link.href = window.URL.createObjectURL(data);
        let now_string = moment().format('YYYY-MM-DD_hh-mm-ss');
        link.download = '備料表_'+now_string+'.xlsx';
        link.click();
      },
      complete: function () {
        console.log('complete');
        $('#modal-export-loading').modal('hide');
        $('#btn-export_daily_list').attr("disabled", false);
      },
      fail: function(data) {
        console.log('fail');
        alert('Not downloaded');
      }
  });
});

//下載備料表(距陣式)
$('#btn-export_matrix_list').on('click', function () {
  $('#modal-export-loading').modal('show');
  var dataString = $('#filter-form_content').serialize();

  $.ajax({
      type: "POST",
      url: "{{ $export_matrix_list_url }}",
      data: dataString,
      cache: false,
      xhrFields:{
          responseType: 'blob'
      },
      beforeSend: function () {
        console.log('beforeSend');
       // $('#btn-export_matrix_list').attr("disabled", true);
      },
      success: function(data)
      {
        console.log('success');
        let link = document.createElement('a');
        link.href = window.URL.createObjectURL(data);
        let now_string = moment().format('YYYY-MM-DD_hh-mm-ss');
        link.download = '備料表多日距陣_'+now_string+'.xlsx';
        link.click();
      },
      complete: function () {
        console.log('complete');
        $('#modal-export-loading').modal('hide');
        $('#btn-export_matrix_list').attr("disabled", false);
      },
      fail: function(data) {
        console.log('fail');
        alert('Not downloaded');
      }
  });
});

</script>
@endsection