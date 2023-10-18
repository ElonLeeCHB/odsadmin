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
        <button type="submit" form="form-category" data-bs-toggle="tooltip" title="儲存" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="{{ $back_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_back }}" class="btn btn-light"><i class="fas fa-reply"></i></a>
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
          </ul>
          <form id="form-category" action="{{ $save_url }}" method="post" data-oc-toggle="ajax">
            @csrf
            @method('POST')

            <div class="tab-content">

              <div id="tab-data" class="tab-pane active">

                <div class="row mb-3">
                  <label for="input-product_id" class="col-sm-2 col-form-label">{{ $lang->column_product_name }}</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <div class="col-sm-2">
                        <input type="text" id="input-product_id" name="product_id" value="{{ $bom->product_id ?? 0 }}" class="form-control" readonly=""/>
                        <div class="form-text">料件流水號</div>
                        <div id="error-product_id" class="invalid-feedback"></div>
                      </div>
                      <div class="col-sm-10">
                        <input type="text" id="input-product_name" name="product_name" value="{{ $bom->product_name }}" class="form-control" data-oc-target="autocomplete-product_name"/>
                        <ul id="autocomplete-product_name" class="dropdown-menu"></ul>
                        <div class="form-text">品名 (可查詢，至少輸入一個字)</div>
                      </div>
                    </div>
                  </div>
                </div>














                <div class="row mb-3">
                  <label for="input-product_id" class="col-sm-2 col-form-label">生效日期</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <div class="col-sm-5">
                      <input type="text" id="input-effective_date" name="effective_date" value="{{ $bom->effective_date }}" class="form-control date" />
                        <div class="form-text">生效日期</div>
                        <div id="error-product_id" class="invalid-feedback"></div>
                      </div>
                      <div class="col-sm-5">
                        <input type="text" id="input-expiry_date" name="expiry_date" value="{{ $bom->expiry_date }}" class="form-control date" />
                        <ul id="autocomplete-product_name" class="dropdown-menu"></ul>
                        <div class="form-text">失效日期</div>
                      </div>
                    </div>
                  </div>
                </div>




                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label">{{ $lang->column_enable }}</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <div id="input-is_active" class="form-check form-switch form-switch-lg">
                        <input type="hidden" name="is_active" value="0"/>
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" @if($bom->is_active) checked @endif/>
                      </div>
                    </div>
                  </div>
                </div>


                <input type="hidden" id="input-bom_id" name="bom_id" value="{{ $bom_id }}"/>


                {{-- 單身元件 --}}
                <div class="table-responsive">
                  <table id="bom" class="table table-striped table-bordered table-hover">
                    <thead>
                      <tr>
                        <td class="text-left">品名</td>
                        <td class="text-left">規格</td>
                        <td class="text-right">用量</td>
                        <td class="text-right">用量單位</td>
                        <td class="text-right">成本</td>
                        <td></td>
                      </tr>
                    </thead>
                    <tbody>
                      @php $bom_product_row = 1; @endphp
                      @foreach($bom_products as $bom_product)
                      <tr id="bom-row-{{ $bom_product_row }}" data-rownum="{{ $bom_product_row }}">
                        <td class="text-left">
                          <input type="hidden" id="input-subproducts-id-{{ $bom_product_row }}" name="sub_product[{{ $bom_product_row }}][id]" value="{{ $bom_product->product_id ?? '' }}" class="form-control" readonly>

                          <input type="text" id="input-subproducts-name-{{ $bom_product_row }}" name="sub_product[{{ $bom_product_row }}][name]" value="{{ $bom_product->product_name }}" data-rownum="{{ $bom_product_row }}" class="form-control schProductName" data-oc-target="autocomplete-bom_product_name-{{ $bom_product_row }}" autocomplete="off">
                          <ul id="autocomplete-bom_product_name-{{ $bom_product_row }}" class="dropdown-menu"></ul>
                        </td>
                        <td class="text-right"><input type="text" id="input-subproducts-specification-{{ $bom_product_row }}" name="sub_product[{{ $bom_product_row }}[product_specification]" alue="{{ $bom_product->product_specification }}" class="form-control" /></td>
                        <td class="text-right"><input type="text" id="input-subproducts-quantity-{{ $bom_product_row }}" name="sub_product[{{ $bom_product_row }}][quantity]" value="{{ $bom_product->quantity }}" class="form-control" onkeyup="calcBOMvalue('bom', '0');" /></td>
                        <td class="text-right"><input type="text" id="input-subproducts-unit_code-{{ $bom_product_row }}" name="sub_product[{{ $bom_product_row }}][value]" value="{{ $bom_product->unit_code }}" placeholder="Value" class="form-control" readonly="readonly" /></td>
                        <td class="text-right"><input type="text" id="input-subproducts-cost-{{ $bom_product_row }}" name="sub_product[{{ $bom_product_row }}][making_charge]" value="{{ $bom_product->cost }}" placeholder="Making Charge" class="form-control" onkeyup="priceReadOnly();" /></td>
                        <td class="text-left">
                          <button type="button" onclick="$('#bom-row0').remove(); priceNotReadOnly();" data-toggle="tooltip" title="" class="btn btn-danger" data-original-title="Remove"><i class="fa fa-minus-circle"></i></button>
                        </td>
                      </tr>
                      @php $bom_product_row++; @endphp
                      @endforeach

                    </tbody>

                    <tfoot>
                      <tr>
                        <td colspan="5"></td>
                        <td class="text-left">
                          <button type="button" onclick="addBOM(); calcBOMvalues();" data-toggle="tooltip" title="" class="btn btn-primary" data-original-title=""><i class="fa fa-plus-circle"></i></button>
                        </td>
                      </tr>
                    </tfoot>
                  </table>
                </div>

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

$(document).ready(function() {
  $('#input-product_name').autocomplete({
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
      $('#input-product_id').val(item['product_id']);
      $('#input-product_name').val(item['name']);
    }
  });

  // 查元件料件名稱
  $('.schProductName').on('click', function(){
    $(this).autocomplete({
      'source': function (request, response) {
        $.ajax({
            url: "{{ $product_autocomplete_url }}?equal_is_active=1&filter_name=" + encodeURIComponent(request)+'&extra_columns=specification,usage_unit_code_name',
            dataType: 'json',
            success: function (json) {
              response(json);
            }
          });
      },
      'select': function (item) {
        var rownum = $(this).closest('[data-rownum]').data("rownum");
        $('#input-subproducts-id-'+rownum).val(item.product_id);
        $('#input-subproducts-name-'+rownum).val(item.name);
        $('#input-subproducts-specification-'+rownum).val(item.specification);
        $('#input-subproducts-quantity-'+rownum).val(item.quantity);
        $('#input-subproducts-usage_unit_code-'+rownum).val(item.usage_unit_code);
        $('#input-subproducts-usage_unit_code_name-'+rownum).val(item.usage_unit_code_name);
      }
    });
  });

});



</script>
@endsection
