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
        <button type="button" id="btn-export-order_products" data-oc-toggle="ajax" data-bs-toggle="tooltip" title="匯出訂單商品，最多兩千筆訂單" class="btn btn-info" data-bs-original-title="匯出訂單商品" aria-label="匯出訂單商品"><i class="fa fa-file-export"></i></button>
        <button type="button" id="btn-batch_print" data-bs-toggle="tooltip" data-loading-text="Loading..." title="批次列印" class="btn btn-info" aria-label="批次列印"><i class="fa fa-print"></i></button>
        {{--<button type="submit" form="form-order" formaction="{{ $copy }}" data-bs-toggle="tooltip" title="複製" class="btn btn-light"><i class="fa-regular fa-copy"></i></button>--}}
        <button type="button" data-bs-toggle="tooltip" title="篩選" onclick="$('#filter-order').toggleClass('d-none');" class="btn btn-light d-md-none d-lg-none"><i class="fas fa-filter" style="font-size:18px"></i></button>
        <a href="{{ route('lang.admin.sale.orders.form') }}" data-bs-toggle="tooltip" title="{{ $lang->button_add }}" class="btn btn-primary"><i class="fas fa-plus"></i></a>
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
          <!--{{-- 
          <div class="mb-3">
            <label class="form-label">快速搜尋</label>
            <select name="filter_predifined" id="input-predifined" class="form-select">
              <option value=""> -- </option>
              <option value="tomorrow">明日訂單</option>
              <option value="two_weeks_unconfirmed">兩周內未確認訂單</option>
              <option value="unmatched">任搭選項待分配</option>
            </select>
          </div>
          --}}-->
          <div class="mb-3">
            <label class="form-label">{{ $lang->column_status }}</label>
            <select id="input-status_id" name="filter_status_id" class="form-select">
            <option value="">--</option>
              @foreach($order_statuses as $status)
              <option value="{{ $status->id }}" >{{ $status->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">{{ $lang->column_code }}</label>
            <input type="text" name="filter_code" value="" placeholder="{{ $lang->column_code }}" id="input-code" list="list-code" class="form-control"/>
          </div>
          <div class="mb-3">
            <label data-bs-toggle="tooltip" title="例如：2023-02-20 或不加橫線 20230220 或範圍 20230301-20230331 或大於某日 >20230101 或小於某日 <20230101" style="font-weight: bolder;" >送達日期 <i class="fa fa-question-circle" aria-hidden="true"></i></label>
            <input type="text" name="filter_delivery_date" value="" placeholder="例如 2023-02-20" id="input-delivery_date" class="form-control"/>
          </div>
          <div class="mb-3">
            <label class="form-label">{{ $lang->column_keyname }}</label>
            <input type="text" name="filter_keyname" value="" placeholder="{{ $lang->placeholder_keyname }}" id="input-keyname" class="form-control"/>
          </div>
          <div class="mb-3">
            <label class="form-label">{{ $lang->column_phone }}</label>
            <input type="text" name="filter_phone" value="" placeholder="{{ $lang->placeholder_phone }}" id="input-phone" class="form-control"/>
          </div>
          <div class="mb-3">
            <label class="form-label">縣市</label>
            <select id="input-shipping_state_id" name="shipping_state_id" class="form-select">
              <option value="">--</option>
              @foreach($states as $state)
              <option value="{{ $state->id }}">{{ $state->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">鄉鎮市區</label>
            <select id="input-shipping_city_id" name="shipping_city_id" class="form-select">
              <option value="">--</option>
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

$('#product').on('click', 'thead a, .pagination a', function(e) {
  e.preventDefault();

  $('#product').load(this.href);
});

$('#button-filter').on('click', function() {
  url = '';
  
  var filter_predifined = $('#input-predifined').val();
  if (filter_predifined) {
    url += '&filter_predifined=' + encodeURIComponent(filter_predifined);
  }

  var filter_code = $('#input-code').val();
  if (filter_code) {
    url += '&filter_code=' + encodeURIComponent(filter_code);
  }

  var filter_delivery_date = $('#input-delivery_date').val();
  if (filter_delivery_date) {
    url += '&filter_delivery_date=' + encodeURIComponent(filter_delivery_date);
  }
  
  var filter_keyname = $('#input-keyname').val();
  if (filter_keyname) {
    url += '&filter_keyname=' + encodeURIComponent(filter_keyname);
  }

  var filter_status_id = $('#input-status_id').val();
  if (filter_status_id) {
    url += '&filter_status_id=' + encodeURIComponent(filter_status_id);
  }

  var filter_phone = $('#input-phone').val();
  if (filter_phone) {
    url += '&filter_phone=' + encodeURIComponent(filter_phone);
  }

  var filter_shipping_state_id = $('#input-shipping_state_id').val();
  if (filter_shipping_state_id) {
    url += '&filter_shipping_state_id=' + encodeURIComponent(filter_shipping_state_id);
  }

  var filter_shipping_city_id = $('#input-shipping_city_id').val();
  if (filter_shipping_city_id) {
    url += '&filter_shipping_city_id=' + encodeURIComponent(filter_shipping_city_id);
  }
  
  url = "{{ route('lang.admin.sale.orders.list') }}?" + url;

  $('#product').load(url);
});


$(function(){
  //匯出按鈕
  $('#btn-export-order_products').on('click', function () {
    $('#modal-export-loading').modal('show');
    var dataString = $('#filter-form').serialize();

    $.ajax({
        type: "POST",
        url: "{{ $export_order_products_url }}",
        data: dataString,
        cache: false,
        xhrFields:{
            responseType: 'blob'
        },
        beforeSend: function () {
          console.log('beforeSend');
          $('#btn-export-order_products').attr("disabled", true);
        },
        success: function(data)
        {
          console.log('success');
          var link = document.createElement('a');
          link.href = window.URL.createObjectURL(data);
          link.download = 'order_products.xlsx';
          link.click();
        },
        complete: function () {
          console.log('complete');
         $('#modal-export-loading').modal('hide');
         $('#btn-export-order_products').attr("disabled", false);
        },
        fail: function(data) {
          console.log('fail');
          alert('Not downloaded');
        }
    });
  });
  
  //批次列印按鈕
  $('#btn-batch_print').on('click', function () {
    //var button = $(this);
    var button = this;
    var dataString = $('#filter-form').serialize();

    $.ajax({
        type: "POST",
        url: "{{ $batch_print_url }}",
        data: dataString,
        cache: false,
        xhrFields:{
            responseType: 'blob'
        },
        beforeSend: function () {
          $(button).prop('disabled', true).addClass('loading');
        },
        complete: function () {
            $(button).prop('disabled', false).removeClass('loading');
        },
        success: function(data)
        {
          console.log('success');
          var link = document.createElement('a');
          link.href = window.URL.createObjectURL(data);
          link.download = 'order_products.xlsx';
          link.click();
        }
    });


  });
})
</script>
@endsection