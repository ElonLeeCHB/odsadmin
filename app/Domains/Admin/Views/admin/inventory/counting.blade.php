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

        <button type="button" id="btn-inventory_product_list" data-bs-toggle="tooltip" data-loading-text="Loading..." title="下載盤點表" class="btn btn-info" aria-label="下載盤點表"><i class="fas fa-file-export"></i></button>
        <button type="button" data-bs-toggle="tooltip" title="{{ $lang->button_filter }}" onclick="$('#filter-list').toggleClass('d-none');" class="btn btn-light d-md-none d-lg-none"><i class="fa-solid fa-filter"></i></button>
        <a id="button-add" href="{{ $add_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_add }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i></a>
      </div>
      <h1>{{ $lang->heading_title }}</h1>
      @include('admin.common.breadcumb')
    </div>
  </div>
  <div class="container-fluid">
    <div class="row">

      <div id="filter-list" class="col-lg-3 col-md-12 order-lg-last d-none d-lg-block mb-3">
        <form>
          <div class="card">
            <div class="card-header"><i class="fa-solid fa-filter"></i> {{ $lang->text_filter }}</div>
            <div class="card-body">
            

              <div class="mb-3">
                <label class="form-label">{{ $lang->column_form_date }}</label>
                <input type="text" id="input-filter_form_date" name="filter_form_date" value="{{ $filter_form_date ?? '' }}"  class="form-control" />
              </div>

              <div class="mb-3">
                <label class="form-label">{{ $lang->column_status }}</label>
                <select name="equal_status_code" id="input-equal_status_code" class="form-select">
                  <option value="*">{{ $lang->text_select }}</option>
                  @foreach($statuses as $status)
                  <option value="{{ $status->code }}">{{ $status->name }}</option>
                  @endforeach
                  <option value="WithoutV" selected>{{ $lang->text_status_without_voided }}</option>
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

          <div id="counting" class="card-body">{!! $list !!}</div>

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
$('#counting').on('click', 'thead a, .pagination a', function(e) {
	e.preventDefault();

	$('#counting').load(this.href);
});

$('#button-filter').on('click', function() {

  url = '';


  var filter_form_date = $('#input-filter_form_date').val();


  if (filter_form_date) {
    url += '&filter_form_date=' + encodeURIComponent(filter_form_date);
  }


  var equal_status_code = $('#input-equal_status_code').val();


  if (equal_status_code && equal_status_code != '*') {
    url += '&equal_status_code=' + encodeURIComponent(equal_status_code);
  }


	list_url = "{{ $list_url }}?" + url;

	$('#counting').load(list_url);

  add_url = $("#button-add").attr("href") + url
  $("#button-add").attr("href", add_url);
});

//匯出盤點表
$('#btn-inventory_product_list').on('click', function () {
  $('#modal-export-loading').modal('show');
  var dataString = $('#filter-form').serialize();

  $.ajax({
      type: "POST",
      url: "{{ $export_counting_product_list }}",
      data: dataString,
      cache: false,
      xhrFields:{
          responseType: 'blob'
      },
      beforeSend: function () {
        console.log('beforeSend');
       // $('#btn-inventory_product_list').attr("disabled", true);
      },
      success: function(data)
      {
        console.log('success');
        let link = document.createElement('a');
        link.href = window.URL.createObjectURL(data);
        let now_string = moment().format('YYYY-MM-DD_hh-mm-ss');
        link.download = '盤點表_'+now_string+'.xlsx';
        link.click();
      },
      complete: function () {
        console.log('complete');
        $('#modal-export-loading').modal('hide');
        $('#btn-inventory_product_list').attr("disabled", false);
      },
      fail: function(data) {
        console.log('fail');
        alert('Not downloaded');
      }
  });
});
</script>
@endsection