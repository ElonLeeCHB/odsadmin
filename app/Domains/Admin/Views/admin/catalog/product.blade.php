@extends('admin.app')

@section('pageJsCss')
<link  href="{{ asset('assets2/public/vendor/select2/select2.min.css') }}" rel="stylesheet" type="text/css"/>
<script src="{{ asset('assets2/public/vendor/select2/select2.min.js') }}"></script>

<style>
.select2-container .select2-selection--single{
   height:100% !important;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
     height:100% !important;
}
</style>
@endsection

@section('columnLeft')
  @include('admin.common.column_left')
@endsection

@section('content')
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
    <div class="float-end">
        <button type="button" data-bs-toggle="tooltip" title="{{ $lang->text_filter }}" onclick="$('#filter-product').toggleClass('d-none');" class="btn btn-light d-md-none d-lg-none"><i class="fas fa-filter" style="font-size:18px"></i></button>
        <a id="button-add" href="{{ $add_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_add }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i></a>
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
          <div class="card-header"><i class="fas fa-filter"></i> {{ $lang->button_filter }}</div>
          <div class="card-body">

            <div class="mb-3">
              <label class="form-label">{{ $lang->column_name }}</label>
              <input type="text" id="input-filter_name" name="filter_name" value="" placeholder="{{ $lang->column_name }}" list="list-name" class="form-control"/>
              <datalist id="list-name"></datalist>
            </div>

            {{-- 
            <div class="mb-3">
              <label class="form-label">商品標籤</label>
              <select id="input-filter_product_tags" name="filter_product_tags[]" class="select2-multiple form-control" multiple="multiple">
                @foreach($product_tags ?? [] as $term_id => $product_tag_name)
                <option value="{{ $term_id }}">{{ $product_tag_name }}</option>
                @endforeach
              </select>
            </div>
             --}}

            <div class="mb-3">
              <label class="form-label">{{ $lang->column_is_active }}</label>
              <select name="equal_is_active" id="input-equal_is_active" class="form-select">
                <option value="*">{{ $lang->text_select }}</option>
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

@endsection

@section('buttom')
<script type="text/javascript">
$('#product').on('click', 'thead a, .pagination a', function(e) {
  e.preventDefault();

  $('#product').load(this.href);
});

$('#button-filter').on('click', function() {
  url = '?';

  var filter_name = $('#input-filter_name').val();

  if (filter_name) {
    url += '&filter_name=' + encodeURIComponent(filter_name);
  }

  var filter_product_tags = $('#input-filter_product_tags').val();

  if (filter_product_tags && filter_product_tags.length > 0) {
    url += '&filter_product_tags=' + encodeURIComponent(filter_product_tags);
  }

  var equal_is_active = $('#input-equal_is_active').val();
  
  if (equal_is_active) {
    url += '&equal_is_active=' + encodeURIComponent(equal_is_active);
  }

  list_url = "{{ $list_url }}" + url;

  $('#product').load(list_url);

  add_url = $("#button-add").attr("href") + url
  $("#button-add").attr("href", add_url);
});

$('#input-filter_product_tags').select2({
  width:'100%',
});


</script>
@endsection