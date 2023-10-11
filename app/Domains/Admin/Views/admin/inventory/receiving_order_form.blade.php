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
                  <legend>{{ $lang->tab_data }}</legend>
                  <div class="row mb-3">
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

                  <div class="row mb-3">
                    <label for="input-form_type" class="col-sm-2 col-form-label">單別</label>
                    <div class="col-sm-10">
                      <select id="input-form_type" name="form_type" class="form-select">
                        <option value="">--</option>
                        <option value="">原物料</option>
                        <option value="">費用</option>
                      </select>
                    </div>
                  </div>

                  <div class="row mb-3">
                    <label for="input-receiving_date" class="col-sm-2 col-form-label">{{ $lang->column_receiving_date }}</label>
                    <div class="col-sm-10">
                      <input type="text" name="receiving_date" value="{{ $receiving_order->receiving_date_ymd }}" id="input-receiving_date" class="form-control date"/>
                      <div id="error-receiving_date" class="invalid-feedback"></div>
                    </div>
                  </div>

                  <div class="row mb-3">
                    <label for="input-supplier" class="col-sm-2 col-form-label">{{ $lang->column_supplier }}</label>
                    <div class="col-sm-10">
                      <div class="input-group">
                        <div class="col-sm-3"><input type="text" id="input-supplier_id" name="supplier_id" value="{{ $receiving_order->supplier_id ?? 0 }}" placeholder="廠商流水號" class="form-control" readonly=""/><div class="form-text">廠商流水號</div></div>
                        <div class="col-sm-6"><input type="text" id="input-supplier_name" name="supplier_name" value="{{ $receiving_order->supplier_name }}" placeholder="{{ $lang->column_supplier_name }}" class="form-control" data-oc-target="autocomplete-supplier_name"/><div class="form-text">廠商名稱 (可查詢，至少輸入一個字)</div></div>
                        <div class="col-sm-3"><input type="text" id="input-tax_id_num" name="tax_id_num" value="{{ $receiving_order->tax_id_num }}" placeholder="{{ $lang->column_tax_id_num }}" class="form-control" data-oc-target="autocomplete-tax_id_num"/><div class="form-text">統一編號(可查詢現有廠商，請輸入8碼)</div></div>
                        <div id="error-supplier_id" class="invalid-feedback"></div>
                        <ul id="autocomplete-supplier_name" class="dropdown-menu"></ul>
                        <ul id="autocomplete-tax_id_num" class="dropdown-menu"></ul>
                      </div>
                    </div>
                  </div>

                  <div class="row mb-3">
                    <label for="input-tax_type_code" class="col-sm-2 col-form-label">{{ $lang->column_tax_type }}</label>
                    <div class="col-sm-10">
                      <select id="input-tax_type_code" name="tax_type_code" class="form-select">
                        <option value="">--</option>
                        @foreach($tax_types as $code => $tax_type)
                        <option value="{{ $tax_type->code }}" @if($tax_type->code == $receiving_order->tax_type_code) selected @endif>{{ $tax_type->name }}</option>
                        @endforeach
                      </select>
                    </div>
                  </div>

                  <div class="row mb-3">
                    <label for="hidden_amount" class="col-sm-2 col-form-label">{{ $lang->column_amount }}</label>
                    <div class="col-sm-10">
                      <input type="text" id="hidden_amount" value="{{ $receiving_order->amount }}" class="form-control" disabled/>
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

                </fieldset>

              </div>

              <div id="tab-products" class="tab-pane">
<style>
    #tab-products .row1 {
      border: 1px solid #ccc;
      padding: 2px;
      margin-bottom: 2px;
    }
