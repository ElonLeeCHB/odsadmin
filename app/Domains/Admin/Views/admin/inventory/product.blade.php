@extends('admin.app')

@section('pageJsCss')
<script src="{{ asset('admin-asset/javascript/jquery/datetimepicker/moment.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('admin-asset/javascript/jquery/datetimepicker/moment-with-locales.min.js') }}" type="text/javascript" ></script>
<script src="{{ asset('admin-asset/javascript/jquery/datetimepicker/daterangepicker.js') }}" type="text/javascript" ></script>
<link  href="{{ asset('admin-asset/javascript/jquery/datetimepicker/daterangepicker.css') }}" rel="stylesheet" type="text/css"/>
@endsection

@section('columnLeft')
  @include('admin.common.column_left')
@endsection

@section('content')
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="float-end">
        <button type="button" data-bs-toggle="tooltip" title="Filter" onclick="$('#filter-product').toggleClass('d-none');" class="btn btn-light d-md-none d-lg-none"><i class="fas fa-filter" style="font-size:18px"></i></button>
        <a href="{{ $add_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_add }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i></a>
        <button type="submit" form="form-term" formaction="{{ $delete_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_delete }}" onclick="return confirm('{{ $lang->text_confirm }}');" class="btn btn-danger"><i class="fa-regular fa-trash-can"></i></button>
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
              <input type="text" name="filter_name" value="" placeholder="{{ $lang->column_name }}" id="input-name" list="list-name" class="form-control"/>
              <datalist id="list-name"></datalist>
            </div>

            <div class="mb-3">
              <label class="form-label">{{ $lang->column_is_salable }}</label>
              <select name="filter_is_salable" id="input-filter_is_salable" class="form-select">
                <option value="">{{ $lang->text_select }}</option>
                <option value="1">{{ $lang->text_yes }}</option>
                <option value="0">{{ $lang->text_no }}</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">{{ $lang->column_is_active }}</label>
              <select name="equal_is_active" id="input-equal_is_active" class="form-select">
                <option value="*"> -- </option>
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
              <img src="{{ asset('backend/assets/image/ajax-loader.gif') }}" width="50"/>     
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
<script type="text/javascript">
$('#product').on('click', 'thead a, .pagination a', function(e) {
  e.preventDefault();

  $('#product').load(this.href);
});

$('#button-filter').on('click', function() {
  url = '';

  var filter_name = $('#input-name').val();

  if (filter_name) {
    url += '&filter_name=' + encodeURIComponent(filter_name);
  }

  var filter_model = $('#input-model').val();

  if (filter_model) {
    url += '&filter_model=' + encodeURIComponent(filter_model);
  }

  var filter_is_salable = $('#input-filter_is_salable').val();

  if (filter_is_salable) {
    url += '&filter_is_salable=' + encodeURIComponent(filter_is_salable);
  }

  var equal_is_active = $('#input-equal_is_active').val();

  if (equal_is_active) {
    url += '&equal_is_active=' + encodeURIComponent(equal_is_active);
  }

  url = "{{ $list_url }}?" + url;

  $('#product').load(url);
});
</script>
@endsection