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
                        <option value="{{ $location->id }}" @if($location->id == $location_id) selected @endif>{{ $location_name }}</option>
                        @endforeach
                      </select>
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
                        </div>
                        <div class="col-sm-3">
                          <input type="text" id="input-tax_rate" name="tax_rate" value="{{ $receiving_order->tax_rate }}" placeholder="{{ $lang->column_tax_rate }}" class="form-control" readonly/>
                        </div>
                        <div class="col-sm-1" style="font-size: 1.3rem;">%</div>

                      </div>

                    </div>
                  </div>

                  <div class="row mb-3">
                    <label for="hidden_before_tax" class="col-sm-2 col-form-label">{{ $lang->column_before_tax }}</label>
                    <div class="col-sm-10">
                      <input type="text" id="hidden_before_tax" value="{{ $receiving_order->before_tax }}" class="form-control" disabled/>
                    </div>
                  </div>

                  <div class="row mb-3">
                    <label for="hidden_tax" class="col-sm-2 col-form-label">{{ $lang->column_tax }}</label>
                    <div class="col-sm-10">
                      <input type="text" id="hidden_tax" value="{{ $receiving_order->tax }}" class="form-control" disabled/>
                    </div>
                  </div>

                  <div class="row mb-3">
                    <label for="hidden_total" class="col-sm-2 col-form-label">{{ $lang->column_total }}</label>
                    <div class="col-sm-10">
                      <input type="text" id="hidden_total" value="{{ $receiving_order->total }}" class="form-control" disabled/>
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

              <table class="table table-bordered">
                <tbody id="order-totals">
                  <tr>
                    <td class="text-end col-sm-2"><strong>{{ $lang->column_before_tax }}</strong></td>
                    <td class="text-end">
                      <input type="text" id="input-before_tax" name="before_tax" value="{{ $receiving_order->before_tax }}" class="form-control">
                    </td>
                  </tr>
                  <tr>
                    <td class="text-end col-sm-2"><strong>{{ $lang->column_tax }}</strong></td>
                    <td class="text-end">
                      <input type="text" id="input-tax" name="tax" value="{{ $receiving_order->tax }}" class="form-control">
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
              <div id="tab-products" class="tab-pane">
                <style>
                    #tab-products .row1 {
                      border: 1px solid #ccc;
                      padding: 2px;
                      margin-bottom: 2px;
                    }
                </style>
                <div class="row row1" >
                  @php
                    $product_row = 1;
                  @endphp
                  
                  @for($i=0; $i<10; $i++)
                    @php $receiving_product = $receiving_products[$i] ?? []; @endphp
                  <div class="row" data-rownum="{{ $product_row }}">
                    <div class="module col-md-1 col-sm-1">
                        <label class="col-form-label">料件流水號</label>
                        <input type="text" id="input-products-id-{{ $product_row }}" name="products[{{ $product_row }}][id]" value="{{ $receiving_product->product_id ?? '' }}" class="form-control" readonly>
                        
                    </div>
                    <div class="module col-md-2 col-sm-2 required">
                      <div>
                        <label class="col-form-label">料件名稱</label>
                        <input type="text" id="input-products-name-{{ $product_row }}" name="products[{{ $product_row }}][name]" value="{{ $receiving_product->product_name ?? '' }}" data-rownum="{{ $product_row }}" class="form-control schProductName" data-oc-target="autocomplete-product_name-{{ $product_row }}" autocomplete="off">
                        
                      </div>
                      <ul id="autocomplete-product_name-{{ $product_row }}" class="dropdown-menu"></ul>
                    </div>
                      
                    <div class="module col-md-2 col-sm-2">
                        <label class="col-form-label">料件規格</label>
                        <input type="text" id="input-products-specification-{{ $product_row }}" name="products[{{ $product_row }}][specification]" value="{{ $receiving_product->product_specification ?? '' }}" class="form-control" readonly>
                    </div>
                    <div class="module col-md-1 col-sm-1 required">
                        <label class="col-form-label">進貨單位</label>
                        <select id="input-products-receiving_unit_code-{{ $product_row }}" name="products[{{ $product_row }}][receiving_unit_code]" class="form-control">
                          <option value=""> -- </option>
                          <option value="{{ $receiving_product->receiving_unit_code ?? '' }}_{{ $receiving_product->receiving_unit_name ?? '' }}" selected>{{ $receiving_product->receiving_unit_name ?? '' }}</option>
                        </select>
                    </div>
                    <div class="module col-md-1 col-sm-1 required">
                        <label class="col-form-label">進貨單價</label>
                        <input type="text" id="input-products-price-{{ $product_row }}" name="products[{{ $product_row }}][price]" value="{{ $receiving_product->price ?? 0 }}" class="form-control productPriceInputs clcProduct" data-rownum="{{ $product_row }}">
                    </div>
                    <div class="module col-md-1 col-sm-1 required">
                        <label class="col-form-label">進貨數量</label>
                        <input type="text" id="input-products-receiving_quantity-{{ $product_row }}" name="products[{{ $product_row }}][receiving_quantity]" value="{{ $receiving_product->receiving_quantity ?? 0 }}" class="form-control productReceivingQuantityInputs clcProduct" data-rownum="{{ $product_row }}">
                    </div>
                    <div class="module col-md-1 col-sm-1">
                        <label class="col-form-label">進貨金額</label>
                        <input type="text" id="input-products-amount-{{ $product_row }}" name="products[{{ $product_row }}][amount]" value="{{ $receiving_product->amount ?? 0 }}" class="form-control productAmountInputs clcProduct" data-rownum="{{ $product_row }}" readonly>
                    </div>
                    <div class="module col-md-1 col-sm-1">
                        <label class="col-form-label">庫存數量</label>
                        <input type="text" id="input-products-stock_quantity-{{ $product_row }}" name="products[{{ $product_row }}][stock_quantity]" value="{{ $receiving_product->stock_quantity ?? '' }}" class="form-control" data-rownum="{{ $product_row }}" readonly>
                    </div>
                    <div class="module col-md-1 col-sm-1">
                        <label class="col-form-label">庫存單位</label>
                        <input type="text" id="input-products-stock_unit_name-{{ $product_row }}" name="products[{{ $product_row }}][stock_unit_name]" value="{{ $receiving_product->stock_unit_name ?? '' }}" class="form-control" readonly>
                        <input type="hidden" id="input-products-stock_unit_code-{{ $product_row }}" name="products[{{ $product_row }}][stock_unit_code]" value="{{ $receiving_product->stock_unit_code ?? '' }}">
                    </div>
                    <div class="module col-md-1 col-sm-1">
                        <label class="col-form-label">庫存單價</label>
                        <input type="text" id="input-products-stock_unit_price-{{ $product_row }}" name="products[{{ $product_row }}][stock_unit_price]" value="{{ $receiving_product->stock_unit_price ?? 0 }}" class="form-control" readonly>
                    </div>
                  </div>

                  @php $product_row++; @endphp
                  @endfor
                </div>
              </div>
            </form>
            </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('buttom')