</style>
    <div class="row row1 overflow-auto" style="height:300px">
      @php
        $product_row = 1;
      @endphp
      
      @for($i=0; $i<10; $i++)
        @php $receiving_product = $receiving_products[$i] ?? []; @endphp
      <div class="row">
        <div class="module col-md-1 col-sm-1">
            <label>料件流水號</label>
            <input type="text" id="input-products-id-{{ $product_row }}" name="products[{{ $product_row }}][id]" value="{{ $receiving_product->product_id ?? '' }}" class="form-control" >
            
        </div>
        <div class="module col-md-2 col-sm-2">
          <div>
            <label>料件名稱</label>
            <input type="text" id="input-products-name-{{ $product_row }}" name="products[{{ $product_row }}][name]" value="{{ $receiving_product->product_name ?? '' }}" data-rownum="{{ $product_row }}" class="form-control schProductName" data-oc-target="autocomplete-product_name-{{ $product_row }}" autocomplete="off">
            <ul id="autocomplete-product_name-{{ $product_row }}" class="dropdown-menu"></ul>
          </div>
        </div>
          
        <div class="module col-md-2 col-sm-2">
            <label>料件規格</label>
            <input type="text" id="input-products-specification-{{ $product_row }}" name="products[{{ $product_row }}][specification]" value="{{ $receiving_product->product_specification ?? '' }}" class="form-control">
        </div>
        <div class="module col-md-1 col-sm-1">
            <label>採購數量</label>
            <input type="text" id="input-products-receiving_quantity-{{ $product_row }}" name="products[{{ $product_row }}][receiving_quantity]" value="{{ $receiving_product->receiving_quantity ?? '' }}" class="form-control" data-rownum="{{ $product_row }}" onfocusout="chkPurchasingQuantity(this)">
        </div>
        <div class="module col-md-1 col-sm-1">
            <label>採購單位</label>
            <select id="input-products-receiving_unit_code-{{ $product_row }}" name="products[{{ $product_row }}][receiving_unit_code]" class="form-control">
              <option value=""> -- </option>
              <option value="{{ $receiving_product->receiving_unit_code ?? '' }}_{{ $receiving_product->receiving_unit_name ?? '' }}" selected>{{ $receiving_product->receiving_unit_name ?? '' }}</option>
            </select>
        </div>
        <div class="module col-md-1 col-sm-1">
            <label>盤點數量</label>
            <input type="text" id="input-products-stock_quantity-{{ $product_row }}" name="products[{{ $product_row }}][stock_quantity]" value="{{ $receiving_product->stock_quantity ?? '' }}" class="form-control" data-rownum="{{ $product_row }}">
        </div>
        <div class="module col-md-1 col-sm-1">
            <label>盤點單位</label>
            <input type="text" id="input-products-stock_unit_name-{{ $product_row }}" name="products[{{ $product_row }}][stock_unit_name]" value="{{ $receiving_product->stock_unit_name ?? '' }}" class="form-control" readonly>
            <input type="hidden" id="input-products-stock_unit_code-{{ $product_row }}" name="products[{{ $product_row }}][stock_unit_code]" value="{{ $receiving_product->stock_unit_code ?? '' }}">
        </div>
        <div class="module col-md-1 col-sm-1">
            <label>採購單價</label>
            <input type="text" id="input-products-price-{{ $product_row }}" name="products[{{ $product_row }}][price]" value="{{ $receiving_product->price ?? '' }}" class="form-control">
        </div>
        <div class="module col-md-1 col-sm-1">
            <label>盤點單價</label>
            <input type="text" id="input-products-stock_price-{{ $product_row }}" name="products[{{ $product_row }}][stock_price]" value="{{ $receiving_product->stock_price ?? '' }}" class="form-control">
        </div>
        <div class="module col-md-1 col-sm-1">
            <label>採購金額</label>
            <input type="text" id="input-products-total-{{ $product_row }}" name="products[{{ $product_row }}][total]" value="{{ $receiving_product->total ?? '' }}" class="form-control" data-rownum="{{ $product_row }}" onfocusout="chkPrice(this)">
        </div>
      </div>

      @php $product_row++; @endphp
      @endfor
    </div>
              <table class="table table-bordered">
                <tbody id="order-totals">
                  <tr>
                    <td class="text-end col-sm-2"><strong>{{ $lang->column_amount }}</strong></td>
                    <td class="text-end">
                      <input type="text" id="input-total-xxx" name="order_totals[xxx][value]" value="222" class="form-control" onchange="calcTotal()">
                    </td>
                  </tr>
                  <tr>
                    <td class="text-end col-sm-2"><strong>{{ $lang->column_tax }}</strong></td>
                    <td class="text-end">
                      <input type="text" id="input-total-xxx" name="order_totals[xxx][value]" value="222" class="form-control" onchange="calcTotal()">
                    </td>
                  </tr>
                  <tr>
                    <td class="text-end col-sm-2"><strong>{{ $lang->column_total }}</strong></td>
                    <td class="text-end">
                      <input type="text" id="input-total-xxx" name="order_totals[xxx][value]" value="222" class="form-control" onchange="calcTotal()">
                    </td>
                  </tr>
                </tbody>
              </table>
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
$(document).ready(function() {
  $('#input-supplier_name').on('input', function(){
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
      }
    });
  });
});

