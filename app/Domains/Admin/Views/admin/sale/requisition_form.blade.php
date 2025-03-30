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

        @if(!empty($print_form_url))
        <a data-href="{{ $print_form_url }}" id="href-printForm"  target="_blank" data-bs-toggle="tooltip" title="列印" class="btn btn-info"><i class="fa-solid fa-print"></i></a>
        @endif
        <a href="{{ $back_url }}" data-bs-toggle="tooltip" title="Back" class="btn btn-light"><i class="fas fa-reply"></i></a>

      </div>
      <h1>{{ $lang->heading_title }}</h1>
      @include('admin.common.breadcumb')
    </div>
  </div>
  <div class="container-fluid">
    <div class="card">
      <div class="card-body">
        <ul class="nav nav-tabs">
            <li class="nav-item"><a href="#tab-general" data-bs-toggle="tab" class="nav-link active">{{ $lang->tab_general }}</a></li>
        </ul>
        <div class="tab-content">
          <div id="tab-general" class="tab-pane active">

            <form id="form-requisition" method="post" data-oc-toggle="ajax" data-oc-load="{{ route('lang.admin.sale.requisitions.getForm') }}" data-oc-target="#requiredDataTable">
              @csrf
              @method('POST')
              <div class="tab-content">
                <div id="tab-general" class="tab-pane active">


<div class="row mb-3 text-start">
  <label for="input-required_date" class="col-sm-2 col-form-label">{{ $lang->column_required_date }}</label>
  <div class="col-sm-10">
    <div class="d-flex gap-3"> 
      <!-- 第一組：需求日期 -->
      <div class="input-group">
        <input type="text" id="input-required_date" name="required_date" value="{{ $required_date ?? \Carbon\Carbon::today()->toDateString() }}" placeholder="{{ $lang->column_required_date }}" class="form-control date"/>
        <div class="input-group-text"><i class="fa-regular fa-calendar"></i></div>
        <button type="button" id="btn-redirectToRequiredDate" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" title="查詢">查詢</button>
      </div>

      <!-- 第二組：需求期間 -->
      <div class="input-group">
        <input type="text" id="input-required_period_start" name="required_period_start" value="" placeholder="需求期間起始" class="form-control date"/>
        <input type="text" id="input-required_period_end" name="required_period_end" value="" placeholder="需求期間結束" class="form-control date"/>
        <div class="input-group-text"><i class="fa-regular fa-calendar"></i></div>
        <button type="button" id="btn-export_matrix_list" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" title="下載">下載</button>
      </div>
    </div>
    <div id="error-required_date" class="invalid-feedback"></div>
  </div>
</div>


                  {{-- 備料表格--}}
                  <div id="requiredDataTable">{!! $requiredDataTable ?? '' !!}</div>

                  {{-- 轉圈圈--}}
                  <div id="loadingSpinner" style="display:none;"><div class="spinner"></div></div>

                </div>
              </div>
            </form>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- 獨立 div --}}
<div id="modal-loading" class="modal fade" style="background-color: rgba(0, 0, 0, 0.5);">
  <div class="modal-dialog">
    <div class="modal-content" style="background: transparent; border: none;">
      <div class="modal-body" style="display: flex; justify-content: center; align-items: center; height: 100px;">
        <div class="loadingdiv" id="loading">
          <img src="{{ asset('image/ajax-loader.gif') }}" width="50"/>
        </div>
      </div>
    </div>
  </div>
</div>

@section('loading')
  @include('admin.partial.modal-loading')
@endsection

<style>
/* 轉圈圈 */
.spinner {
    border: 4px solid #f3f3f3; /* 輕灰色 */
    border-top: 4px solid #3498db; /* 藍色 */
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 2s linear infinite;
}
#loadingSpinner {
    position: fixed; /* 讓 spinner 固定在頁面上 */
    top: 50%; /* 垂直居中 */
    left: 50%; /* 水平居中 */
    transform: translate(-50%, -50%); /* 精確居中對齊 */
    z-index: 9999; /* 確保 spinner 在最上層 */
    display: none; /* 預設隱藏 */
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
@endsection

@section('buttom')
<script type="text/javascript">
//下載備料表(距陣式)
$('#btn-export_matrix_list').on('click', function () {
  $('#modal-loading').modal('show');
    var start_date = $('#input-required_period_start').val();
    var end_date = $('#input-required_period_end').val();

  $.ajax({
    type: "POST",
    url: "{{ $export_matrix_list_url }}?start_date=" + start_date + '&end_date=' + end_date,
    cache: false,
    xhrFields:{
        responseType: 'blob'
    },
    beforeSend: function () {
      $('#btn-export_matrix_list').attr("disabled", true);
    },
    success: function(data)
    {
      let link = document.createElement('a');
      link.href = window.URL.createObjectURL(data);
      let now_string = moment().format('YYYY-MM-DD_hh-mm-ss');
      link.download = '備料表_'+now_string+'.xlsx';
      link.click();
    },
    complete: function () {
      $('#modal-loading').modal('hide');
      $('#btn-export_matrix_list').attr("disabled", false);
    },
    error: function (xhr, status, error) {
      $('#alert').prepend('<div class="alert alert-danger alert-dismissible"><i class="fa-solid fa-circle-exclamation"></i> 無法產生資料。可能日期錯誤。 <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
    },
    fail: function(data) {
      alert('Not downloaded');
    }
  });
});

function loadData(forceUpdate = false) {
    var required_date = $('#input-required_date').val();
    var url = '{{ route('lang.admin.sale.requisitions.getForm') }}?required_date=' + required_date;

    if (forceUpdate) {
        url += '&force_update=1';
    }
    
    window.history.pushState({}, null, '{{ route('lang.admin.sale.requisitions.form') }}?required_date=' + required_date);

    $('#requiredDataTable').empty();
    $('#loadingSpinner').show();

    $('#btn-redirectToRequiredDate, #btn-redirectToRequiredDateUpdate').prop('disabled', true);

    // $('#requiredDataTable').load(url, function() {
    //     $('#loadingSpinner').hide();
    //     $('#btn-redirectToRequiredDate, #btn-redirectToRequiredDateUpdate').prop('disabled', false);
    // });
    $.ajax({
      url: url,
      type: 'GET',
      success: function(data) {
          $('#requiredDataTable').html(data);
          $('#loadingSpinner').hide();
      },
      complete: function () {
        $('#btn-redirectToRequiredDate, #btn-redirectToRequiredDateUpdate').prop('disabled', false);
      },
      error: function(xhr, status, error) {
        var errorMessage = '';
        try {
            var response = JSON.parse(xhr.responseText);
            errorMessage = response.error || '無法產生資料';
        } catch (e) {
            errorMessage = 'Error parsing error response';
        }

        $('#alert').prepend('<div class="alert alert-danger alert-dismissible"><i class="fa-solid fa-circle-exclamation"></i> ' + errorMessage + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');

        $('#loadingSpinner').hide();
      }
    });
}

$('#btn-redirectToRequiredDate').on('click', function() {
    loadData();
});

$(document).ready(function() {
    $('[data-bs-toggle="tooltip"]').tooltip();
});
</script>

<style>
.tooltip .tooltip-inner {
  max-width: 500px; /* 調整 Tooltip 最大寬度 */
  text-align: left; /* 讓文字靠左 */
  white-space: pre-line; /* 讓 <br> 換行 */
}
</style>

@endsection
