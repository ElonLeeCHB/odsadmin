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
            <li class="nav-item"><a href="#tab-general" data-bs-toggle="tab" class="nav-link">{{ $lang->tab_general }}</a></li>
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
                          <input type="text" name="translations[{{ $language->code }}][full_name]" value="{{ $product_translations[$language->code]['full_name'] ?? ''  }}" placeholder="{{ $lang->column_full_name }}" id="input-name-{{ $language->code }}" class="form-control">
                                                  </div>
                        <div id="error-full_name-{{ $language->code }}" class="invalid-feedback"></div>
                      </div>
                    </div>
                    <div class="row mb-3 ">
                      <label for="input-short_name-{{ $language->code }}" class="col-sm-2 col-form-label">{{ $lang->column_short_name }}</label>
                      <div class="col-sm-10">
                        <div class="input-group">
                          <input type="text" name="translations[{{ $language->code }}][short_name]" value="{{ $product_translations[$language->code]['short_name'] ?? ''  }}" placeholder="{{ $lang->column_short_name }}" id="input-short_name-{{ $language->code }}" class="form-control">
                                                  </div>
                        <div id="error-short_name-{{ $language->code }}" class="invalid-feedback"></div>
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

              <div id="tab-general" class="tab-pane">
                {{-- main category--}}
                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label">{{ $lang->column_main_category }}</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <input type="text" name="main_category" value="{{ $product->main_category->translation->name ?? ''}}" placeholder="{{ $lang->column_main_category }}" id="input-main_category" data-oc-target="autocomplete-main_category_id" class="form-control"/>
                    </div>
                    <input type="hidden" name="main_category_id" value="{{ $product->main_category_id }}" id="input-main_category_id"/>
                    <ul id="autocomplete-main_category_id" class="dropdown-menu"></ul>
                    <div class="form-text">{{ $lang->help_main_category }}</div>
                  </div>
                </div>

                {{-- product_accounting_category --}}
                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label">{{ $lang->column_accounting_category }}</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <input type="text" id="input-product_accounting_category" name="product_accounting_category" value="" data-oc-target="autocomplete-accounting_category_id" class="form-control"/>
                    </div>
                    <input type="hidden" id="input-accounting_category_id" name="accounting_category_id" value="" />
                    <ul id="autocomplete-accounting_category_id" class="dropdown-menu"></ul>
                    <div class="form-text"></div>
                  </div>
                </div>

                {{-- stock_category --}}
                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label">{{ $lang->column_stock_category }}</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <input type="text" id="input-stock_category" name="stock_category" value="" data-oc-target="autocomplete-stock_category_id" class="form-control"/>
                    </div>
                    <input type="hidden" id="input-stock_category_id" name="stock_category_id" value="" />
                    <ul id="autocomplete-stock_category_id" class="dropdown-menu"></ul>
                    <div class="form-text"></div>
                  </div>
                </div>

                {{-- sales_category --}}
                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label">{{ $lang->column_sales_category }}</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <input type="text" id="input-sales_category" name="sales_category" value="" data-oc-target="autocomplete-sales_category_id" class="form-control"/>
                    </div>
                    <input type="hidden" id="input-sales_category_id" name="sales_category_id" value="" />
                    <ul id="autocomplete-sales_category_id" class="dropdown-menu"></ul>
                    <div class="form-text"></div>
                  </div>
                </div>

                {{-- part_type --}}
                <div class="row mb-3">
                  <label for="input-part_type" class="col-sm-2 col-form-label">{{ $lang->column_part_type }}</label>
                  <div class="col-sm-10">
                    <select id="input-part_type" name="part_type" class="form-control">
                      <option value="P">採購件</option>
                      <option value="M">自製件</option>
                      <option value="S">委外加工件</option>
                    </select>
                    <div id="error-part_type" class="invalid-feedback"></div>
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

                {{-- weight_class_id --}}
                <div class="row mb-3">
                  <label for="input-weight_class_id" class="col-sm-2 col-form-label">{{ $lang->column_weight_class }}</label>
                  <div class="col-sm-10">
                    <select id="input-weight_class_id" name="weight_class_id" class="form-control">
                      <option value="KG">公斤</option>
                      <option value="G">公克</option>
                    </select>
                    <div id="error-weight_class_id" class="invalid-feedback"></div>
                  </div>
                </div>

                {{-- main_supplier --}}
                <div class="row mb-3">
                  <label for="input-model" class="col-sm-2 col-form-label">{{ $lang->column_main_supplier }}</label>
                  <div class="col-sm-10">
                    <input type="text" id="input-main_supplier" name="main_supplier " value="{{ $product->main_supplier }}" class="form-control"/>
                    <div id="error-main_supplier " class="invalid-feedback"></div>
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
  <script type="text/javascript"><!--
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


  --></script>
@endsection
