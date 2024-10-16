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
        <button type="button" id="btn-status" data-bs-toggle="tooltip" data-loading-text="Loading..." title="變更狀態" class="btn btn-info" aria-label="變更狀態"><i class="fas fa-check-circle"></i></button>
        <button type="submit" form="form-member" data-bs-toggle="tooltip" title="{{ $lang->save }}" class="btn btn-primary"><i class="fa fa-save"></i></button>
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
            <li class="nav-item"><a href="#tab-data" data-bs-toggle="tab" class="nav-link active">{{ $lang->tab_data }}</a></li>
            <li class="nav-item"><a href="#tab-products" data-bs-toggle="tab" class="nav-link">{{ $lang->tab_products }}</a></li>
          </ul>
          <form id="form-member" action="{{ $save_url }}" method="post" data-oc-toggle="ajax">
            @csrf
            @method('POST')

            <input type="hidden" id="input-receiving_order_id" name="receiving_order_id" value="{{ $receiving_order_id }}"/>

            <div class="tab-content">

              <div id="tab-data" class="tab-pane active">
                <fieldset>
                  <div class="row mb-3 required">
                    <label for="input-location_name" class="col-sm-2 col-form-label">{{ $lang->column_location_name }}</label>
                    <div class="col-sm-10">
                      <select id="input-location_id" name="location_id" class="form-select">
                        <option value="">--</option>
                        @foreach($locations as $location)
                        <option value="{{ $location->id }}" @if($location->id == $location_id) selected @endif>{{ $location->name }}</option>
                        @endforeach
                      </select>
                      <div id="error-location_id" class="invalid-feedback"></div>
                    </div>
                  </div>

                  <div class="row mb-3">
                    <label for="input-code" class="col-sm-2 col-form-label">{{ $lang->column_code }}</label>
                    <div class="col-sm-10">
                      <input type="text" name="code" value="{{ $receiving_order->code ?? '' }}" id="input-code" class="form-control" readonly/>
                      <div id="error-code" class="invalid-feedback"></div>
                    </div>
                  </div>

                  <div class="row mb-3 required">
                    <label for="input-form_type_code" class="col-sm-2 col-form-label">單別</label>
                    <div class="col-sm-10">
                      <select id="input-form_type_code" name="form_type_code" class="form-select">
                        <option value="">--</option>
                        <option value="RMT" @if($receiving_order->form_type_code == 'RMT') selected @endif>原物料</option>
                        <option value="EXP" @if($receiving_order->form_type_code == 'EXP') selected @endif>費用</option>
                      </select>
                      <div id="error-form_type_code" class="invalid-feedback"></div>
                    </div>
                  </div>

                  <div class="row mb-3 required">
                    <label for="input-receiving_date" class="col-sm-2 col-form-label">{{ $lang->column_receiving_date }}</label>
                    <div class="col-sm-10">
                      <input type="text" name="receiving_date" value="{{ $receiving_order->receiving_date_ymd }}" id="input-receiving_date" class="form-control date"/>
                      <div id="error-receiving_date" class="invalid-feedback"></div>
                    </div>
                  </div>

                  <div class="row mb-3 required">
                    <label for="input-supplier" class="col-sm-2 col-form-label">{{ $lang->column_supplier }}</label>
                    <div class="col-sm-10">
                      <div class="input-group">
                        <div class="col-sm-3">
                          <input type="text" id="input-supplier_id" name="supplier_id" value="{{ $receiving_order->supplier_id ?? 0 }}" placeholder="廠商流水號" class="form-control" readonly=""/>
                          <div class="form-text">廠商流水號</div>
                          <div id="error-supplier_id" class="invalid-feedback"></div>
                        </div>
                        <div class="col-sm-6">
                          <input type="text" id="input-supplier_name" name="supplier_name" value="{{ $receiving_order->supplier_name }}" placeholder="{{ $lang->column_supplier_name }}" class="form-control" data-oc-target="autocomplete-supplier_name"/>
                          <div class="form-text">廠商名稱 (可輸入文字做查詢)</div>
                          <ul id="autocomplete-supplier_name" class="dropdown-menu"></ul>
                        </div>
                        <div class="col-sm-3">
                          <input type="text" id="input-tax_id_num" name="tax_id_num" value="{{ $receiving_order->tax_id_num }}" placeholder="{{ $lang->column_tax_id_num }}" class="form-control" />
                          <div class="form-text"></div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="row mb-3 required">
                    <label for="input-invoice_type" class="col-sm-2 col-form-label">{{ "單據"}}</label>
                    <div class="col-sm-10">
                      <div class="input-group">
                        <div class="col-sm-3">
                          
                        <select id="input-invoice_type" name="invoice_type" class="form-select" readonly>
                            <option value="">--</option>
                            @foreach($invoice_types as $code => $invoice_type)  
                            <option value="{{ $invoice_type['code'] }}" @if($invoice_type['code'] == $receiving_order->invoice_type) selected @endif>{{ $invoice_type['name'] }}</option>
                            @endforeach
                          </select>
                        </div>
                        <div class="col-sm-6">
                          <input type="text" id="input-invoice_num" name="invoice_num" value="{{ $receiving_order->invoice_num }}" placeholder="{{ '單據號碼' }}" class="form-control" data-oc-target="autocomplete-supplier_name"/>
                          <div class="form-text">單據號碼</div>
                          <ul id="autocomplete-invoice_num" class="dropdown-menu"></ul>
                        </div>
                        </div>
                      </div>
                    </div>
                  </div>


                  <div class="row mb-3 required">
                    <label for="input-tax_type_code" class="col-sm-2 col-form-label">{{ $lang->column_tax_type }}</label>
                    <div class="col-sm-10">
                      <div class="input-group">
                        <div class="col-sm-3">
                          <select id="input-tax_type_code" name="tax_type_code" class="form-select" readonly>
                            <option value="">--</option>
                            @foreach($tax_types as $code => $tax_type)
                            <option value="{{ $tax_type->code }}" @if($tax_type->code == $receiving_order->tax_type_code) selected @endif>{{ $tax_type->name }}</option>
                            @endforeach
                          </select>
                          <div id="error-tax_type_code" class="invalid-feedback"></div>
                        </div>
                        <div class="col-sm-3">
                          <input type="text" id="input-formatted_tax_rate" name="formatted_tax_rate" value="{{ $receiving_order->formatted_tax_rate }}" placeholder="{{ $lang->column_tax_rate }}" class="form-control" readonly/>
                        </div>
                        <div class="col-sm-1" style="font-size: 1.3rem;">%</div>

                      </div>

                    </div>
                  </div>

                  <div class="row mb-3">
                    <label for="input-status_code" class="col-sm-2 col-form-label">{{ $lang->column_status }}</label>
                    <div class="col-sm-10">
                      <select id="input-status_code" name="status_code" class="form-select">
                        <option value="">--</option>
                          @foreach($statuses as $status)
                          <option value="{{ $status->code }}" @if($status->code == $receiving_order->status_code) selected @endif>{{ $status->name }}</option>
                          @endforeach
                      </select>
                      <div id="error-status_code" class="invalid-feedback"></div>
                    </div>
                  </div>

                  <div class="row mb-3">
                    <label for="input-comment" class="col-sm-2 col-form-label">{{ $lang->column_comment }}</label>
                    <div class="col-sm-10">
                      <textarea id="input-comment" name="comment" class="form-control" >{{ $receiving_order->comment }}</textarea>
                    </div>
                  </div>

                </fieldset>
              </div>



              <div id="tab-products" class="tab-pane">
                <table class="table table-bordered">
                  <tbody id="order-totals">
                    <tr>
                      <td class="text-end col-sm-2"><strong>{{ $lang->column_before_tax }}</strong></td>
                      <td class="text-end">
                        <input type="text" id="input-before_tax" name="before_tax" value="{{ $receiving_order->before_tax }}" class="form-control" oninput="calcTotals()">
                      </td>
                    </tr>
                    <tr>
                      <td class="text-end col-sm-2"><strong>{{ $lang->column_tax }}</strong></td>
                      <td class="text-end">
                        <input type="text" id="input-tax" name="tax" value="{{ $receiving_order->tax }}" class="form-control" oninput="calcTotals()">
                      </td>
                    </tr>
                    <tr>
                      <td class="text-end col-sm-2"><strong>{{ $lang->column_total }}</strong></td>
                      <td class="text-end">
                        <input type="text" id="input-total" name="total" value="{{ $receiving_order->total }}" class="form-control">
                      </td>
                    </tr>
                  </tbody>
                </table>


                <style>
                    #products .row1 {
                      border: 1px solid #ccc;
                      padding: 2px;
                      margin-bottom: 2px;
                    }
                </style>

              @php $product_row = 1; @endphp
              <div class="table-responsive">
                <table id="products" class="table table-striped table-bordered table-hover">
                  <thead>
                    <tr>
                      <td class="text-left"></td>
                      <td class="text-left">品名</td>
                      <td class="text-left">規格</td>
                      <td class="text-left" style="width:80px;"><label data-bs-toggle="tooltip" title="若要選擇不同單位，請先重新選擇料件" style="font-weight: bolder;" >進貨<BR>單位 <i class="fa fa-question-circle" aria-hidden="true"></i></label></td>
                      <td class="text-left" style="width:100px;">進貨<BR>數量</td>
                      <td class="text-left" style="width:100px;">進貨<BR>單價</td>
                      <td class="text-left" style="width:100px;">進貨<BR>金額</td>
                      <td class="text-left" style="width:80px;">庫存<BR>單位</td>
                      <td class="text-left" style="width:100px;">庫存<BR>單價</td>
                      <td class="text-left" style="width:100px;"><label data-bs-toggle="tooltip" title="轉入庫存數量" style="font-weight: bolder;" >入庫<BR>數量 <i class="fa fa-question-circle" aria-hidden="true"></i></label></td>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($receiving_products as $receiving_product)
                    <tr id="product-row{{ $product_row }}" data-rownum="{{ $product_row }}">
                      <td class="text-start">
                        <button type="button" onclick="$('#product-row{{ $product_row }}').remove();" data-toggle="tooltip" title="" class="btn btn-danger" data-original-title="Remove"><i class="fa fa-minus-circle"></i></button>
                      </td>
                      <td class="text-start" style="padding-left: 1px;">
                        <div class="container input-group col-sm-12" style="padding-left: 1px;">
                          <div class="col-sm-3">
                            <input type="text" id="input-products-id-{{ $product_row }}" name="products[{{ $product_row }}][id]" value="{{ $receiving_product->product_id ?? '' }}" class="form-control" readonly>
                          </div>
                          <div class="col-sm-8">
                            <input type="text" id="input-products-name-{{ $product_row }}" name="products[{{ $product_row }}][name]" value="{{ $receiving_product->product_name ?? '' }}" data-rownum="{{ $product_row }}" class="form-control schProductName" data-oc-target="autocomplete-product_name-{{ $product_row }}" autocomplete="off">
                            <ul id="autocomplete-product_name-{{ $product_row }}" class="dropdown-menu"></ul>
                          </div>
                          <div class="col-sm-1">
                            <div class="input-group-append">
                              <a href="{{ $receiving_product->product_edit_url ?? '' }}" class="btn btn-outline-secondary" target="_blank"><i class="fas fa-external-link-alt"></i></a>
                            </div>
                          </div>
                        </div>
                      </td>
                      <td class="text-start">
                        <input type="text" id="input-products-specification-{{ $product_row }}" name="products[{{ $product_row }}][specification]" value="{{ $receiving_product->product_specification ?? '' }}" class="form-control" readonly>
                      </td>
                      <td class="text-start">
                        <select id="input-products-receiving_unit_code-{{ $product_row }}" name="products[{{ $product_row }}][receiving_unit_code]" class="form-control" >
                          <option value="{{ $receiving_product->receiving_unit_code ?? '' }}_{{ $receiving_product->receiving_unit_name ?? '' }}" data-factor="{{ $receiving_product->factor }}" selected>{{ $receiving_product->receiving_unit_name ?? '' }}</option>
                        </select>
                      </td>
                      <td class="text-start">
                        <input type="text" id="input-products-receiving_quantity-{{ $product_row }}" name="products[{{ $product_row }}][receiving_quantity]" value="{{ $receiving_product->receiving_quantity }}" class="form-control text-end productPriceInputs clcProduct" data-rownum="{{ $product_row }}">
                      </td>
                      <td class="text-start">
                        <input type="text" id="input-products-price-{{ $product_row }}" name="products[{{ $product_row }}][price]" value="{{ $receiving_product->price ?? 0 }}" class="form-control text-end productPriceInputs clcProduct" data-rownum="{{ $product_row }}">
                      </td>
                      <td class="text-start">
                        <input type="text" id="input-products-amount-{{ $product_row }}" name="products[{{ $product_row }}][amount]" value="{{ $receiving_product->amount ?? 0 }}" class="form-control text-end productAmountInputs clcProduct" data-rownum="{{ $product_row }}" readonly>
                      </td>
                      <td class="text-start">
                        <input type="text" id="input-products-stock_unit_name-{{ $product_row }}" name="products[{{ $product_row }}][stock_unit_name]" value="{{ $receiving_product->stock_unit_name ?? '' }}" class="form-control" readonly>
                        <input type="hidden" id="input-products-stock_unit_code-{{ $product_row }}" name="products[{{ $product_row }}][stock_unit_code]" value="{{ $receiving_product->stock_unit_code ?? '' }}">
                      </td>
                      <td class="text-start">
                        <input type="text" id="input-products-stock_price-{{ $product_row }}" name="products[{{ $product_row }}][stock_price]" value="{{ $receiving_product->stock_price ?? 0 }}" class="form-control" readonly>
                      </td>
                      <td class="text-start">
                        <input type="text" id="input-products-stock_quantity-{{ $product_row }}" name="products[{{ $product_row }}][stock_quantity]" value="{{ $receiving_product->stock_quantity ?? 0 }}" class="form-control productReceivingQuantityInputs clcProduct" data-rownum="{{ $product_row }}">
                      </td>
                    </tr>
                    @php $product_row++; @endphp
                    @endforeach
                  </tbody>
                  <tfoot>
                    <tr>
                      <td colspan="10" class="text-left">
                        <button type="button" onclick="addReceivingProduct()" data-toggle="tooltip" title="" class="btn btn-primary" data-original-title=""><i class="fa fa-plus-circle"></i></button>
                      </td>
                    </tr>
                  </tfoot>
                </table>
              </div>





              </div>
            </form>
            </div>
            </div>
        </div>
    </div>
