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
              @foreach($receiving_order_statuses as $status)
              <option value="{{ $status->code }}" >{{ $status->name }}</option>
              @endforeach
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
//選縣市查區
$('#input-shipping_state_id').on('change', function(){
  var state_id = $(this).val();
  if(state_id){
    $.ajax({
      type:'get',
      url: "{{ route('lang.admin.localization.divisions.getJsonCities') }}?filter_parent_id=" + state_id,
      data:'filter_parent_id='+state_id,
      success:function(json){
        html = '<option value=""> -- </option>';
        
        $.each(json, function(i, item) {
          html += '<option value="'+item.city_id+'">'+item.name+'</option>';
        });

        $('#input-shipping_city_id').html(html);
        
        $('#input-shipping_road').val('');

      }
    }); 
  }else{
    $('#input-shipping_city_id').html('<option value="">--</option>');
  }  
});

$('#receiving_order').on('click', 'thead a, .pagination a', function(e) {
  e.preventDefault();

  $('#receiving_order').load(this.href);
});

$('#button-filter').on('click', function() {
  url = '';

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

  var filter_receiving_date = $('#input-filter_receiving_date').val();
  if (filter_receiving_date) {
    url += '&filter_receiving_date=' + encodeURIComponent(filter_receiving_date);
  }

  var filter_status_code = $('#input-filter_status_code').val();
  if (filter_status_code) {
    url += '&filter_status_code=' + encodeURIComponent(filter_status_code);
  }
  
  url = "{{ $list_url }}?" + url;

  $('#receiving_order').load(url);
});


</script>
@endsection