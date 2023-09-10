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
          {{-- <a href="javascript:void(0)" data-bs-toggle="tooltip" title="Orders" class="btn btn-warning"><i class="fas fa-receipt"></i></a> --}}
        {{-- <button type="submit" form="form-product" data-bs-toggle="tooltip" title="Save" class="btn btn-primary"><i class="fas fa-save"></i></button> --}}
        <button type="submit" form="form-product" data-bs-toggle="tooltip" title="儲存" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="{{ $back }}" data-bs-toggle="tooltip" title="{{ $lang->button_back }}" class="btn btn-light"><i class="fas fa-reply"></i></a>
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
          </ul>
          <form id="form-product" action="{{ $save }}" method="post" data-oc-toggle="ajax">
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
                      <label for="input-full_name-{{ $language->code }}" class="col-sm-2 col-form-label">{{ $lang->column_full_name }}</label>
                      <div class="col-sm-10">
                        <div class="input-group">
                          <input type="text" name="translations[{{ $language->code }}][full_name]" value="{{ $product_translations[$language->code]['full_name'] ?? ''  }}" id="input-name-{{ $language->code }}" class="form-control">
                                                  </div>
                        <div id="error-full_name-{{ $language->code }}" class="invalid-feedback"></div>
                      </div>
                    </div>

                    <div class="row mb-3 ">
                      <label for="input-short_name-{{ $language->code }}" class="col-sm-2 col-form-label">{{ $lang->column_short_name }}</label>
                      <div class="col-sm-10">
                        <div class="input-group">
                          <input type="text" name="translations[{{ $language->code }}][short_name]" value="{{ $product_translations[$language->code]['short_name'] ?? ''  }}" id="input-short_name-{{ $language->code }}" class="form-control">
                                                  </div>
                        <div id="error-short_name-{{ $language->code }}" class="invalid-feedback"></div>
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

                    <div class="row mb-3">
                      <label for="input-meta-title-{{ $language->code }}" class="col-sm-2 col-form-label">{{ $lang->column_meta_title }}</label>
                      <div class="col-sm-10">
                        <div class="input-group">
                          <input type="text" name="translations[{{ $language->code }}][meta_title]" value="{{ $product_translations[$language->code]['meta_title'] ?? '' }}" placeholder="Meta Tag Title" id="input-meta-title-{{ $language->code }}" class="form-control">
                                                  </div>
                        <div id="error-meta-title-{{ $language->code }}" class="invalid-feedback"></div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="input-meta-description-{{ $language->code }}" class="col-sm-2 col-form-label">{{ $lang->column_meta_description }}</label>
                      <div class="col-sm-10">
                        <div class="input-group">
                          <textarea name="translations[{{ $language->code }}][meta_description]" rows="5" placeholder="{{ $lang->column_meta_description }}" id="input-meta-description-{{ $language->code }}" class="form-control">{{ $product_translations[$language->code]['meta_description'] ?? '' }}</textarea>
                                                  </div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="input-meta-keyword-{{ $language->code }}" class="col-sm-2 col-form-label">{{ $lang->column_meta_keyword }}</label>
                      <div class="col-sm-10">
                        <div class="input-group">
                          <textarea name="translations[{{ $language->code }}][meta_keyword]" rows="5" placeholder="{{ $lang->column_meta_keyword }}" id="input-meta-keyword-{{ $language->code }}" class="form-control">{{ $product_translations[$language->code]['meta_keyword'] ?? '' }}</textarea>
                                                  </div>
                      </div>
                    </div>
                  </div>
                  @endforeach
                </div>
              </div>

              <div id="tab-data" class="tab-pane">

                {{-- source_code --}}
                <div class="row mb-3">
                  <label for="input-source_code" class="col-sm-2 col-form-label">{{ $lang->column_source_code }}</label>
                  <div class="col-sm-10">
                    <select id="input-source_code" name="source_code" class="form-control">
                      <option value="*">{{ $lang->text_select }}</option>
                      @foreach($source_codes as $source_code)
                      <option value="{{ $source_code->code }}" @if($product->source_code==$source_code->code) selected @endif>{{ $source_code->name }}</option>
                      @endforeach
                    </select>
                    <div id="error-source_code" class="invalid-feedback"></div>
                  </div>
                </div>

                {{-- model --}}
                <div class="row mb-3">
                  <label for="input-model" class="col-sm-2 col-form-label">{{ $lang->column_model }}</label>
                  <div class="col-sm-10">
                    <input type="text" name="model" value="{{ $product->model }}" placeholder="{{ $lang->placeholder_model }}" id="input-model" class="form-control"/>
                    <div id="error-model" class="invalid-feedback"></div>
                  </div>
                </div>

                {{-- purchasing_unit_code --}}
                <div class="row mb-3">
                  <label for="input-purchasing_unit_code" class="col-sm-2 col-form-label">{{ $lang->column_purchasing_unit }}</label>
                  <div class="col-sm-10">
                    <select id="input-purchasing_unit_code" name="purchasing_unit_code" class="form-control">
                      <option value="">--</option>
                      @foreach($units as $code => $unit)
                      <option value="{{ $unit->code }}" @if($unit->code==$product->purchasing_unit_code) selected @endif>{{ $unit->label }}</option>
                      @endforeach
                    </select>
                    <div id="error-purchasing_unit_code" class="invalid-feedback"></div>
                  </div>
                </div>

                {{-- stock_unit_code --}}
                <div class="row mb-3">
                  <label for="input-stock_unit_code" class="col-sm-2 col-form-label">{{ $lang->column_stock_unit }}</label>
                  <div class="col-sm-10">
                    <select id="input-stock_unit_code" name="stock_unit_code" class="form-control">
                      <option value="">--</option>
                      @foreach($units as $code => $unit)
                      <option value="{{ $unit->code }}" @if($unit->code==$product->stock_unit_code) selected @endif>{{ $unit->label }}</option>
                      @endforeach
                    </select>
                    <div id="error-stock_unit_code" class="invalid-feedback"></div>
                  </div>
                </div>

                {{-- manufacturing_unit_code --}}
                <div class="row mb-3">
                  <label for="input-manufacturing_unit_code" class="col-sm-2 col-form-label">{{ $lang->column_manufacturing_unit }}</label>
                  <div class="col-sm-10">
                    <select id="input-manufacturing_unit_code" name="manufacturing_unit_code" class="form-control">
                      <option value="">--</option>
                      @foreach($units as $code => $unit)
                      <option value="{{ $unit->code }}" @if($unit->code==$product->manufacturing_unit_code) selected @endif>{{ $unit->label }}</option>
                      @endforeach
                    </select>
                    <div id="error-manufacturing_unit_code" class="invalid-feedback"></div>
                  </div>
                </div>

                {{-- supplier --}}
                <div class="row mb-3">
                  <label for="input-supplier_name" class="col-sm-2 col-form-label">{{ $lang->column_supplier }}</label>
                  <div class="col-sm-10">
                    <input type="text" id="input-supplier_name" name="supplier_name" value="{{ $product->supplier_name }}" class="form-control" data-oc-target="autocomplete-supplier_name" />
                    <input type="hidden" id="input-supplier_id" name="supplier_id" value="{{ $product->supplier_id }}">
                    <ul id="autocomplete-supplier_name" class="dropdown-menu"></ul>
                    <div id="error-supplier_name " class="invalid-feedback"></div>
                  </div>
                </div>

                {{-- supplier_product_name --}}
                <div class="row mb-3">
                  <label for="input-supplier_product_name" class="col-sm-2 col-form-label">{{ $lang->column_supplier_product }}</label>
                  <div class="col-sm-10">
                    <input type="text" id="input-supplier_product_name" name="supplier_product_name" value="{{ $product->supplier_product_name }}" class="form-control" data-oc-target="autocomplete-supplier_product_name" />
                    <input type="hidden" id="input-supplier_product_id" name="supplier_product_id" value="{{ $product->supplier_product_id }}">
                    <ul id="autocomplete-supplier_product_name" class="dropdown-menu"></ul>
                    <div id="error-supplier_product_name " class="invalid-feedback"></div>
                  </div>
                </div>

                {{-- is_active --}}
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
                  <label for="input-quantity" class="col-sm-2 col-form-label">{{ $lang->column_quantity}}</label>
                  <div class="col-sm-10">
                    <input type="text" name="quantity" value="{{ $product->quantity }}" placeholder="{{ $lang->column_quantity }}" id="input-quantity" class="form-control"/>
                    <div id="error-quantity" class="invalid-feedback"></div>
                  </div>
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
  $('#input-category').autocomplete({
    'source': function(request, response) {
      $.ajax({
        url: "{{ route('lang.admin.catalog.categories.autocomplete') }}?filter_name=" + encodeURIComponent(request),
        dataType: 'json',
        success: function(json) {
          response($.map(json, function(item) {
            return {
              label: item['name'],
              value: item['category_id']
            }
          }));
        }
      });
    },
    'select': function (item) {
      $('#input-category').val('');

      $('#product-category-' + item['value']).remove();

      html = '<tr id="product-category-' + item['value'] + '">';
      html += '  <td>' + item['label'] + '<input type="hidden" name="product_categories[]" value="' + item['value'] + '"/></td>';
      html += '  <td class="text-end"><button type="button" class="btn btn-danger btn-sm"><i class="fa-solid fa-minus-circle"></i></button></td>';
      html += '</tr>';

      $('#product-category tbody').append(html);
    }
  });

  $('#product-category').on('click', '.btn', function () {
      $(this).parent().parent().remove();
  });

  // Main Category
  $('#input-main_category').autocomplete({
    'source': function (request, response) {
        $.ajax({
          url: "{{ route('lang.admin.catalog.categories.autocomplete') }}?filter_name=" + encodeURIComponent(request),
          dataType: 'json',
          success: function (json) {
            json.unshift({
              category_id: 0,
              name: '{{ $lang->text_none }}'
            });

            response($.map(json, function (item) {
              return {
                label: item['name'],
                value: item['category_id']
              }
            }));
          }
        });
    },
    'select': function (item) {
      $('#input-main_category_id').val(item['value']);
      $('#input-main_category').val(item['label']);
    }
  });

// 查主供應商
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
  });
});

// 查廠商料件
$(document).ready(function() {
  $('#input-supplier_product_name').on('input', function(){
    $('#input-supplier_product_name').autocomplete({
      'minLength': 1,
      'source': function (request, response) {
        var regex = /[a-zA-Z0-9\u3105-\u3129]+/; // 注音符號
        if (regex.test(request)) {
          return;
        }else{
          $.ajax({
            url: "{{ $supplier_product_autocomplete_url }}?equal_source_code=SUP&filter_name=" + encodeURIComponent(request),
            dataType: 'json',
            success: function (json) {
              response(json);
            }
          });
        }
      },
      'select': function (item) {
        $('#input-supplier_product_id').val(item.product_id);
        $('#input-supplier_product_name').val(item.product_name);
      }
    });
  });
});

</script>
@endsection