<script type="text/javascript">

// 查廠商名稱
$('#input-supplier_name').on('click', function(e){
  e.preventDefault();

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
});

// 查料件名稱
$('.schProductName').autocomplete({
  'source': function (request, response) {
    let supplier_id = $('#input-supplier_id').val();
    let supplier_url = '';

    if($.isNumeric(supplier_id) && supplier_id > 0){
      supplier_url = '&equal_supplier_id=' + supplier_id + '&limit=0&pagination=0';
    }
    $.ajax({
        url: "{{ $product_autocomplete_url }}?equal_is_management=1&equal_is_active=1&with=product_units&filter_name=" + encodeURIComponent(request) + '&extra_columns=stock_unit_code,stock_unit_name' + supplier_url,
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

    var selectElement = $('#input-products-receiving_unit_code-'+rownum);
    selectElement.empty();

    $.each(item.product_units, function(index, unit) {
      // 创建一个option元素
      var option = $('<option></option>');

      // 设置option的值和文本
      option.val(unit.source_unit_code); // 假设id是选项的值
      option.text(unit.source_unit_name); // 假设name是选项的显示文本
      option.attr('data-multiplier', unit.destination_quantity);
      //console.log('unit.source_unit_code='+unit.source_unit_code+', unit.source_unit_name='+unit.source_unit_name+', unit.destination_quantity='+unit.destination_quantity)

      // 将option添加到select元素中
      selectElement.append(option);
    });
  }
});

// 課稅別
$('#input-tax_type_code').on("change", function() {
  //$('#input-tax_type_code').val(tax_type_code); //不允許手動變更，回復原值
  chgTaxRate()
});
// 變更稅率
function chgTaxRate(){
  let tax_type_code = $('#input-tax_type_code').val(); 
  if(tax_type_code == 1){
    $('#input-tax_rate').val(5);
    tax_rate = 5;
  }else if(tax_type_code == 2){
    $('#input-tax_rate').val(5);
    tax_rate = 5;
  }else if(tax_type_code == 3){
    $('#input-tax_rate').val(0);
    tax_rate = 0;
  }else if(tax_type_code == 4){
    $('#input-tax_rate').val(0);
    tax_rate = 0;
  }
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

// 採購金額變動函數
const $productPriceInputs = $('.productPriceInputs'); //單價
const $productReceivingQuantityInputs = $('.productReceivingQuantityInputs'); //數量
const $productAmountInputs = $('.productAmountInputs'); // 金額
const $before_tax = $('#input-before_tax');
const maxProductRow = 20;

// 進貨單價、進貨數量、進貨金額 觸發計算
$('.clcProduct').on('focusout', function(){
  let rownum = $(this).closest('[data-rownum]').data('rownum');
  calcProduct(rownum)
});
function calcProduct(rownum){
  let price = $('#input-products-price-'+rownum).val() ?? 0;
  let receiving_quantity = $('#input-products-receiving_quantity-'+rownum).val() ?? 0;
  let amount = $('#input-products-amount-'+rownum).val() ?? 0;
  let destination_quantity = 0;
  let multiplier = $('#input-products-receiving_unit_code-'+rownum + ' option:selected').data('multiplier');

  amount = price*receiving_quantity
  $('#input-products-amount-'+rownum).val(amount);

  
  if ($.isNumeric(receiving_quantity) && $.isNumeric(multiplier)) {
    destination_quantity = receiving_quantity * multiplier
  }

  let stock_unit_price = 0;
  if ($.isNumeric(receiving_quantity) && destination_quantity > 0) {
    stock_unit_price = amount / destination_quantity;
  }
  //console.log('amount='+amount+', receiving_quantity='+receiving_quantity+', destination_quantity='+destination_quantity+', multiplier='+multiplier);

  // 庫存數量
  $('#input-products-stock_quantity-'+rownum).val(destination_quantity);

  // 庫存單價 = 進貨金額/庫存數量
  $('#input-products-stock_unit_price-'+rownum).val(stock_unit_price);
  
  calcAllProducts()
}
  

function calcAllProducts(){
  let sum_amount = 0; // 單身金額加總
  let total = 0; // 單頭稅後總金額

  $productAmountInputs.each(function() {
    sum_amount += parseFloat($(this).val()) || 0;
  });

  var tax_type_code = $('#input-tax_type_code').val();
  var tax_rate_pcnt = $('#input-tax_rate').val()/100;
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
  console.log('sum_amount='+sum_amount+', before_tax='+before_tax+', tax='+tax+', total='+total)

  $('#input-before_tax').val(before_tax);
  $('#input-total').val(sum_amount);
  $('#input-tax').val(tax);
}

$('#input-tax').on('focusout', function(){
  let num = $(this).val();
  $('#hidden_tax').val(num);
});
$('#input-total').on('focusout', function(){
  let num = $(this).val();
  $('#hidden_total').val(num);
});




</script>
@endsection

