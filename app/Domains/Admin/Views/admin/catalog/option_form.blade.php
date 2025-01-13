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
        <button type="submit" form="form-option" data-bs-toggle="tooltip" title="Save" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i></button>
        <a href="{{ $back_url }}" data-bs-toggle="tooltip" title="Back" class="btn btn-light"><i class="fa-solid fa-reply"></i></a></div>
        <h1>{{ $lang->heading_title }}</h1>
        @include('admin.common.breadcumb')
    </div>
  </div>
  <div class="container-fluid">
    <div class="card">
      <div class="card-header"><i class="fa-solid fa-pencil"></i> {{ $lang->text_form }}</div>
      <div class="card-body">
        <form id="form-option" action="{{ $save_url }}" method="post" data-oc-toggle="ajax">
          @csrf
          @method('POST')
          <input type="hidden" name="option_id" value="{{ $option->id }}" id="input-option-id">
          <fieldset>
            <legend>{{ $lang->text_option }}</legend>

            <div class="row mb-3 required">
              <label class="col-sm-2 col-form-label">{{ $lang->entry_name }}</label>
              <div class="col-sm-10">
                @foreach($languages as $language)
                  <input type="hidden" name="option_translations[{{ $language->code }}][id]" value="{{ $option_translations[$language->code]['id'] ?? ''  }}" >

                  <div class="input-group">
                    <div class="input-group-text">{{ $language->native_name }}</div>
                    <input type="text" name="option_translations[{{ $language->code }}][name]" value="{{ $option_translations[$language->code]['name'] ?? '' }}" id="input-name-{{ $language->code }}" class="form-control"/>
                  </div>
                  <div id="error-name-{{ $language->code }}" class="invalid-feedback"></div>
                @endforeach
              </div>
            </div>

            <div class="row mb-3">
              <label for="input-type" class="col-sm-2 col-form-label">{{ $lang->entry_type }}</label>
              <div class="col-sm-10">
              <select name="type" id="input-type" class="form-select">
                  <optgroup label="{{ $lang->text_choose }}">
                    <option value="select" @if($option->type == 'select') selected @endif >{{ $lang->text_select }}</option>
                    <option value="radio" @if($option->type == 'radio') selected @endif >{{ $lang->text_radio }}</option>
                    <option value="checkbox" @if($option->type == 'checkbox') selected @endif >{{ $lang->text_checkbox }}</option>
                    <option value="options_with_qty" @if($option->type == 'options_with_qty') selected @endif >{{ $lang->text_options_with_qty }}</option>

                  </optgroup>
                  <optgroup label="{{ $lang->text_input }}">
                    <option value="text" @if($option->type == 'text') selected @endif >{{ $lang->text_text }}</option>
                    <option value="textarea" @if($option->type == 'textarea') selected @endif >{{ $lang->text_textarea }}</option>
                  </optgroup>
                  <optgroup label="{{ $lang->text_file }}">
                    <option value="file" @if($option->type == 'file') selected @endif >{{ $lang->text_file }}</option>
                  </optgroup>
                  <optgroup label="{{ $lang->text_date }}">
                    <option value="date" @if($option->type == 'date') selected @endif >{{ $lang->text_date }}</option>
                    <option value="time" @if($option->type == 'time') selected @endif >{{ $lang->text_time }}</option>
                    <option value="datetime" @if($option->type == 'datetime') selected @endif >{{ $lang->text_datetime }}</option>
                  </optgroup>
                </select>
              </div>
            </div>
            
            <div class="row mb-3">
              <label for="input-code" class="col-sm-2 col-form-label">編碼</label>
              <div class="col-sm-10">
                <input type="text" name="code" value="{{ $option->code }}" placeholder="本欄請由開發人員修改" id="input-code" class="form-control">
              </div>
            </div>

            <div class="row mb-3">
              <label for="input-note" class="col-sm-2 col-form-label">備註</label>
              <div class="col-sm-10">
                <input type="text" id="input-note" name="note" value="{{ $option->note }}" placeholder="備註" class="form-control">
              </div>
            </div>
          </fieldset>

          {{-- 選項值 --}}
          <fieldset>
            <legend>{{ $lang->text_value }}</legend>
            <table id="option-value" class="table table-bordered table-hover">
              <thead>
                <tr>
                  <td style="width: 50px;">項次</td>
                  <td>ID</td>
                  <td class="text-start required">{{ $lang->entry_option_value_name }}</td>
                  <td>簡稱</td>
                  <td>官網名稱</td>
                  <td>官網使用</td>
                  <td style="width: 80px;" class="text-end">{{ $lang->entry_sort_order }}</td>
                  <td>對應商品</td>
                  <td>是否啟用</td>
                  <td></td>
                </tr>
              </thead>
              <tbody>
                <?php $option_value_row = 1; ?>
                @foreach($option_values as $option_value)
                <tr id="option-value-row-{{ $option_value_row }}">
                  <td>{{ $option_value_row }}</td>
                  <td class="text-end">{{ $option_value->id }}</td>
                  <td class="text-center">
                    <input type="hidden" name="option_values[{{ $option_value_row }}][option_value_id]" value="{{ $option_value->id }}">
                    @foreach($languages as $language)
                    <div class="input-group">
                      <div class="input-group-text">{{ $language->native_name }}</div>
                      <input type="text" name="option_values[{{ $option_value_row }}][option_value_translations][{{ $language->code }}][name]" value="{{ $option_value->name }}" placeholder="{{ $lang->entry_option_value_name }}" id="input-option-value-{{ $option_value_row }}-{{ $language->code }}" class="form-control">
                    </div>
                    <div id="error-option-value-{{ $option_value_row }}-{{ $language->code }}" class="invalid-feedback"></div>
                    @endforeach
                  </td>
                  <td>
                    @foreach($languages as $language)
                    <div class="input-group">
                      <div class="input-group-text">{{ $language->native_name }}</div>
                      <input type="text" name="option_values[{{ $option_value_row }}][option_value_translations][{{ $language->code }}][short_name]" value="{{ $option_value->short_name }}" id="input-option-value-{{ $option_value_row }}-{{ $language->code }}-short_name" class="form-control">
                    </div>
                    <div id="error-option-value-{{ $option_value_row }}-{{ $language->code }}" class="invalid-feedback"></div>
                    @endforeach
                  </td>
                  <td>
                    @foreach($languages as $language)
                    <div class="input-group">
                      <div class="input-group-text">{{ $language->native_name }}</div>
                      <input type="text" name="option_values[{{ $option_value_row }}][option_value_translations][{{ $language->code }}][web_name]" value="{{ $option_value->web_name }}" id="input-option-value-{{ $option_value_row }}-{{ $language->code }}-web_name" class="form-control">
                    </div>
                    <div id="error-option-value-{{ $option_value_row }}-{{ $language->code }}" class="invalid-feedback"></div>
                    @endforeach
                  </td>
                  <td>
                    <select id="input-is_on_www" name="option_values[{{ $option_value_row }}][is_on_www]">
                      <option value="1" @if($option_value->is_on_www == 1 ) selected @endif>是</option>
                      <option value="0" @if($option_value->is_on_www != 1 ) selected @endif>否</option>
                  </td>
                  <td class="text-end"><input type="text" name="option_values[{{ $option_value_row }}][sort_order]" value="{{ $option_value->sort_order }}" class="form-control text-end"></td>
                  <td>
                    <input type="text" data-rownum="{{ $option_value_row }}" id="input-option_values-{{ $option_value_row }}-product_name" name="option_values[{{ $option_value_row }}][product_name]" value="{{ $option_value->product->name ?? '' }}" autocomplete="off" placeholder="{{ $lang->entry_name }}" data-oc-target="autocomplete-product_name" class="form-control option_value_product"/>
                    <ul id="autocomplete-product_name" class="dropdown-menu"></ul>
                    <input type="hidden" id="input-option_values-{{ $option_value_row }}-product_id" name="option_values[{{ $option_value_row }}][product_id]" value="{{ $option_value->product_id ?? '' }}">
                    <input type="hidden" id="input-option_values-{{ $option_value_row }}-option_id" name="option_values[{{ $option_value_row }}][option_id]" value="{{ $option->id ?? '' }}">
                  </td>
                  <td>
                    <select id="input-is_active" name="option_values[{{ $option_value_row }}][is_active]">
                      <option value="1" @if($option_value->is_active == 1 ) selected @endif>是</option>
                      <option value="0" @if($option_value->is_active != 1 ) selected @endif>否</option>
                  </td>
                  <td class="text-end"><button type="button" onclick="$('#option-value-row-{{ $option_value_row }}').remove();" data-bs-toggle="tooltip" title="{{ $lang->button_remove }}" class="btn btn-danger"><i class="fa-solid fa-minus-circle"></i></button></td>
                  
                </tr>
                @php $option_value_row++; @endphp
              @endforeach
              </tbody>
              <tfoot>
                <tr>
                  <td colspan="9"></td>
                  <td class="text-end"><button type="button" onclick="addOptionValue();" data-bs-toggle="tooltip" title="{{ $lang->button_add_option_value }}" class="btn btn-primary"><i class="fa-solid fa-plus-circle"></i></button></td>
                </tr>
              </tfoot>
            </table>
          </fieldset>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection


