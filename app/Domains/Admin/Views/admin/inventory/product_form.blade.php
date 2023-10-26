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
        <button type="submit" form="form-product" data-bs-toggle="tooltip" title="儲存" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="{{ $back_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_back }}" class="btn btn-light"><i class="fas fa-reply"></i></a>
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
          <li class="nav-item"><a href="#tab-trans" data-bs-toggle="tab" class="nav-link active">{{ $lang->tab_trans }}</a></li>
          <li class="nav-item"><a href="#tab-data" data-bs-toggle="tab" class="nav-link">{{ $lang->tab_data }}</a></li>
          <li class="nav-item"><a href="#tab-units" data-bs-toggle="tab" class="nav-link">{{ $lang->tab_units }}</a></li>
        </ul>
        <form id="form-product" action="{{ $save_url }}" method="post" data-oc-toggle="ajax">
          @csrf
          @method('POST')

          <div class="tab-content">
            <div id="tab-trans" class="tab-pane active" >
              <ul class="nav nav-tabs">
                @foreach($languages as $language)
                <li class="nav-item"><a href="#language-{{ $language->code }}" data-bs-toggle="tab" class="nav-link @if ($loop->first)active @endif">{{ $language->native_name }}</a></li>
                @endforeach
              </ul>
              <div class="tab-content">
                @foreach($languages as $language)
                <div id="language-{{ $language->code }}" class="tab-pane @if ($loop->first)active @endif">
                  <input type="hidden" name="translations[{{ $language->code }}][id]" value="{{ $product_translations[$language->code]['id'] ?? '' }}" >

                  <div class="row mb-3 required">
                    <label for="input-name-{{ $language->code }}" class="col-sm-2 col-form-label">{{ $lang->column_name }}</label>
                    <div class="col-sm-10">
                      <div class="input-group">
                        <input type="text" name="translations[{{ $language->code }}][name]" value="{{ $product_translations[$language->code]['name'] ?? ''  }}" placeholder="{{ $lang->column_name }}" id="input-name-{{ $language->code }}" class="form-control">
                                                </div>
                      <div id="error-name-{{ $language->code }}" class="invalid-feedback"></div>
                    </div>
                  </div>

                  <div class="row mb-3 ">
                    <label for="input-specification-{{ $language->code }}" class="col-sm-2 col-form-label">{{ $lang->column_specification }}</label>
                    <div class="col-sm-10">
                      <div class="input-group">
                        <input type="text" name="translations[{{ $language->code }}][specification]" value="{{ $product_translations[$language->code]['specification'] ?? ''  }}" id="input-specification-{{ $language->code }}" class="form-control">
                                                </div>
                      <div id="error-specification-{{ $language->code }}" class="invalid-feedback"></div>
                    </div>
                  </div>

                </div>
                @endforeach
              </div>
            </div>

            <div id="tab-data" class="tab-pane">

              <div class="row mb-3">
                <label for="input-code" class="col-sm-2 col-form-label">{{ $lang->column_code }}</label>
                <div class="col-sm-10">
                  <input type="text" name="code" value="{{ $product->code }}" id="input-code" class="form-control"/>
                  <div id="error-code" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-supplier_name" class="col-sm-2 col-form-label">{{ $lang->column_supplier }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-supplier_name" name="supplier_name" value="{{ $product->supplier_name }}" class="form-control" data-oc-target="autocomplete-supplier_name" autocomplete="off"/>
                  <input type="hidden" id="input-supplier_id" name="supplier_id" value="{{ $product->supplier_id }}">
                  <ul id="autocomplete-supplier_name" class="dropdown-menu"></ul>
                  <div id="error-supplier_name " class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-supplier_own_product_code" class="col-sm-2 col-form-label">{{ $lang->column_supplier_own_product_code }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-supplier_own_product_code" name="supplier_own_product_code" value="{{ $product->supplier_own_product_code }}" class="form-control"/>
                  <div id="error-supplier_own_product_code" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-supplier_own_product_name" class="col-sm-2 col-form-label">{{ $lang->column_supplier_own_product_name }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-supplier_own_product_name" name="supplier_own_product_name" value="{{ $product->supplier_own_product_name }}" class="form-control"/>
                  <div id="error-supplier_own_product_name" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-supplier_own_product_specification" class="col-sm-2 col-form-label">{{ $lang->column_supplier_own_product_specification }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-supplier_own_product_specification" name="supplier_own_product_specification" value="{{ $product->supplier_own_product_specification }}" class="form-control"/>
                  <div id="error-supplier_own_product_specification" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-source_type_code" class="col-sm-2 col-form-label">{{ $lang->column_source_type_code }}</label>
                <div class="col-sm-10">
                  <select id="input-source_type_code" name="source_type_code" class="form-control">
                    <option value="">{{ $lang->text_select }}</option>
                    @foreach($source_type_codes as $source_type_code)
                    <option value="{{ $source_type_code->code }}" @if($product->source_type_code==$source_type_code->code) selected @endif>{{ $source_type_code->name }}</option>
                    @endforeach
                  </select>
                  <div id="error-source_type_code" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-accounting_category_code" class="col-sm-2 col-form-label">{{ $lang->column_accounting_category }}</label>
                <div class="col-sm-10">
                  <select id="input-accounting_category_code" name="accounting_category_code" class="form-control">
                    <option value="">{{ $lang->text_select }}</option>
                    @foreach($accounting_categories as $accounting_category)
                    <option value="{{ $accounting_category->code }}" @if($accounting_category->code==$product->accounting_category_code) selected @endif>{{ $accounting_category->name }}</option>
                    @endforeach
                  </select>
                  <div id="error-accounting_category_code" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-stock_unit_code" class="col-sm-2 col-form-label">{{ $lang->column_stock_unit }}</label>
                <div class="col-sm-10">
                  @php
                    $disabled = !empty($product->stock_unit_code) ? ' disabled' : '';
                  @endphp
                  <select id="input-stock_unit_code" name="stock_unit_code" class="form-control">
                    <option value="">--</option>
                    @foreach($units as $code => $unit)
                    <option value="{{ $unit->code }}" @if($unit->code==$product->stock_unit_code) selected @endif>{{ $unit->label }}</option>
                    @endforeach
                  </select>
                  <div id="error-stock_unit_code" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-usage_unit_code" class="col-sm-2 col-form-label">{{ $lang->column_usage_unit_code }}</label>
                <div class="col-sm-10">
                  <select id="input-usage_unit_code" name="usage_unit_code" class="form-control">
                    <option value="">--</option>
                    @foreach($units as $code => $unit)
                    <option value="{{ $unit->code }}" @if($unit->code==$product->usage_unit_code) selected @endif>{{ $unit->label }}</option>
                    @endforeach
                  </select>
                  <div id="error-usage_unit_code" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-stock_unit_price" class="col-sm-2 col-form-label">{{ $lang->column_stock_unit_price }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-stock_unit_price" name="stock_unit_price" value="{{ $product->stock_unit_price }}" class="form-control"/>
                  <div id="error-stock_unit_price" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label class="col-sm-2 col-form-label">{{ $lang->column_enable }}</label>
                <div class="col-sm-10">
                  <div class="input-group">
                    <div id="input-is_active" class="form-check form-switch form-switch-lg">
                      <input type="hidden" name="is_active" value="0"/>
                      <input type="checkbox" name="is_active" value="1" class="form-check-input" @if($product->is_active) checked @endif/>
                    </div>
                  </div>
                </div>
              </div>

              <div class="row mb-3">
                <label class="col-sm-2 col-form-label">{{ $lang->column_is_stock_management }}</label>
                <div class="col-sm-10">
                  <div class="input-group">
                    <div id="input-is_stock_management" class="form-check form-switch form-switch-lg">
                      <input type="hidden" name="is_stock_management" value="0"/>
                      <input type="checkbox" name="is_stock_management" value="1" class="form-check-input" @if($product->is_stock_management) checked @endif/>
                    </div>
                  </div>
                </div>
              </div>

              <div class="row mb-3">
                <label class="col-sm-2 col-form-label">{{ $lang->column_is_salable }}</label>
                <div class="col-sm-10">
                  <div class="input-group">
                    <div id="input-is_salable" class="form-check form-switch form-switch-lg">
                      <input type="hidden" name="is_salable" value="0"/>
                      <input type="checkbox" name="is_salable" value="1" class="form-check-input" @if($product->is_salable) checked @endif/>
                    </div>
                  </div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-price" class="col-sm-2 col-form-label">{{ $lang->column_price}}</label>
                <div class="col-sm-10">
                  <input type="text" name="price" value="{{ $product->price }}" placeholder="{{ $lang->column_price }}" id="input-price" class="form-control"/>
                  <div id="error-price" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-purchasing_price" class="col-sm-2 col-form-label">{{ $lang->column_purchasing_price }}</label>
                <div class="col-sm-10">
                  <input type="text" name="purchasing_price" value="{{ $product->purchasing_price ?? 0}}" id="input-purchasing_price" class="form-control"/>
                  <div id="error-purchasing_price" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-quantity" class="col-sm-2 col-form-label">{{ $lang->column_quantity}}</label>
                <div class="col-sm-10">
                  <input type="text" name="quantity" value="{{ $product->quantity }}" placeholder="{{ $lang->column_quantity }}" id="input-quantity" class="form-control"/>
                  <div id="error-quantity" class="invalid-feedback"></div>
                </div>
              </div>
            </div>

            <div id="tab-units" class="tab-pane">
              <div class="table-responsive">

                <input type="hidden" id="input-available_unit_codes" name="available_unit_codes" value="{{ $product->available_unit_codes ?? ''}}" >

                @if(!empty($product->stock_unit_code))
                <table class="table table-bordered table-hover">
                  <tr>
                    <td class="text-start">來源數量</td>
                    <td class="text-start">來源單位</td>
                    <td class="text-start">目的數量</td>
                    <td class="text-start">目的單位(庫存單位)</td>
                  </tr>

                    @php $product_unit_row = 1; @endphp
                  @foreach($product_units as $product_unit)

                    <input type="hidden" name="product_units[{{ $product_unit_row }}][id]" value="{{ $product_unit->id ?? '' }}" >
                  <tr>
                    <td><input type="text" id="input-units-{{ $product_unit_row }}-source_quantity" name="product_units[{{ $product_unit_row }}][source_quantity]" value="{{ $product_unit->source_quantity ?? 0 }}" class="form-control"></td>
                    <td>
                      <select id="input-units-{{ $product_unit_row }}-source_unit_code" name="product_units[{{ $product_unit_row }}][source_unit_code]" class="form-control source_unit_code">
                        <option value="">--</option>
                        @foreach($units as $code => $unit)
                        <option value="{{ $unit->code }}" @if($unit->code==$product_unit->source_unit_code) selected @endif>{{ $unit->label }}</option>
                        @endforeach
                      </select>
                    </td>
                    <td><input type="text" id="input-units-{{ $product_unit_row }}-stock_quantity" name="product_units[{{ $product_unit_row }}][destination_quantity]" value="{{ $product_unit->destination_quantity ?? 0 }}" class="form-control"></td>
                    <td>
                      <select id="input-units-{{ $product_unit_row }}-destination_unit_code" name="product_units[{{ $product_unit_row }}][destination_unit_code]" class="form-control">
                        <option value="{{ $product->stock_unit_code }}">{{ $product->stock_unit_name }}</option>
                      </select>
                    </td>
                  </tr>
                    @php $product_unit_row++; @endphp
                  @endforeach

                </table>
                @else
                請先設定庫存單位(盤點單位)，然後按F5重新整理. 
                @endif
              </div>
            </div>

            <input type="hidden" name="product_id" value="{{ $product_id }}" id="input-product_id"/>
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
// 查主供應商
$('#input-supplier_name').autocomplete({
  'source': function (request, response) {
    var regex = /[a-zA-Z0-9\u3105-\u3129]+/; // 注音符號
    if (regex.test(request)) {
      return;
    }else{
      $.ajax({
        url: "{{ $supplier_autocomplete_url }}?filter_name=" + encodeURIComponent(request),
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
  }
});

// 查料件名稱
$('.searchProductName').autocomplete({
  'source': function (request, response) {
    $.ajax({
        url: "{{ $product_autocomplete_url }}?equal_source_type_code=PUR&equal_is_active=1&filter_name=" + encodeURIComponent(request),
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

    //console.log(JSON.stringify(item.purchasing_units));

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

// 使用到的單位
$('.source_unit_code').on('focusout change', function() {
  var valuesArray = [];

  $('.source_unit_code').each(function() {
    var value = $(this).val(); // 获取当前元素的值

    if(value != ''){
      valuesArray.push(value); // 将值添加到数组
    }
  });
  
  var jsonArray = JSON.stringify(valuesArray);
  $('#input-available_unit_codes').val(jsonArray);
  console.log(jsonArray);
});
</script>
@endsection
