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
        <button type="button" id="btn-inventory_product_list" data-bs-toggle="tooltip" data-loading-text="Loading..." title="匯出" class="btn btn-info" aria-label="匯出"><i class="fas fa-file-export"></i></button>
        <button type="button" data-bs-toggle="tooltip" title="Filter" onclick="$('#filter-product').toggleClass('d-none');" class="btn btn-light d-md-none d-lg-none"><i class="fas fa-filter" style="font-size:18px"></i></button>
        <a href="{{ $add_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_add }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i></a>
        <button type="submit" form="form-product" formaction="{{ $delete_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_delete }}" onclick="return confirm('{{ $lang->text_confirm }}');" class="btn btn-danger"><i class="fa-regular fa-trash-can"></i></button>
      </div>
      <h1>{{ $lang->heading_title }}</h1>
      @include('admin.common.breadcumb')
    </div>
  </div>
  <div class="container-fluid">
  <div class="row">
    <div id="filter-product" class="col-lg-3 col-md-12 order-lg-last d-none d-lg-block mb-3">
      <form id="filter-form">
        <div class="card">
          <div class="card-header"><i class="fas fa-filter"></i> Filter</div>
          <div class="card-body">

            <div class="mb-3">
              <label class="form-label">{{ $lang->column_name }}</label>
              <input type="text" id="input-filter_name" name="filter_name" value="" placeholder="{{ $lang->column_name }}" list="list-name" class="form-control"/>
              <datalist id="list-name"></datalist>
            </div>

            <div class="mb-3">
              <label class="form-label">{{ $lang->column_supplier }}</label>
              <input type="text" id="input-filter_supplier_name" name="filter_supplier_name" value="" placeholder="{{ $lang->column_supplier }}" list="list-supplier" class="form-control"/>
              <datalist id="list-supplier"></datalist>
            </div>

            <div class="mb-3">
              <label class="form-label">{{ $lang->column_source_type }}</label>
              <select id="input-filter_source_type_code" name="filter_source_type_code"  class="form-select">
                <option value="">{{ $lang->text_select }}</option>
                @foreach($source_codes as $source_type)
                <option value="{{ $source_type->code }}">{{ $source_type->label }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">{{ $lang->column_accounting_category_code }}</label>
              <select id="input-filter_accounting_category_code" name="filter_accounting_category_code"  class="form-select">
                <option value="">{{ $lang->text_select }}</option>
                @foreach($accounting_categories as $accounting_category)
                <option value="{{ $accounting_category->code }}">{{ $accounting_category->label }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">{{ $lang->column_is_salable }}</label>
              <select id="input-filter_is_salable" name="filter_is_salable" class="form-select">
                <option value=""> -- </option>
                <option value="1">{{ $lang->text_yes }}</option>
                <option value="0">{{ $lang->text_no }}</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">{{ $lang->column_is_inventory_managed }}</label>
              <select id="input-filter_is_inventory_managed" name="filter_is_inventory_managed" class="form-select">
                <option value=""> -- </option>
                <option value="1" selected>{{ $lang->text_yes }}</option>
                <option value="0">{{ $lang->text_no }}</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">{{ $lang->column_is_active }}</label>
              <select id="input-equal_is_active" name="equal_is_active" class="form-select">
                <option value="*"> -- </option> {{-- 這裡的 value 必須是 * ，其它篩選則不必。--}}
                <option value="1" selected>{{ $lang->text_yes }}</option>
                <option value="0">{{ $lang->text_no }}</option>
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
      <div class="card-header"><i class="fas fa-list"></i> {{ $lang->text_list }}</div>
      <div id="product" class="card-body">{!! $list !!}</div>
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
$('#product').on('click', 'thead a, .pagination a', function(e) {
  e.preventDefault();

  $('#product').load(this.href);
});

$('#button-filter').on('click', function() {
  url = '';

  var filter_name = $('#input-filter_name').val();

  if (filter_name) {
    url += 'filter_name=' + encodeURIComponent(filter_name);
  }
  
  var filter_supplier_name = $('#input-filter_supplier_name').val();

  if (filter_supplier_name) {
    url += '&filter_supplier_name=' + encodeURIComponent(filter_supplier_name);
  }

  var filter_source_type_code = $('#input-filter_source_type_code').val();

  if (filter_source_type_code) {
    url += '&filter_source_type_code=' + encodeURIComponent(filter_source_type_code);
  }

  var filter_accounting_category_code = $('#input-filter_accounting_category_code').val();

  if (filter_accounting_category_code) {
    url += '&filter_accounting_category_code=' + encodeURIComponent(filter_accounting_category_code);
  }
  
  var filter_is_salable = $('#input-filter_is_salable').val();

  if (filter_is_salable) {
    url += '&filter_is_salable=' + encodeURIComponent(filter_is_salable);
  }

  var filter_is_inventory_managed = $('#input-filter_is_inventory_managed').val();

  if (filter_is_inventory_managed) {
    url += '&filter_is_inventory_managed=' + encodeURIComponent(filter_is_inventory_managed);
  }
  
  var equal_is_active = $('#input-equal_is_active').val();

  if (equal_is_active) {
    url += '&equal_is_active=' + encodeURIComponent(equal_is_active);
  }

  url = "{{ $list_url }}?" + url;

  $('#product').load(url);
});

//匯出盤點表
$('#btn-inventory_product_list').on('click', function () {
  $('#modal-export-loading').modal('show');
  var dataString = $('#filter-form').serialize();

  $.ajax({
      type: "POST",
      url: "{{ $export_inventory_product_list }}",
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
        var link = document.createElement('a');
        link.href = window.URL.createObjectURL(data);
        link.download = '料件.xlsx';
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