@section('buttom')
<script type="text/javascript">
$('#input-type').on('change', function() {
    if (this.value == 'select' || this.value == 'radio' || this.value == 'checkbox' || this.value == 'options_with_qty' || this.value == 'image') {
        $('#option-value').parent().show();
    } else {
        $('#option-value').parent().hide();
    }
});
$('#input-type').trigger('change');


var option_value_row = {{ $option_value_row }};

function addOptionValue() {
    html = '<tr id="option-value-row-' + option_value_row + '">';
    html += '  <td></td>';
    html += '  <td></td>';

    html += '  <td class="text-start"><input type="hidden" name="option_values[' + option_value_row + '][option_value_id]" value="" />';
    @foreach($languages as $language)
    html += '    <div class="input-group">';
    html += '      <div class="input-group-text">{{ $language->native_name }}</div>';
    html += '      <input type="text" name="option_values[' + option_value_row + '][option_value_translations][{{ $language->code }}][name]" value="" placeholder="{{ $lang->entry_option_value_name }}" id="input-option-value-' + option_value_row + '-{{ $language->code }}-name" class="form-control">';
    html += '    </div>';
    html += '    <div id="error-option-value-' + option_value_row + '-{{ $language->code }}-name" class="invalid-feedback"></div>';
    @endforeach
    html += '  </td>';

    html += '  <td class="text-start">';
    @foreach($languages as $language)
    html += '    <div class="input-group">';
    html += '      <div class="input-group-text">{{ $language->native_name }}</div>';
    html += '      <input type="text" name="option_values[' + option_value_row + '][option_value_translations][{{ $language->code }}][short_name]" value="" placeholder="{{ $lang->entry_short_name }}" id="input-option-value-' + option_value_row + '-{{ $language->code }}" class="form-control">';
    html += '    </div>';
    html += '    <div id="error-option-value-' + option_value_row + '-{{ $language->code }}-short_name" class="invalid-feedback"></div>';
    @endforeach
    html += '  </td>';
    html += '  <td class="text-end"><input type="text" name="option_values[' + option_value_row + '][sort_order]" value="" class="form-control"></td>';
    html += '  <td></td>';
    html += '  <td>';
    html += '  </td>';
    html += '  <td class="text-end"><button type="button" onclick="$(\'#option-value-row-' + option_value_row + '\').remove();" data-bs-toggle="tooltip" title="{{ $lang->button_remove }}" class="btn btn-danger"><i class="fa-solid fa-minus-circle"></i></button></td>';
    html += '</tr>';

    $('#option-value tbody').append(html);

    option_value_row++;
}