// 查統一編號
$(document).ready(function() {
  $('#input-tax_id_num').on('input', function(){
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
});

var productData = [];

// 查料件名稱
$(document).ready(function() {
  $('.schProductName').on('input', function(){
    $(this).autocomplete({
      'source': function (request, response) {
        $.ajax({
            url: "{{ $product_autocomplete_url }}?filter_name=" + encodeURIComponent(request),
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

        $('#input-products-receiving_unit_code-'+rownum).empty();

        console.log(JSON.stringify(item.purchasing_units));

        item.purchasing_units.forEach(function(unitData) {
          let option = $('<option></option>');
          option.val(unitData.source_unit_code + '_' + unitData.source_unit_name);
          option.text(unitData.source_unit_name);
          option.attr('data-multiplier', unitData.destination_quantity);
          $('#input-products-receiving_unit_code-'+rownum).append(option);
        });

        // item.product_units.forEach(function(unitData) {
        //   alert(unitData.source_unit_name)
        // });


        // for (var i = 0; i < item.product_units.length; i++) {
        //   let unitData = item.product_units[i];
        //   let option = $('<option></option>');
        //   option.val(unitData.source_unit_code + '_' + unitData.source_unit_name);
        //   option.text(unitData.source_unit_code + ' ' + unitData.source_unit_name);
        //   option.attr('data-multiplier', unitData.destination_quantity);
        //   $('#input-products-receiving_unit_code-'+rownum).append(option);
        // }
      }
    });
  });


  
});

function chkPurchasingQuantity(inputElement){
  var rownum = $(inputElement).data('rownum');
  var multiplier = $('#input-products-receiving_unit_code-'+rownum + ' option:selected').data('multiplier');
  var quantity = $(inputElement).val();
  var destination_quantity = quantity * multiplier
  console.log('rownum='+rownum+', multiplier='+multiplier + ', destination_quantity='+destination_quantity);

  $('#input-products-stock_quantity-'+rownum).val(destination_quantity);
}

function chkPrice(inputElement){
  var rownum = $(inputElement).data('rownum');
  var total = $(inputElement).val();
  var receiving_quantity = $('#input-products-receiving_quantity-'+rownum).val();
  var stock_quantity = $('#input-products-stock_quantity-'+rownum).val();
  console.log('rownum='+rownum+', total='+total + ', receiving_quantity='+receiving_quantity + ', stock_quantity='+stock_quantity);
  var price = parseFloat(total/receiving_quantity).toFixed(2);
  var stock_price = parseFloat(total/stock_quantity).toFixed(2);
  console.log('price='+price+', stock_price='+stock_price);

  if(!isNaN(price)){
    $('#input-products-price-'+rownum).val(price);
  }
  if(!isNaN(stock_price)){
  $('#input-products-stock_price-'+rownum).val(stock_price);
  }
}

function calcTotal(){
  var ro_amount = 0; //採購金額
  var ro_total = 0; //金額總計
  var ro_products_total = 0;
  var ro_sub_total = 0;
}
</script>
@endsection