</div>

{{-- 變更狀態 --}}
<div id="modal-status" class="modal fade" style="">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-file-excel"></i> 變更狀態</h5> <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="form-status" method="post" data-oc-toggle="ajax">
          @csrf
          @method('POST')
          <input type="hidden" name="update_status[id]" value="{{ $receiving_order->id }}">
          <div class="row mb-3">
            <label for="input-update_status_code" class="col-sm-2 col-form-label">狀態</label>
            <div class="col-sm-10">
              <select id="input-update_status_code" name="update_status[status_code]"  class="form-control">
                <option value="" selected> -- </option>
                @foreach($statuses as $status)
                <option value="{{ $status->code }}">{{ $status->name }}</option>
                @endforeach
              </select>
              <div id="error-update_status_code" class="invalid-feedback"></div>
            </div>
          </div>

          <div class="row mb-3 justify-content-end">
            <div class="col-sm-10">
              <button type="button" id="btn-status_save" data-bs-toggle="tooltip" data-loading-text="Loading..." title="確定" class="btn btn-info" aria-label="確定">確定</button>
            </div>
          </div>
        </form>

        <div class="loadingdiv" id="loading" style="display: none;">
          <img src="{{ asset('image/ajax-loader.gif') }}" width="50"/>
        </div>


      </div>
    </div>
  </div>
