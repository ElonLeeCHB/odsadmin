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
      <h1>{{ $lang->heading_title }}</h1>
      @include('admin.common.breadcumb')
      <div class="float-end">
        <button type="button" id="btn-export01" data-bs-toggle="tooltip" data-loading-text="Loading..." title="下載進貨報表" class="btn btn-info" aria-label="下載進貨報表"><i class="fas fa-file-export"></i></button>
        <button type="button" data-bs-toggle="tooltip" title="篩選" onclick="$('#filter-order').toggleClass('d-none');" class="btn btn-light d-md-none d-lg-none"><i class="fas fa-filter" style="font-size:18px"></i></button>
        <a href="{{ $add_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_add }}" class="btn btn-primary"><i class="fas fa-plus"></i></a>
      </div>
    </div>
  </div>
  <div class="container-fluid">
  <div class="row">
    <div id="filter-product" class="col-lg-3 col-md-12 order-lg-last d-none d-lg-block mb-3">
    <form id="filter-form">
      <div class="card">
        <div class="card-header"><i class="fas fa-filter"></i> {{ $lang->text_filter }}</div>
        <div class="card-body">

          <div class="mb-3">
            <label class="form-label">{{ $lang->column_form_type }}</label>
            <select id="input-equal_form_type_code" name="equal_form_type_code" class="form-select">
              <option value="">--</option>
              @foreach($form_types as $form_type)
              <option value="{{ $form_type->code }}" >{{ $form_type->name }}</option>
              @endforeach
            </select>
          </div>
          
          <div class="mb-3">
            <label class="form-label">{{ $lang->column_supplier_name }}</label>
            <input type="text" id="input-filter_supplier_name" name="filter_supplier_name" value="" class="form-control"/>
          </div>
          
          <div class="mb-3">
            <label class="form-label">{{ $lang->column_product_name }}</label>
            <input type="text" id="input-filter_product_name" name="filter_product_name" value="" class="form-control"/>
          </div>

          <div class="mb-3">
            <label data-bs-toggle="tooltip" title="2023-02-20 或不加橫線 20230220 或範圍 20230301-20230331 或大於某日 >20230101 或小於某日 <20230101" style="font-weight: bolder;" >進貨日期 <i class="fa fa-question-circle" aria-hidden="true"></i></label>
            <input type="text" id="input-filter_receiving_date" name="filter_receiving_date" value="" placeholder="例如 2023-02-20" class="form-control"/>
          </div>
          
          <div class="mb-3">
            <label class="form-label">{{ $lang->column_tax_type }}</label>
            <select id="input-filter_tax_type_code" name="filter_tax_type_code" class="form-select">
              <option value="">--</option>
              @foreach($tax_types as $tax_type)
              <option value="{{ $tax_type->code }}" >{{ $tax_type->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">{{ $lang->column_status }}</label>
            <select id="input-filter_status_code" name="filter_status_code" class="form-select">
              <option value="">--</option>
              @foreach($statuses as $status)
              <option value="{{ $status->code }}" >{{ $status->name }}</option>
              @endforeach
              <option value="withoutV" selected>{{ $lang->text_status_without_voided }}</option>
            </select>
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
      <div id="receiving_order" class="card-body">{!! $list !!}</div>
    </div>
    </div>
  </div>
  </div>
</div>
@endsection

@section('buttom')
<script type="text/javascript">

$('#receiving_order').on('click', 'thead a, .pagination a', function(e) {
  e.preventDefault();

  $('#receiving_order').load(this.href);
});

$('#button-filter').on('click', function() {
  let queryUrl = getFilterUrl();
  
  url = "{{ $list_url }}?" + queryUrl;

  $('#receiving_order').load(url);
});

function getFilterUrl(){
  let url = '';

  var filter_code = $('#input-filter_code').val();
  if (filter_code) {
    url += '&filter_code=' + encodeURIComponent(filter_code);
  }

  var equal_form_type_code = $('#input-equal_form_type_code').val();
  if (equal_form_type_code) {
    url += '&equal_form_type_code=' + encodeURIComponent(equal_form_type_code);
  }
  
  var filter_keyname = $('#input-filter_keyname').val();
  if (filter_keyname) {
    url += '&filter_keyname=' + encodeURIComponent(filter_keyname);
  }

  var filter_supplier_name = $('#input-filter_supplier_name').val();
  if (filter_supplier_name) {
    url += '&filter_supplier_name=' + encodeURIComponent(filter_supplier_name);
  }

  var filter_product_name = $('#input-filter_product_name').val();
  if (filter_product_name) {
    url += '&filter_product_name=' + encodeURIComponent(filter_product_name);
  }
  
  var filter_receiving_date = $('#input-filter_receiving_date').val();
  if (filter_receiving_date) {
    url += '&filter_receiving_date=' + encodeURIComponent(filter_receiving_date);
  }

  var filter_status_code = $('#input-filter_status_code').val();
  if (filter_status_code) {
    url += '&filter_status_code=' + encodeURIComponent(filter_status_code);
  }

  return url;
}

//下載進貨報表
$('#btn-export01').on('click', function () {
  $('#modal-export-loading').modal('show');
  var dataString = $('#filter-form_content').serialize();
  var queryUrl = getFilterUrl(); 
  var export01_url = "{{ $export01_url }}?" + queryUrl;
  console.log('export01_url='+export01_url);

  $.ajax({
      type: "POST",
      url: export01_url,
      data: dataString,
      cache: false,
      xhrFields:{
          responseType: 'blob'
      },
      beforeSend: function () {
        console.log('beforeSend');
       $('#btn-export01').attr("disabled", true);
      },
      success: function(data)
      {
        console.log('success');
        let link = document.createElement('a');
        link.href = window.URL.createObjectURL(data);
        let now_string = moment().format('YYYY-MM-DD_hh-mm-ss');
        link.download = '進貨報表_'+now_string+'.xlsx';
        link.click();
      },
      complete: function () {
        console.log('complete');
        $('#modal-export-loading').modal('hide');
        $('#btn-export01').attr("disabled", false);
      },
      fail: function(data) {
        console.log('fail');
        alert('Not downloaded');
      }
  });
});

</script>
@endsection