// $('.option_value_product').autocomplete({
// 	'source': function(request, response) {
// 		$.ajax({
//       url: "{{ route('lang.admin.catalog.products.autocomplete') }}?filter_name=" + encodeURIComponent(request),
// 			dataType: 'json',
// 			success: function(json) {
//         response(json);
// 			}
// 		});
// 	},
// 	'select': function(item) {
//     var rownum = $(this).data("rownum");
//     console.log('rownum:'+rownum+', event.product_id:'+item.product_id+', product_name:'+item.label);
//     $('#input-option_values-'+rownum+'-product_id').val(item.product_id);
//     $('#input-option_values-'+rownum+'-product_name').val(item.label);
//   }
// });

$(document).ready(function() {

  $("#option-value").on("focus", ".option_value_product", function(event) {

    $('.option_value_product').autocomplete({
      'source': function(request, response) {
        $.ajax({
          url: "{{ route('lang.admin.catalog.products.autocomplete') }}?filter_name=" + encodeURIComponent(request),
          dataType: 'json',
          success: function(json) {
            response(json);
          }
        });
      },
      'select': function(item) {
        var rownum = $(this).data("rownum");
        $('#input-option_values-'+rownum+'-product_id').val(item.product_id);
        $('#input-option_values-'+rownum+'-product_name').val(item.label);
      }
    });
  });

});

</script>
@endsection

