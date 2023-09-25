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
        <div class="card-header"><i class="fas fa-pencil-alt"></i> {{ $lang->text_form }}</div>
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
                    <label for="input-purchasing_date" class="col-sm-2 col-form-label">{{ $lang->column_purchasing_date }}</label>
                    <div class="col-sm-10">
                      <input type="text" name="purchasing_date" value="{{ $receiving_order->purchasing_date_ymd }}" id="input-purchasing_date" class="form-control date"/>
                      <div id="error-purchasing_date" class="invalid-feedback"></div>
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
                    <label for="input-before_tax" class="col-sm-2 col-form-label">{{ $lang->column_before_tax }}</label>
                    <div class="col-sm-10">
                      <input type="text" name="before_tax" value="{{ $receiving_order->before_tax }}" id="input-before_tax" class="form-control"/>
                      <div id="error-before_tax" class="invalid-feedback"></div>
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
      border: 1px solid #ccc; /* 添加边框样式 */
      padding: 2px; /* 添加一些内边距以改善外观 */
      margin-bottom: 2px; /* 添加一些底部外边距以分隔模块 */
    }
</style>
    <div class="row row1">
      @php
        $product_row = 1;
      @endphp
      
      @for($i=0; $i<10; $i++)
        @php $receiving_product = $receiving_products[$i] ?? []; @endphp
      <div class="row">
        <div class="module col-md-1 col-sm-1">
            <label>料件流水號</label>
            <input type="text" id="input-row-{{ $product_row }}-product_id" name="products[{{ $product_row }}][product_id]" value="{{ $receiving_product->product_id ?? '' }}" class="form-control" >
        </div>
        <div class="module col-md-2 col-sm-2">
            <label>料件名稱</label>
            <input type="text" id="input-row-{{ $product_row }}-product_name" name="products[{{ $product_row }}][product_name]" value="{{ $receiving_product->product_name ?? '' }}" class="form-control">
        </div>
        <div class="module col-md-2 col-sm-2">
            <label>料件規格</label>
            <input type="text" id="input-row-{{ $product_row }}-product_specification" name="products[{{ $product_row }}][product_specification]" value="{{ $receiving_product->product_specification ?? '' }}" class="form-control">
        </div>
        <div class="module col-md-1 col-sm-1">
            <label>採購數量</label>
            <input type="text" id="input-row-{{ $product_row }}-receiving_quantity" name="products[{{ $product_row }}][receiving_quantity]" value="{{ $receiving_product->receiving_quantity ?? '' }}" class="form-control">
        </div>
        <div class="module col-md-1 col-sm-1">
            <label>採購單位</label>
            <input type="text" id="input-row-{{ $product_row }}-receiving_unit_name" name="products[{{ $product_row }}][receiving_unit_name]" value="{{ $receiving_product->receiving_unit_name ?? '' }}" class="form-control">
            <input type="hidden" id="input-row-{{ $product_row }}-receiving_unit_code" name="products[{{ $product_row }}][receiving_unit_code]" value="{{ $receiving_product->receiving_unit_code ?? '' }}">
        </div>
        <div class="module col-md-1 col-sm-1">
            <label>盤點數量</label>
            <input type="text" id="input-row-{{ $product_row }}-stock_quantity" name="products[{{ $product_row }}][stock_quantity]" value="{{ $receiving_product->stock_quantity ?? '' }}" class="form-control">
        </div>
        <div class="module col-md-1 col-sm-1">
            <label>盤點單位</label>
            <input type="text" id="input-row-{{ $product_row }}-stock_unit_code" name="products[{{ $product_row }}][stock_unit_code]" value="{{ $receiving_product->stock_unit_code ?? '' }}" class="form-control" readonly>
        </div>
        <div class="module col-md-1 col-sm-1">
            <label>採購單價</label>
            <input type="text" id="input-row-{{ $product_row }}-price" name="products[{{ $product_row }}][price]" value="{{ $receiving_product->price ?? '' }}" class="form-control">
        </div>
        <div class="module col-md-1 col-sm-1">
            <label>採購金額</label>
            <input type="text" id="input-row-{{ $product_row }}-total" name="products[{{ $product_row }}][total]" value="{{ $receiving_product->total ?? '' }}" class="form-control">
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
</script>
@endsection