</div>
<script type="text/javascript">
//顯示變更狀態彈窗
$('#btn-status').on('click', function () {
  $('#modal-status').modal('show');
});
//在變更狀態彈窗裡按下確定
$('#btn-status_save').on('click', function () {
  $.ajax({
    url: "{{ $status_save_url }}",
    method: 'POST',
    data: $('#form-status').serialize(),
    dataType: 'json',
    success: function(data) {
      if(data.success){
        $('#alert').prepend('<div class="alert alert-success alert-dismissible"><i class="fa-solid fa-circle-check"></i> ' + data['success'] + ' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
        let status_code = data.data.status_code;
        let status_name = data.data.status_name;
        $('#input-update_status_code').val(status_code);
        $('#input-status_code').val(status_code);
        console.log(status_code)
        // a lot of shit
      }else{
        $('#alert').prepend('<div class="alert alert-danger alert-dismissible"><i class="fa-solid fa-circle-exclamation"></i> ' + data['error'] + ' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
      }
    },
    complete: function () {
      console.log('complete');
      $('#modal-status').modal('hide');
    },
  });
});
</script>
@endsection

@section('buttom')
<script type="text/javascript">


$(document).ready(function () {
  // 進貨單價、進貨數量、進貨金額 觸發計算
  $('#products').on('focusout', '.clcProduct', function(){
setInterval(() => {
    let rownum = $(this).closest('[data-rownum]').data('rownum');
    calcProduct(rownum)
}, 2000);
  });

  // 觸發查詢料件的 click 事件
  $('.schProductName').trigger('click');
});


// 查廠商名稱
$('#input-supplier_name').autocomplete({
  'minLength': 1,
  'source': function (request, response) {
    var regex = /[a-zA-Z0-9\u3105-\u3129]+/; // 注音符號
    if (regex.test(request)) {
      return;
    }else{
      $.ajax({
        url: "{{ $supplier_autocomplete_url }}?filter_keyword=" + encodeURIComponent(request),
        dataType: 'json',
        success: function (json) {
          response(json);
        }
      });
    }
  },
  'select': function (item) {
    $('#input-supplier_id').val(item.supplier_id);
    $('#input-supplier_name').val(item.supplier_name);
    $('#input-tax_id_num').val(item.tax_id_num);
    $('#input-tax_type_code').val(item.tax_type_code);
    chgTaxRate()
  }
});

// 查料件名稱
$(document).on('click', '.schProductName', function() {
  $('.schProductName').autocomplete({
    'source': function (request, response) {
      var supplier_id = $('#input-supplier_id').val();
      var supplier_url = '';
      var form_type_code = $('#input-form_type_code').val();

      if(request.length == 0 && $.isNumeric(supplier_id) && supplier_id > 0){
        supplier_url = '&equal_supplier_id=' + supplier_id + '&limit=0&pagination=0';
      }

      ajaxUrl = '{{ $product_autocomplete_url }}?equal_is_active=1&with=product_units&filter_name=' + encodeURIComponent(request) + '&extra_columns=stock_unit_code,stock_unit_name' + supplier_url;

      if(form_type_code == 'RMT'){ //RMT原物料，如果是，則庫存管理=1。
        ajaxUrl += '&equal_is_inventory_managed=1'
      }

      $.ajax({
          url: ajaxUrl,
          dataType: 'json',
          success: function (json) {
            response(json);
          }
        });
    },
    'select': function (item) {
      var rownum = $(this).data("rownum");
      $('#input-products-id-'+rownum).val(item.product_id);
      $('#input-products-name-'+rownum).val(item.name);
      $('#input-products-specification-'+rownum).val(item.specification);
      $('#input-products-stock_unit_code-'+rownum).val(item.stock_unit_code);
      $('#input-products-stock_unit_name-'+rownum).val(item.stock_unit_name);
      $('#input-products-product_edit_url-'+rownum).attr('href', item.product_edit_url);
      $('#input-products-product_edit_url-'+rownum).attr('target', '_blank');

      var selectElement = $('#input-products-receiving_unit_code-'+rownum);
      selectElement.empty();

      $.each(item.product_units, function(index, product_unit) {
        var option = $('<option></option>');

        option.val(product_unit.source_unit_code);
        option.text(product_unit.source_unit_name);
        option.attr('data-factor', product_unit.destination_quantity);
        //console.log('unit.source_unit_code='+unit.source_unit_code+', unit.source_unit_name='+unit.source_unit_name+', unit.destination_quantity='+unit.destination_quantity)

        selectElement.append(option);
      });
    }
  });



});
$('.schProductName').first().click();

// 課稅別
$('#input-tax_type_code').on("change", function() {
  //$('#input-tax_type_code').val(tax_type_code); //不允許手動變更，回復原值
  chgTaxRate()
});
// 變更稅率
function chgTaxRate(){
  let tax_type_code = $('#input-tax_type_code').val();
  if(tax_type_code == 1){
    $('#input-formatted_tax_rate').val(5);
    formatted_tax_rate = 5;
  }else if(tax_type_code == 2){
    $('#input-formatted_tax_rate').val(5);
    formatted_tax_rate = 5;
  }else if(tax_type_code == 3){
    $('#input-formatted_tax_rate').val(0);
    formatted_tax_rate = 0;
  }else if(tax_type_code == 4){
    $('#input-formatted_tax_rate').val(0);
    formatted_tax_rate = 0;
  }

  calcAllProducts()
}

// 查統一編號
$('#input-tax_id_num').on('click', function(){
  $('#input-tax_id_num').autocomplete({
    'minLength': 1,
    'source': function (request, response) {
      if (request.length < 8) {
        return;
      }else{
        $.ajax({
          url: "{{ $supplier_autocomplete_url }}?filter_tax_id_num=" + encodeURIComponent(request),
          dataType: 'json',
          success: function (json) {
            response(json);
          }
        });
      }
    },
    'select': function (item) {
      $('#input-supplier_id').val(item.supplier_id);
      $('#input-supplier_name').val(item.supplier_name);
      $('#input-tax_id_num').val(item.tax_id_num);
    }
  });
});

// 計算單一料件
function calcProduct(rownum){
  let price = $('#input-products-price-'+rownum).val() ?? 0;
  let receiving_quantity = $('#input-products-receiving_quantity-'+rownum).val() ?? 0;
  let amount = $('#input-products-amount-'+rownum).val() ?? 0;
  let destination_quantity = 0;
  let factor = $('#input-products-receiving_unit_code-'+rownum + ' option:selected').data('factor');
  
  amount = (price*receiving_quantity).toFixed(2)
  $('#input-products-amount-'+rownum).val(amount);
  
  console.log(receiving_quantity,factor)
  if ($.isNumeric(receiving_quantity) && $.isNumeric(factor)) {
    destination_quantity = (receiving_quantity * factor).toFixed(4);
  }

  let stock_price = 0;
  if ($.isNumeric(receiving_quantity) && destination_quantity > 0) {
    stock_price = (amount / destination_quantity).toFixed(2)
  }
  console.log('amount='+amount+', receiving_quantity='+receiving_quantity+', factor='+factor+', destination_quantity='+destination_quantity);

  // 庫存數量
  $('#input-products-stock_quantity-'+rownum).val(destination_quantity);

  // 庫存單價 = 進貨金額/庫存數量
  $('#input-products-stock_price-'+rownum).val(stock_price);

  calcAllProducts()
}

// 逐一計算全部料件的加總
function calcAllProducts(){
  let sum_amount = 0; // 單身金額加總
  let total = 0; // 單頭稅後總金額

  $('.productAmountInputs').each(function() {
    sum_amount += parseFloat($(this).val()) || 0;
  });

  var tax_type_code = $('#input-tax_type_code').val();
  var tax_rate_pcnt = $('#input-formatted_tax_rate').val()/100;
  var tax = $('#input-tax').val();

  // 應稅內含
  if(tax_type_code == 1){
    total = sum_amount;
    before_tax = total/(1+tax_rate_pcnt);
    before_tax = Math.round(before_tax);
    tax = total - before_tax;
  }
  // 應稅外加
  else if(tax_type_code == 2){
    before_tax = sum_amount;
    tax = Math.round(sum_amount*tax_rate_pcnt);
    total = sum_amount + tax;
  }
  // 零稅率或免稅
  else{
    before_tax = sum_amount;
    tax = 0;
    total = sum_amount;
  }
  before_tax = before_tax.toFixed(0);
  tax = tax.toFixed(0);
  total = total.toFixed(0)

  console.log('sum_amount='+sum_amount+', before_tax='+before_tax+', tax='+tax+', total='+total)

  $('#input-before_tax').val(before_tax);
  $('#input-tax').val(tax);
  $('#input-total').val(total);
}

// 單頭金額變動時，只計算單頭
function calcTotals(){
  let before_tax = $('#input-before_tax').val();
  let tax = $('#input-tax').val();
  total = tax.toNum() + before_tax.toNum();
  $('#input-total').val(total);
}

var product_row = {{ $product_row }};

function addReceivingProduct(){

  html = '<tr id="product-row'+product_row+'" data-rownum="'+product_row+'">';
  html += '  <td class="text-left">';
  html += '    <button type="button" onclick="$(\'#product-row\').remove();" data-toggle="tooltip" title="" class="btn btn-danger" data-original-title="Remove"><i class="fa fa-minus-circle"></i></button>';
  html += '  </td>';
  html += '  <td class="text-left">';

  html += '    <div class="container input-group col-sm-12">';
  html += '      <div class="col-sm-3">';
  html += '        <input type="text" id="input-products-id-'+product_row+'" name="products['+product_row+'][id]" value="" class="form-control" readonly>';
  html += '      </div>';
  html += '      <div class="col-sm-8">';
  html += '        <input type="text" id="input-products-name-'+product_row+'" name="products['+product_row+'][name]" value="" data-rownum="'+product_row+'" class="form-control schProductName" data-oc-target="autocomplete-product_name-'+product_row+'" autocomplete="off">';
  html += '        <ul id="autocomplete-product_name-'+product_row+'" class="dropdown-menu"></ul>';
  html += '      </div>';
  html += '      <div class="col-sm-1">';
  html += '        <div class="input-group-append">';
  html += '          <a href="javascript:void(0);" id="input-products-product_edit_url-'+product_row+'" class="btn btn-outline-secondary"><i class="fas fa-external-link-alt"></i></a>';
  html += '        </div>';
  html += '      </div>';
  html += '    </div>';

  // html += '    <input type="text" id="input-products-name-'+product_row+'" name="products['+product_row+'][name]" value="" data-rownum="'+product_row+'" class="form-control schProductName" data-oc-target="autocomplete-product_name-'+product_row+'" autocomplete="off">';
  // html += '    <ul id="autocomplete-product_name-'+product_row+'" class="dropdown-menu"></ul>';
  // html += '    <input type="hidden" id="input-products-id-'+product_row+'" name="products['+product_row+'][id]" value="" class="form-control" readonly>';
  html += '  </td>';
  html += '  <td class="text-left">';
  html += '    <input type="text" id="input-products-specification-'+product_row+'" name="products['+product_row+'][specification]" value="" class="form-control" readonly>';
  html += '  </td>';
  html += '  <td class="text-left">';
  html += '    <select id="input-products-receiving_unit_code-'+product_row+'" name="products['+product_row+'][receiving_unit_code]" class="form-control">';
  html += '      <option value=""> -- </option>';
  html += '    </select>';
  html += '  </td>';
  html += '  <td class="text-left">';
  html += '    <input type="text" id="input-products-receiving_quantity-'+product_row+'" name="products['+product_row+'][receiving_quantity]" value="" class="form-control productPriceInputs clcProduct" data-rownum="'+product_row+'">';
  html += '  </td>';
  html += '  <td class="text-left">';
  html += '    <input type="text" id="input-products-price-'+product_row+'" name="products['+product_row+'][price]" value="" class="form-control productPriceInputs clcProduct" data-rownum="'+product_row+'">';
  html += '  </td>';
  html += '  <td class="text-left">';
  html += '    <input type="text" id="input-products-amount-'+product_row+'" name="products['+product_row+'][amount]" value="" class="form-control productAmountInputs clcProduct" data-rownum="'+product_row+'" readonly>';
  html += '  </td>';
  html += '  <td class="text-left">';
  html += '    <input type="text" id="input-products-stock_unit_name-'+product_row+'" name="products['+product_row+'][stock_unit_name]" value="" class="form-control" readonly>';
  html += '    <input type="hidden" id="input-products-stock_unit_code-'+product_row+'" name="products['+product_row+'][stock_unit_code]" value="">';
  html += '  </td>';
  html += '  <td class="text-left">';
  html += '    <input type="text" id="input-products-stock_price-'+product_row+'" name="products['+product_row+'][stock_price]" value="" class="form-control" readonly>';
  html += '  </td>';
  html += '  <td class="text-left">';
  html += '    <input type="text" id="input-products-stock_quantity-'+product_row+'" name="products['+product_row+'][stock_quantity]" value="" class="form-control productReceivingQuantityInputs clcProduct" data-rownum="'+product_row+'">';
  html += '  </td>';
  html += '</tr>';


	$('#products tbody').append(html);

  $('.schProductName').first().click();

	product_row++;
}


</script>
@endsection

