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
        <button type="button" id="btn-export_list" data-bs-toggle="tooltip" data-loading-text="Loading..." title="下載" class="btn btn-info" aria-label="下載"><i class="fas fa-file-export"></i></button>
        <button type="button" data-bs-toggle="tooltip" title="{{ $lang->button_filter }}" onclick="$('#filter-unit').toggleClass('d-none');" class="btn btn-light d-md-none d-lg-none"><i class="fa-solid fa-filter"></i></button>
        <button type="button" id="btn-analize" data-bs-toggle="tooltip" data-loading-text="Loading..." title="(開發中)分析料件需求" class="btn btn-info" aria-label="分析料件需求"><i class="fas fa-database"></i></button>
      </div>
      <h1>{{ $lang->heading_title }}</h1>
      @include('admin.common.breadcumb')
    </div>
  </div>
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-3 col-md-12 order-lg-last d-none d-lg-block mb-3">
        <form id="filter-form">
          <div class="card">
            <div class="card-header"><i class="fa-solid fa-filter"></i> {{ $lang->text_filter }}</div>
            <div class="card-body">

            <div class="mb-3">
                <label data-bs-toggle="tooltip" title="例如：2023-02-20 或不加橫線 20230220 或範圍 20230301-20230331 或大於某日 >20230101 或小於某日 <20230101" style="font-weight: bolder;" >{{ $lang->column_required_date }} <i class="fa fa-question-circle" aria-hidden="true"></i></label>
                <input type="text" id="input-filter_required_date" name="filter_required_date" value="" placeholder="例如 2023-02-20" class="form-control"/>
            </div>

              <div class="mb-3">
                <label class="form-label">{{ $lang->column_product_name }}</label>
                <input type="text" id="input-filter_product_name" name="filter_product_name" value="{{ $filter_product_name ?? '' }}"  class="form-control"/>
              </div>

              <div class="mb-3">
                <label class="form-label">{{ $lang->column_product_supplier_own_product_code }}</label>
                <input type="text" id="input-filter_supplier_product_code" name="filter_supplier_product_code" value="{{ $filter_supplier_product_code ?? '' }}"  class="form-control" />
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
          <div id="requirement" class="card-body">{!! $list !!}</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="modal-loading" class="modal fade">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-file-excel"></i> 分析料件需求</h5> <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="loadingdiv" id="loading" style="display: block;">
          <img src="{{ asset('image/ajax-loader.gif') }}" width="50"/>     
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
$('#requirement').on('click', 'thead a, .pagination a', function(e) {
  e.preventDefault();

  $('#requirement').load(this.href);
});

$('#button-filter').on('click', function() {
  url = '';

  var filter_required_date = $('#input-filter_required_date').val();

  if (filter_required_date) {
    url += '&filter_required_date=' + encodeURIComponent(filter_required_date);
  }

  var filter_product_name = $('#input-filter_product_name').val();

  if (filter_product_name) {
    url += '&filter_product_name=' + encodeURIComponent(filter_product_name);
  }

  var filter_supplier_product_code = $('#input-filter_supplier_product_code').val();

  if (filter_supplier_product_code) {
    url += '&filter_supplier_product_code=' + encodeURIComponent(filter_supplier_product_code);
  }

  url = "{{ $list_url }}?" + url;

  $('#requirement').load(url);
});

//分析料件需求
// $('#btn-analize').on('click', function () {
//   $('#modal-loading').modal('show');
//   var dataString = $('#filter-form').serialize();

//   var filter_required_date = $('#input-filter_required_date').val();

//   //console.log('filter_required_date='+filter_required_date+', url='+url)

//   $.ajax({
//     type: "POST",
//     url:  "{{ $anylize_url }}",
//     data: $('#filter-form').serialize(),
//     success:function(response){

//       if(response.error){
//         console.log('1')
//       }
//     }
//   });
// });

//匯出
$('#btn-export_list').on('click', function () {
  $('#modal-export-loading').modal('show');
  var dataString = $('#filter-form').serialize();

  $.ajax({
      type: "POST",
      url: "{{ $export_list }}",
      data: dataString,
      cache: false,
      xhrFields:{
          responseType: 'blob'
      },
      beforeSend: function () {
        console.log('beforeSend');
       // $('#btn-export_list').attr("disabled", true);
      },
      success: function(data)
      {
        console.log('success');
        let link = document.createElement('a');
        link.href = window.URL.createObjectURL(data);
        let now_string = moment().format('YYYY-MM-DD_hh-mm-ss');
        link.download = '料件需求表_'+now_string+'.xlsx';
        link.click();
      },
      complete: function () {
        console.log('complete');
        $('#modal-export-loading').modal('hide');
        $('#btn-export_list').attr("disabled", false);
      },
      fail: function(data) {
        console.log('fail');
        alert('Not downloaded');
      }
  });
});
</script>
</script>
@endsection