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
        <button type="submit" form="form-product" data-bs-toggle="tooltip" title="{{ $lang->button_save }}" class="btn btn-primary"><i class="fa fa-save"></i></button>
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
            <li class="nav-item"><a href="#tab-option" data-bs-toggle="tab" class="nav-link">{{ $lang->tab_option }}</a></li>
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
                    <input type="hidden" name="translations[{{ $language->code }}][id]" value="{{ $translations[$language->code]['id'] ?? '' }}" >

                    <div class="row mb-3 required">
                      <label for="input-name-{{ $language->code }}" class="col-sm-2 col-form-label">{{ $lang->column_name }}</label>
                      <div class="col-sm-10">
                        <div class="input-group">
                          <input type="text" name="translations[{{ $language->code }}][name]" value="{{ $translations[$language->code]['name'] ?? ''  }}" placeholder="{{ $lang->column_name }}" id="input-name-{{ $language->code }}" class="form-control">
                        </div>
                        <div id="error-name-{{ $language->code }}" class="invalid-feedback"></div>
                      </div>
                    </div>
                    <div class="row mb-3 ">
                      <label for="input-full_name-{{ $language->code }}" class="col-sm-2 col-form-label">{{ $lang->column_full_name }}</label>
                      <div class="col-sm-10">
                        <div class="input-group">
                          <input type="text" name="translations[{{ $language->code }}][full_name]" value="{{ $translations[$language->code]['full_name'] ?? ''  }}" placeholder="{{ $lang->column_full_name }}" id="input-name-{{ $language->code }}" class="form-control">
                                                  </div>
                        <div id="error-full_name-{{ $language->code }}" class="invalid-feedback"></div>
                      </div>
                    </div>
                    <div class="row mb-3 ">
                      <label for="input-short_name-{{ $language->code }}" class="col-sm-2 col-form-label">{{ $lang->column_short_name }}</label>
                      <div class="col-sm-10">
                        <div class="input-group">
                          <input type="text" name="translations[{{ $language->code }}][short_name]" value="{{ $translations[$language->code]['short_name'] ?? ''  }}" placeholder="{{ $lang->column_short_name }}" id="input-short_name-{{ $language->code }}" class="form-control">
                                                  </div>
                        <div id="error-short_name-{{ $language->code }}" class="invalid-feedback"></div>
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label for="input-meta-title-{{ $language->code }}" class="col-sm-2 col-form-label">{{ $lang->column_meta_title }}</label>
                      <div class="col-sm-10">
                        <div class="input-group">
                          <input type="text" name="translations[{{ $language->code }}][meta_title]" value="{{ $translations[$language->code]['meta_title'] ?? '' }}" placeholder="Meta Tag Title" id="input-meta-title-{{ $language->code }}" class="form-control">
                                                  </div>
                        <div id="error-meta-title-{{ $language->code }}" class="invalid-feedback"></div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="input-meta-description-{{ $language->code }}" class="col-sm-2 col-form-label">{{ $lang->column_meta_description }}</label>
                      <div class="col-sm-10">
                        <div class="input-group">
                          <textarea name="translations[{{ $language->code }}][meta_description]" rows="5" placeholder="{{ $lang->column_meta_description }}" id="input-meta-description-{{ $language->code }}" class="form-control">{{ $translations[$language->code]['meta_description'] ?? '' }}</textarea>
                                                  </div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="input-meta-keyword-{{ $language->code }}" class="col-sm-2 col-form-label">{{ $lang->column_meta_keyword }}</label>
                      <div class="col-sm-10">
                        <div class="input-group">
                          <textarea name="translations[{{ $language->code }}][meta_keyword]" rows="5" placeholder="{{ $lang->column_meta_keyword }}" id="input-meta-keyword-{{ $language->code }}" class="form-control">{{ $translations[$language->code]['meta_keyword'] ?? '' }}</textarea>
                                                  </div>
                      </div>
                    </div>
                  </div>
                  @endforeach
                </div>
              </div>

              <div id="tab-data" class="tab-pane">
                
                {{-- sort_order --}}
                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label">{{ $lang->column_sort_order }}</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <input type="text" id="input-sort_order" name="sort_order" value="{{ $product->sort_order }}" class="form-control"/>
                    </div>
                    <div class="form-text">有可能會影響列印排版(主餐)</div>
                  </div>
                </div>

                {{-- main_category --}}
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

                {{-- model --}}
                <div class="row mb-3">
                  <label for="input-model" class="col-sm-2 col-form-label">{{ $lang->column_model }}</label>
                  <div class="col-sm-10">
                    <input type="text" name="model" value="{{ $product->model }}" placeholder="{{ $lang->column_model }}" id="input-model" class="form-control"/>
                    <div id="error-model" class="invalid-feedback"></div>
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

                
                <div class="row mb-3">
                  <label for="input-comment" class="col-sm-2 col-form-label">{{ $lang->column_comment}}</label>
                  <div class="col-sm-10">
                    <textarea id="input-comment" name="comment" class="form-control">{{ $product->comment }}</textarea>
                    <div id="error-comment" class="invalid-feedback"></div>
                  </div>
                </div>
                
              </div>

              <div id="tab-bom" class="tab-pane">
                <div class="col-sm-12">
                  <div class="row">
                    <div class="table-responsive">
                      <table id="product-bom" class="table table-striped table-bordered table-hover">
                        <thead>
                          <tr>
                            <td class="text-left"><span data-toggle="tooltip" title="(Autocomplete)">代號</span></td>
                            <td class="text-left"><span data-toggle="tooltip" title="(Autocomplete)">品名</span></td>
                            <td class="text-right">數量</td>
                            <td></td>
                          </tr>
                        </thead>
                        <tbody>
                          <?php $bom_row = 0; ?>
                          @foreach($bom_products as $i => $bom_product)
                          <tr id="bom-row-{{ $bom_row }}">
                            <td class="text-left"><input type="text" name="product_bom[{{ $bom_row }}][item_code]" value="{{ $bom_product->model }}" placeholder="Item Model" class="form-control" disabled/>
                              <input type="hidden" name="product_bom[{{ $bom_row }}][component_id]" value="1"/>
                              <input type="hidden" name="product_bom[{{ $bom_row }}][item_cost]" value="3"/>
                              <input type="hidden" name="product_bom[{{ $bom_row }}][scrap]" value="0"/>
                              <input type="hidden" name="product_bom[{{ $bom_row }}][material_qty_digit]" value="0"/>
                              <input type="hidden" name="product_bom[{{ $bom_row }}][item_cost_digit]" value="0"/>
                            </td>
                            <td class="text-left"><input type="text" name="product_bom[{{ $bom_row }}][item_name]" value="{{ $bom_product->name }}" placeholder="Item Name" id="input-product_name-{{ $bom_row }}" list="list-row-{{ $bom_row }}-name" class="form-control bom-product"/>
                            <datalist id="list-row-{{ $bom_row }}-name"></datalist>
                            </td>
                            <td class="text-right"><input type="text" name="product_bom[{{ $bom_row }}][item_qty]" value="{{ $bom_product->pivot->quantity }}" placeholder="用量" class="form-control bom_item_qty isDecimal qtyDec-0" data-rel_wt_calculation="1" onchange="getGrossQty(0)"/></td>
                            <td class="text-left"><button type="button" onclick="$('#bom-row-{{ $bom_row }}').remove();" data-toggle="tooltip" title="Remove" class="btn btn-danger"><i class="fa fa-minus-circle"></i></button></td>
                            </tr>    
                                                
                          <?php $bom_row++; ?>
                          @endforeach                                                                                        </tbody>
                        <tfoot>
                          <tr>
                            <td colspan="3" class="text-right">
                            </td>
                            <td class="text-start"><button type="button" id="button-bom" data-bs-toggle="tooltip" title="{{ $lang->button_bom_add }}" class="btn btn-primary"><i class="fa-solid fa-plus-circle"></i></button></td>
                          </tr>
                        </tfoot>
                      </table>
                      </div>   
                    </div>
                </div>               
              </div>

              <div id="tab-option" class="tab-pane">
                <div id="option">
                  @php
                  $option_row = 0;
                  $option_value_row = 0;
                  @endphp

                  @foreach($product_options as $product_option)

                  <fieldset id="option-row-{{ $option_row }}">
                      <legend class="float-none">
                        {{ $product_option->name }} 
                        <span style="font-size:10px;">option_id:{{ $product_option->option_id }} </span>
                        <button type="button" class="btn btn-danger btn-sm float-end" onclick="$('#option-row-{{ $option_row }}').remove();"><i class="fa-solid fa-minus-circle"></i></button>
                      </legend>
                      <input type="hidden" name="product_options[{{ $option_row }}][product_option_id]" value="{{ $product_option->product_option_id }}"/> 
                      <input type="hidden" name="product_options[{{ $option_row }}][name]" value="{{ $product_option->name }}"/> 
                      <input type="hidden" name="product_options[{{ $option_row }}][option_id]" value="{{ $product_option->option_id }}"/> 
                      <input type="hidden" name="product_options[{{ $option_row }}][type]" value="{{ $product_option->type }}"/>

                      <div class="row mb-3">
                        <label for="input-required-{{ $option_row }}" class="col-sm-2 col-form-label">{{ $lang->text_required }}</label>
                        <div class="col-sm-10">
                          <select name="product_options[{{ $option_row }}][required]" id="input-required-{{ $option_row }}" class="form-select">
                            <option value="1"@if($product_option->required ) selected="selected" @endif>{{ $lang->text_yes }}</option>
                            <option value="0"@if(!$product_option->required )selected="selected" @endif>{{ $lang->text_no }}</option>
                          </select>
                        </div>
                      </div>

                      <div class="row mb-3">
                        <label for="input-is_active-{{ $option_row }}" class="col-sm-2 col-form-label">{{ $lang->column_enable }}</label>
                        <div class="col-sm-10">
                          <select name="product_options[{{ $option_row }}][is_active]" id="input-is_active-{{ $option_row }}" class="form-select">
                            <option value="1"@if($product_option->is_active ) selected="selected" @endif>{{ $lang->text_yes }}</option>
                            <option value="0"@if(!$product_option->is_active )selected="selected" @endif>{{ $lang->text_no }}</option>
                          </select>
                        </div>
                      </div>

                      <div class="row mb-3">
                        <label for="input-is_fixed-{{ $option_row }}" class="col-sm-2 col-form-label">{{ $lang->column_is_fixed }}</label>
                        <div class="col-sm-10">
                          <select name="product_options[{{ $option_row }}][is_fixed]" id="input-is_fixed-{{ $option_row }}" class="form-select">
                            <option value="1"@if($product_option->is_fixed ) selected="selected" @endif>{{ $lang->text_yes }}</option>
                            <option value="0"@if(!$product_option->is_fixed ) selected="selected" @endif>{{ $lang->text_no }}</option>
                          </select>
                        </div>
                      </div>

                      <div class="row mb-3">
                        <label for="input-is_hidden-{{ $option_row }}" class="col-sm-2 col-form-label">{{ $lang->column_is_hidden }}</label>
                        <div class="col-sm-10">
                          <select name="product_options[{{ $option_row }}][is_hidden]" id="input-is_hidden-{{ $option_row }}" class="form-select">
                            <option value="1"@if($product_option->is_hidden ) selected="selected" @endif>{{ $lang->text_yes }}</option>
                            <option value="0"@if(!$product_option->is_hidden )selected="selected" @endif>{{ $lang->text_no }}</option>
                          </select>
                        </div>
                      </div>

                      @if($product_option->type == 'text')
                        <div class="row mb-3">
                          <label for="input-option-{{ $option_row }}-value" class="col-sm-2 col-form-label">{{ $lang->column_option_value }}</label>
                          <div class="col-sm-10">
                            <input type="text" id="input-option-{{ $option_row }}-value" name="product_options[{{ $option_row }}][value]" value="{{ $product_option->value }}" placeholder="{{ $lang->column_option_value }}" class="form-control"/>
                          </div>
                        </div>
                      @endif

                      @if($product_option->type == 'textarea')
                        <div class="row mb-3">
                          <label for="input-option-{{ $option_row }}-value" class="col-sm-2 col-form-label">{{ $lang->column_option_value }}</label>
                          <div class="col-sm-10">
                            <textarea id="input-option-{{ $option_row }}-value" name="product_options[{{ $option_row }}][value]" rows="5" placeholder="{{ $lang->column_option_value }}" class="form-control">{{ $product_option->value }}</textarea>
                          </div>
                        </div>
                      @endif

                      @if($product_option->type == 'file')
                        <div class="row mb-3 d-none">
                          <label for="input-option-{{ $option_row }}-value" class="col-sm-2 col-form-label">{{ $lang->column_option_value }}</label>
                          <div class="col-sm-10"><input type="text" id="input-option-{{ $option_row }}-value" name="product_options[{{ $option_row }}][value]" value="{{ $product_option->value }}" placeholder="{{ $lang->column_option_value }}" class="form-control"/></div>
                        </div>
                      @endif

                      @if($product_option->type == 'date')
                        <div class="row mb-3">
                          <label for="input-option-{{ $option_row }}-value" class="col-sm-2 col-form-label">{{ $lang->column_option_value }}</label>
                          <div class="col-sm-10 col-md-4">
                            <div class="input-group">
                              <input type="text" id="input-option-{{ $option_row }}-value" name="product_options[{{ $option_row }}][value]" value="{{ $product_option->value }}" placeholder="{{ $lang->column_option_value }}" class="form-control date"/>
                              <div class="input-group-text"><i class="fa-regular fa-calendar"></i></div>
                            </div>
                          </div>
                        </div>
                      @endif

                      @if($product_option->type == 'time')
                        <div class="row mb-3">
                          <label for="input-option-{{ $option_row }}-value" class="col-sm-2 col-form-label">{{ $lang->column_option_value }}</label>
                          <div class="col-sm-10 col-md-4">
                            <div class="input-group">
                              <input type="text" id="input-option-{{ $option_row }}-value" name="product_options[{{ $option_row }}][value]" value="{{ $product_option->value }}" placeholder="{{ $lang->column_option_value }}" class="form-control time"/>
                              <div class="input-group-text"><i class="fa-regular fa-calendar"></i></div>
                            </div>
                          </div>
                        </div>
                      @endif

                      @if($product_option->type == 'datetime')
                        <div class="row mb-3">
                          <label for="input-option-{{ $option_row }}-value" class="col-sm-2 col-form-label">{{ $lang->column_option_value }}</label>
                          <div class="col-sm-10 col-md-4">
                            <div class="input-group">
                              <input type="text" id="input-option-{{ $option_row }}-value" name="product_options[{{ $option_row }}][value]" value="{{ $product_option->value }}" placeholder="{{ $lang->column_option_value }}" class="form-control datetime"/>
                              <div class="input-group-text"><i class="fa-regular fa-calendar"></i></div>
                            </div>
                          </div>
                        </div>
                      @endif

                      <div class="row mb-3">
                        <label for="input-option-{{ $option_row }}-sort_order" class="col-sm-2 col-form-label">{{ $lang->column_sort_order }}</label>
                        <div class="col-sm-10">
                          <input type="number" id="input-option-{{ $option_row }}-sort_order" name="product_options[{{ $option_row }}][sort_order]" value="{{ $product_option->sort_order }}" class="form-control" >
                        </div>
                      </div>
                      
                      @if($product_option->type == 'options_with_qty' || $product_option->type == 'select' || $product_option->type == 'radio' || $product_option->type == 'checkbox' || $product_option->type == 'image')
                        <div class="table-responsive">
                          <table class="table table-bordered table-hover">
                            <thead>
                              <tr>
                                <td class="text-start">{{ $lang->column_option_value }}</td>
                                <td class="text-start">{{ $lang->column_is_default }}</td>
                                <td class="text-start">{{ $lang->column_is_active }}</td>
                                <td class="text-end">{{ $lang->column_price }}</td>
                                <td class="text-end">{{ $lang->column_sort_order }}</td>
                                <td></td>
                              </tr>
                            </thead>
                            <tbody id="option-value-{{ $option_row }}">
                            
                              @foreach($product_option->product_option_values as $product_option_value)
                                <tr id="option-value-row-{{ $option_value_row }}">
                                  <td class="text-start">{{ $product_option_value->name }}
                                    <input type="hidden" name="product_options[{{ $option_row }}][product_option_values][{{ $option_value_row }}][option_value_id]" value="{{ $product_option_value->option_value_id }}"/> 
                                    <input type="hidden" name="product_options[{{ $option_row }}][product_option_values][{{ $option_value_row }}][product_option_value_id]" value="{{ $product_option_value->product_option_value_id }}"/>
                                  </td>
                                  <td class="text-start">@if($product_option_value->is_default)
                                      {{ $lang->text_yes }}
                                    @else
                                      {{ $lang->text_no }}
                                    @endif
                                    <input type="hidden" name="product_options[{{ $option_row }}][product_option_values][{{ $option_value_row }}][is_default]" value="{{ $product_option_value->is_default }}"/>
                                  </td>
                                  <td class="text-start">@if($product_option_value->is_active)
                                      {{ $lang->text_yes }}
                                    @else
                                      {{ $lang->text_no }}
                                    @endif
                                    <input type="hidden" name="product_options[{{ $option_row }}][product_option_values][{{ $option_value_row }}][is_active]" value="{{ $product_option_value->is_active }}"/>
                                  </td>
                                  <td class="text-end">{{ $product_option_value->price_prefix }}{{ $product_option_value->price }}
                                    <input type="hidden" name="product_options[{{ $option_row }}][product_option_values][{{ $option_value_row }}][price_prefix]" value="{{ $product_option_value->price_prefix }}"/> 
                                    <input type="hidden" name="product_options[{{ $option_row }}][product_option_values][{{ $option_value_row }}][price]" value="{{ $product_option_value->price }}"/></td>
                                  <td class="text-end">
                                  {{ $product_option_value->sort_order ?? '' }}
                                  <input type="hidden" name="product_options[{{ $option_row }}][product_option_values][{{ $option_value_row }}][sort_order]" value="{{ $product_option_value->sort_order }}"/></td>
                                  </td>
                                  <td class="text-end">
                                    <button type="button" title="{{ $lang->button_edit }}" data-bs-toggle="tooltip" data-option-row="{{ $option_row }}" data-option-value-row="{{ $option_value_row }}" class="btn btn-primary"><i class="fa-solid fa-pencil"></i></button> 
                                    <button type="button" onclick="$('#option-value-row-{{ $option_value_row }}').remove();" data-bs-toggle="tooltip" title="{{ $lang->button_remove }}" class="btn btn-danger"><i class="fa-solid fa-minus-circle"></i></button>
                                  </td>
                                </tr>
                                @php $option_value_row++; @endphp
                              @endforeach
                            </tbody>
                            <tfoot>
                              <tr>
                                <td colspan="5"></td>
                                <td class="text-end"><button type="button" data-bs-toggle="tooltip" title="{{ $lang->button_add }}" data-option-row="{{ $option_row }}" class="btn btn-primary"><i class="fa-solid fa-plus-circle"></i></button></td>
                              </tr>
                            </tfoot>
                          </table>
                          <select id="product-option-values-{{ $option_row }}" class="d-none">
                            @if($option_values[$product_option->option_id])
                              @foreach($option_values[$product_option->option_id] as $option_value)
                              <option value="{{ $option_value->id }}">{{ $option_value->name }}</option>
                              @endforeach
                            @endif
                          </select>
                        </div>
                      @endif
                    </fieldset>

                    @php $option_row++; @endphp
                  @endforeach
                </div>
                <fieldset>
                  <legend class="float-none">新增選項</legend>
                  <div class="row mb-3">
                    <label for="input-option" class="col-sm-2 col-form-label">選項</label>
                    <div class="col-sm-10">
                      <input type="text" name="option" value="" placeholder="選項名稱" id="input-option" data-oc-target="autocomplete-option" class="form-control" autocomplete="off">
                      <ul id="autocomplete-option" class="dropdown-menu"></ul>
                      <div class="form-text">(即時查詢)</div>
                    </div>
                  </div>
                </fieldset>
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
            response(json);
          }
        });
    },
    'select': function (item) {
      //alert(JSON.stringify(item));
      $('#input-main_category_id').val(item['value']);
      $('#input-main_category').val(item['label']);
    }
  });


  var bom_row = {{ $bom_row }};

  $('#button-bom').on('click', function () {
      html = '<tr id="bom-row-' + bom_row + '">';
      html += '  <td class="text-end"><input type="text" name="product_bom[' + bom_row + '][model]" value="" placeholder="{{ $lang->column_model }}" class="form-control" disabled /></td>';
      html += '  <td class="text-end"><input type="text" name="product_bom[' + bom_row + '][name]" value="" placeholder="{{ $lang->column_name }}" class="form-control bom-product"/></td>';
      html += '  <td class="text-end"><input type="text" name="product_bom[' + bom_row + '][quantity]" value="" placeholder="{{ $lang->column_quantity }}" class="form-control"/></td>';
      html += '  <td class="text-start"><button type="button" onclick="$(\'#bom-row-' + bom_row + '\').remove();" data-bs-toggle="tooltip" title="{{ $lang->button_remove }}" class="btn btn-danger"><i class="fa-solid fa-minus-circle"></i></button></td>';
      html += '</tr>';

      $('#product-bom tbody').append(html);
      bom_row++;
  });

  $('.bom-product').autocomplete({
    'source': function(request, response) {
      $.ajax({
        url: "{{ route('lang.admin.catalog.products.autocomplete') }}?filter_is_salable=0&filter_name=" + encodeURIComponent(request),
        dataType: 'json',
        success: function(json) {
          response($.map(json, function(item) {
            return {
              label: item['name'],
              value: item['product_id']
            }
          }));
        }
      });
    },
    'select': function (item) {
      // $('#input-category').val('');

      // $('#product-category-' + item['value']).remove();

    }
  });

  <?php if(empty($product->master_id)){ ?>
  var option_row = {{ $option_row }};

  $('#input-option').autocomplete({
      'source': function (request, response) {
        $.ajax({
            url: "{{ route('lang.admin.catalog.options.autocomplete') }}?filter_name=" + encodeURIComponent(request)+ "&filter_model=Product",
            dataType: 'json',
            success: function (json) {
                response($.map(json, function (item) {
                    return {
                        label: item['name'],
                        value: item['option_id'],
                        type: item['type'],
                        option_value: item['option_value']
                    }
                }));
            }
        });
      },
      'select': function (item) {
          html = '<fieldset id="option-row-' + option_row + '">';
          html += '  <legend class="float-none">' + item['label'] + ' <button type="button" class="btn btn-danger btn-sm float-end" onclick="$(\'#option-row-' + option_row + '\').remove();"><i class="fa-solid fa-minus-circle"></i></button></legend>';
          html += '  <input type="hidden" name="product_options[' + option_row + '][product_option_id]" value="" />';
          html += '  <input type="hidden" name="product_options[' + option_row + '][name]" value="' + item['label'] + '" />';
          html += '  <input type="hidden" name="product_options[' + option_row + '][option_id]" value="' + item['value'] + '" />';
          html += '  <input type="hidden" name="product_options[' + option_row + '][type]" value="' + item['type'] + '" />';

          html += '  <div class="row mb-3">';
          html += '    <label for="input-required-' + option_row + '" class="col-sm-2 col-form-label">{{ $lang->column_required }}</label>';
          html += '	   <div class="col-sm-10"><select name="product_options[' + option_row + '][required]" id="input-required-' + option_row + '" class="form-select">';
          html += '	     <option value="1">{{ $lang->text_yes }}</option>';
          html += '	     <option value="0" selected>{{ $lang->text_no }}</option>';
          html += '	 </select></div>';
          html += '  </div>';

          if (item['type'] == 'text') {
              html += '	<div class="row mb-3">';
              html += '	  <label for="input-option-' + option_row + '" class="col-sm-2 col-form-label">{{ $lang->column_option_value }}</label>';
              html += '	  <div class="col-sm-10"><input type="text" name="product_options[' + option_row + '][value]" value="" placeholder="{{ $lang->column_option_value }}" id="input-option-' + option_row + '" class="form-control"/></div>';
              html += '	</div>';
          }

          if (item['type'] == 'textarea') {
              html += '	<div class="row mb-3">';
              html += '	  <label for="input-option-' + option_row + '" class="col-sm-2 col-form-label">{{ $lang->column_option_value }}</label>';
              html += '	  <div class="col-sm-10"><textarea name="product_options[' + option_row + '][value]" rows="5" placeholder="{{ $lang->column_option_value }}" id="input-option-' + option_row + '" class="form-control"></textarea></div>';
              html += '	</div>';
          }

          if (item['type'] == 'file') {
              html += '	<div class="row mb-3 d-none">';
              html += '	  <label for="input-option-' + option_row + '" class="col-sm-2 col-form-label">{{ $lang->column_option_value }}</label>';
              html += '	  <div class="col-sm-10 d-none"><input type="text" name="product_options[' + option_row + '][value]" value="" placeholder="{{ $lang->column_option_value }}" id="input-option-' + option_row + '" class="form-control"/></div>';
              html += '	</div>';
          }

          if (item['type'] == 'date') {
              html += '	<div class="row mb-3">';
              html += '	  <label for="input-option-' + option_row + '" class="col-sm-2 col-form-label">{{ $lang->column_option_value }}</label>';
              html += '	  <div class="col-sm-10 col-md-4"><div class="input-group"><input type="text" name="product_options[' + option_row + '][value]" value="" placeholder="{{ $lang->column_option_value }}" id="input-option-' + option_row + '" class="form-control date"/><div class="input-group-text"><i class="fa-regular fa-calendar"></i></div></div></div>';
              html += '	</div>';
          }

          if (item['type'] == 'time') {
              html += '	<div class="row mb-3">';
              html += '	  <label for="input-option-' + option_row + '" class="col-sm-2 col-form-label">{{ $lang->column_option_value }}</label>';
              html += '	  <div class="col-sm-10 col-md-4"><div class="input-group"><input type="text" name="product_options[' + option_row + '][value]" value="" placeholder="{{ $lang->column_option_value }}" id="input-option-' + option_row + '" class="form-control time"/><div class="input-group-text"><i class="fa-regular fa-calendar"></i></div></div></div>';
              html += '	</div>';
          }

          if (item['type'] == 'datetime') {
              html += '	<div class="row mb-3">';
              html += '	  <label for="input-option-' + option_row + '" class="col-sm-2 col-form-label">{{ $lang->column_option_value }}</label>';
              html += '	  <div class="col-sm-10 col-md-4"><div class="input-group"><input type="text" name="product_options[' + option_row + '][value]" value="" placeholder="{{ $lang->column_option_value }}" id="input-option-' + option_row + '" class="form-control datetime"/><div class="input-group-text"><i class="fa-regular fa-calendar"></i></div></div></div>';
              html += '	</div>';
          }

          if (item['type'] == 'options_with_qty' || item['type'] == 'select' || item['type'] == 'radio' || item['type'] == 'checkbox' || item['type'] == 'image') {
              html += '<div class="table-responsive">';
              html += '  <table id="option-value-' + option_row + '" class="table table-bordered table-hover">';
              html += '  	 <thead>';
              html += '      <tr>';
              html += '        <td class="text-start">{{ $lang->column_option_value }}</td>';
              html += '        <td class="text-start">{{ $lang->column_is_default }}</td>';
              html += '        <td class="text-end">{{ $lang->column_price }}</td>';
              html += '        <td class="text-end">{{ $lang->column_sort_order }}</td>';
              html += '        <td></td>';
              html += '      </tr>';
              html += '    </thead>';
              html += '    <tbody></tbody>';
              html += '    <tfoot>';
              html += '      <tr>';
              html += '        <td colspan="5"></td>';
              html += '        <td class="text-end"><button type="button" data-option-row="' + option_row + '" data-bs-toggle="tooltip" title="{{ $lang->button_option_value_add }}" class="btn btn-primary"><i class="fa-solid fa-plus-circle"></i></button></td>';
              html += '      </tr>';
              html += '    </tfoot>';
              html += '  </table>';
              html += '</div>';

              html += '  <select id="product-option-values-' + option_row + '" class="d-none">';

              for (i = 0; i < item['option_value'].length; i++) {
                  html += '<option value="' + item['option_value'][i]['option_value_id'] + '">' + item['option_value'][i]['name'] + '</option>';
              }

              html += '  </select>';
              html += '</fieldset>';
            }

          $('#option').append(html);

          option_row++;
      }
  });


  var option_value_row = {{ $option_value_row }};

  $('#option').on('click', '.btn-primary', function () { //彈出選項視窗

      var element = this;

      if ($(element).attr('data-option-value-row')) {
          element.option_value_row = $(element).attr('data-option-value-row');
      } else {
          element.option_value_row = option_value_row;
      }

      $('.modal').remove();

      html = '<div id="modal-option" class="modal fade">';
      html += '  <div class="modal-dialog">';
      html += '    <div class="modal-content">';
      html += '      <div class="modal-header">';
      html += '        <h5 class="modal-title"><i class="fa-solid fa-pencil"></i> {{ $lang->text_option_value }}</h5> <button type="button" class="btn-close" data-bs-dismiss="modal"></button>';
      html += '      </div>';
      html += '      <div class="modal-body">';
      html += '        <div class="mb-3">';
      html += '      	   <label for="input-modal-option-value" class="form-label">{{ $lang->column_option_value }}</label>';
      html += '      	   <select name="option_value_id" id="input-modal-option-value" class="form-select">';

      option_value = $('#product-option-values-' + $(element).attr('data-option-row') + ' option');

      for (i = 0; i < option_value.length; i++) {
          if ($(element).attr('data-option-value-row') && $(option_value[i]).val() == $('input[name=\'product_options[' + $(element).attr('data-option-row') + '][product_option_values][' + element.option_value_row + '][option_value_id]\']').val()) {
              html += '<option value="' + $(option_value[i]).val() + '" selected="selected">' + $(option_value[i]).text() + '</option>';
          } else {
              html += '<option value="' + $(option_value[i]).val() + '">' + $(option_value[i]).text() + '</option>';
          }
      }

      html += '      	   </select>';
      html += '          <input type="hidden" name="product_option_value_id" value="' + ($(element).attr('data-option-value-row') ? $('input[name=\'product_options[' + $(element).attr('data-option-row') + '][product_option_values][' + element.option_value_row + '][product_option_value_id]\']').val() : '') + '"/>';
      html += '        </div>';

      html += '        <div class="mb-3">';
      html += '      	   <label for="input-modal-is_default" class="form-label">{{ $lang->column_is_default }}</label>';
      html += '      	   <select name="is_default" id="input-modal-is_default" class="form-select">';

      if ($(element).attr('data-option-value-row') && $('input[name=\'product_options[' + $(element).attr('data-option-row') + '][product_option_values][' + element.option_value_row + '][is_default]\']').val() == '1') {
          html += '        <option value="1" selected="selected">{{ $lang->text_yes }}</option>';
          html += '      	 <option value="0">{{ $lang->text_no }}</option>';
      } else {
          html += '      	 <option value="1">{{ $lang->text_yes }}</option>';
          html += '      	 <option value="0" selected="selected">{{ $lang->text_no }}</option>';
      }

      html += '      	   </select>';
      html += '        </div>';

      html += '        <div class="mb-3">';
      html += '      	   <label for="input-modal-is_active" class="form-label">{{ $lang->column_is_active }}</label>';
      html += '      	   <select name="is_active" id="input-modal-is_active" class="form-select">';

      if ($(element).attr('data-option-value-row') && $('input[name=\'product_options[' + $(element).attr('data-option-row') + '][product_option_values][' + element.option_value_row + '][is_active]\']').val() == '1') {
          html += '        <option value="1" selected="selected">{{ $lang->text_yes }}</option>';
          html += '      	 <option value="0">{{ $lang->text_no }}</option>';
      } else {
          html += '      	 <option value="1">{{ $lang->text_yes }}</option>';
          html += '      	 <option value="0" selected="selected">{{ $lang->text_no }}</option>';
      }

      html += '      	   </select>';
      html += '        </div>';

      html += '        <div class="mb-3">';
      html += '      	   <label for="input-modal-price" class="form-label">{{ $lang->column_price }}</label>';
      html += '          <div class="input-group">';
      html += '            <select name="price_prefix" class="form-select">';

      if ($(element).attr('data-option-value-row') && $('input[name=\'product_options[' + $(element).attr('data-option-row') + '][product_option_values][' + element.option_value_row + '][price_prefix]\']').val() == '+') {
          html += '      	   <option value="+" selected="selected">+</option>';
      } else {
          html += '      	   <option value="+">+</option>';
      }

      if ($(element).attr('data-option-value-row') && $('input[name=\'product_options[' + $(element).attr('data-option-row') + '][product_option_values][' + element.option_value_row + '][price_prefix]\']').val() == '-') {
          html += '      	       <option value="-" selected="selected">-</option>';
      } else {
          html += '      	       <option value="-">-</option>';
      }

      html += '      	     </select>';
      html += '      	     <input type="text" name="price" value="' + ($(element).attr('data-option-value-row') ? $('input[name=\'product_options[' + $(element).attr('data-option-row') + '][product_option_values][' + element.option_value_row + '][price]\']').val() : '0') + '" placeholder="{{ $lang->column_price }}" id="input-modal-price" class="form-control"/>';
      html += '          </div>';
      html += '        </div>';

      //sort_order
      html += '        <div class="mb-3">';
      html += '      	   <label for="input-modal-sort_order" class="form-label">{{ $lang->column_sort_order}}</label>';
      html += '          <input type="text" id="input-modal-sort_order" name="sort_order" value="' + ($(element).attr('data-option-value-row') ? $('input[name=\'product_options[' + $(element).attr('data-option-row') + '][product_option_values][' + element.option_value_row + '][sort_order]\']').val() : '9999') + '" class="form-control" autocomplete="off">';
      html += '        </div>';

      html += '      </div>';

      html += '      <div class="modal-footer">';
      html += '	     <button type="button" id="button-save" data-option-row="' + $(element).attr('data-option-row') + '" data-option-value-row="' + element.option_value_row + '" class="btn btn-primary">{{ $lang->button_save }}</button> <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ $lang->button_cancel }}</button>';
      html += '      </div>';
      html += '    </div>';
      html += '  </div>';
      html += '</div>';

      $('body').append(html);

      $('#modal-option').modal('show');

      $('#modal-option #button-save').on('click', function () {
          html = '<tr id="option-value-row-' + element.option_value_row + '">';
          html += '  <td class="text-start">' + $('#modal-option select[name=\'option_value_id\'] option:selected').text() + '<input type="hidden" name="product_options[' + $(element).attr('data-option-row') + '][product_option_values][' + element.option_value_row + '][option_value_id]" value="' + $('#modal-option select[name=\'option_value_id\']').val() + '"/><input type="hidden" name="product_options[' + $(element).attr('data-option-row') + '][product_option_values][' + element.option_value_row + '][product_option_value_id]" value="' + $('#modal-option input[name=\'product_option_value_id\']').val() + '"/></td>';
          html += '  <td class="text-start">' + ($('#modal-option select[name=\'is_default\'] option:selected').val() == '1' ? '{{ $lang->text_yes }}' : '{{ $lang->text_no }}') + '<input type="hidden" name="product_options[' + $(element).attr('data-option-row') + '][product_option_values][' + element.option_value_row + '][is_default]" value="' + $('#modal-option select[name=\'is_default\'] option:selected').val() + '"/></td>';
          html += '  <td class="text-start">' + ($('#modal-option select[name=\'is_active\'] option:selected').val() == '1' ? '{{ $lang->text_yes }}' : '{{ $lang->text_no }}') + '<input type="hidden" name="product_options[' + $(element).attr('data-option-row') + '][product_option_values][' + element.option_value_row + '][is_active]" value="' + $('#modal-option select[name=\'is_active\'] option:selected').val() + '"/></td>';
          html += '  <td class="text-end">' + $('#modal-option select[name=\'price_prefix\'] option:selected').val() + $('#modal-option input[name=\'price\']').val() + '<input type="hidden" name="product_options[' + $(element).attr('data-option-row') + '][product_option_values][' + element.option_value_row + '][price_prefix]" value="' + $('#modal-option select[name=\'price_prefix\'] option:selected').val() + '"/><input type="hidden" name="product_options[' + $(element).attr('data-option-row') + '][product_option_values][' + element.option_value_row + '][price]" value="' + $('#modal-option input[name=\'price\']').val() + '"/></td>';
          html += '  <td class="text-end">'+$('#modal-option input[name=\'sort_order\']').val()+'<input type="hidden" name="product_options[' + $(element).attr('data-option-row') + '][product_option_values][' + element.option_value_row + '][sort_order]" value="' + $('#modal-option input[name=\'sort_order\']').val() + '"/></td>';
          html += '  <td class="text-end"><button type="button" data-bs-toggle="tooltip" title="{{ $lang->button_edit }}" data-option-row="' + $(element).attr('data-option-row') + '" data-option-value-row="' + element.option_value_row + '"class="btn btn-primary"><i class="fa-solid fa-pencil"></i></button> <button type="button" onclick="$(\'#option-value-row-' + element.option_value_row + '\').remove();" data-bs-toggle="tooltip" rel="tooltip" title="{{ $lang->button_remove }}" class="btn btn-danger"><i class="fa-solid fa-minus-circle"></i></button></td>';
          html += '</tr>';

          if ($(element).attr('data-option-value-row')) {
              $('#option-value-row-' + element.option_value_row).replaceWith(html);
          } else {
              $('#option-value-' + $(element).attr('data-option-row')).append(html);

              option_value_row++;
          }

          $('#modal-option').modal('hide');
      });
  });
  <?php } ?>

</script>
@endsection
