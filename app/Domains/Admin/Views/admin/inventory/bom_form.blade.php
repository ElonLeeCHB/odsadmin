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

                <div class="row mb-3 required">
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
                      <input type="text" id="input-effective_date" name="effective_date" value="{{ $bom->effective_date_ymd }}" class="form-control date" />
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
                  <label for="input-total" class="col-sm-2 col-form-label">成本</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <input type="text" id="input-total" name="total" value="{{ $bom->total }}" class="form-control">
                    </div>
                    <div id="error-total" class="invalid-feedback"></div>
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
                        <td class="text-left required">品名</td>
                        <td class="text-left">規格</td>
                        <td class="text-left">廠商</td>
                        <td class="text-right required">用量</td>
                        <td class="text-right">用量單位</td>
                        <td class="text-right">單位成本</td>
                        <td class="text-right">成本</td>
                        <td></td>
                      </tr>
                    </thead>
                    <tbody>
                      @php $product_row = 1; @endphp
                      @foreach($bom_products as $bom_product)
                      <tr id="bom-row{{ $product_row }}" data-rownum="{{ $product_row }}">
                        <td class="text-left">

                          <div class="container input-group col-sm-12">
                            <div class="col-sm-3">
                              <input type="text" id="input-products-sub_product_id-{{ $product_row }}" name="products[{{ $product_row }}][sub_product_id]" value="{{ $bom_product->sub_product_id ?? '' }}" class="form-control" readonly>
                            </div>
                            <div class="col-sm-8">
                              <input type="text" id="input-products-sub_product_name-{{ $product_row }}" name="products[{{ $product_row }}][sub_product_name]" value="{{ $bom_product->sub_product_name ?? '' }}" data-rownum="{{ $product_row }}" class="form-control schProductName" data-oc-target="autocomplete-sub_product_name-{{ $product_row }}" autocomplete="off">
                              <ul id="autocomplete-sub_product_name-{{ $product_row }}" class="dropdown-menu"></ul>
                            </div>
                            <div class="col-sm-1">
                              <div class="input-group-append">
                                <a href="{{ $bom_product->product_edit_url ?? '' }}" class="btn btn-outline-secondary" target="_blank"><i class="fas fa-external-link-alt"></i></a>
                              </div>
                            </div>
                          </div>

                          {{-- bom表的主索引 id --}}
                          <input type="hidden" id="input-products-id-{{ $product_row }}" name="products[{{ $product_row }}][id]" value="{{ $bom_product->id ?? '' }}"  readonly>
                          
                          {{-- bom表的主件 product_id --}}
                          <input type="hidden" id="input-products-product_id-{{ $product_row }}" name="products[{{ $product_row }}][product_id]" value="{{ $bom_product->product_id ?? '' }}"  readonly>
                        </td>
                        <td class="text-right"><input type="text" id="input-products-sub_product_specification-{{ $product_row }}" name="products[{{ $product_row }}][sub_product_specification]" value="{{ $bom_product->sub_product_specification }}" class="form-control" disabled/></td>
                        <td class="text-right"><input type="text" id="input-products-sub_product_supplier_short_name-{{ $product_row }}" name="products[{{ $product_row }}][sub_product_supplier_short_name]" value="{{ $bom_product->sub_product_supplier_short_name ?? '' }}" class="form-control" disabled/></td>
                        <td class="text-right"><input type="text" id="input-products-quantity-{{ $product_row }}" name="products[{{ $product_row }}][quantity]" value="{{ $bom_product->quantity }}" class="form-control" onkeyup="calcSubProduct('{{ $product_row }}');" /></td>
                        <td class="text-right">
                          <input type="text" id="input-products-usage_unit_name-{{ $product_row }}" name="products[{{ $product_row }}][usage_unit_name]" value="{{ $bom_product->usage_unit_name }}" class="form-control" readonly="readonly" />
                          <input type="hidden" id="input-products-usage_unit_code-{{ $product_row }}" name="products[{{ $product_row }}][usage_unit_code]" value="{{ $bom_product->usage_unit_code }}" class="form-control" readonly="readonly" />
                        </td>
                        <td class="text-right"><input type="text" id="input-products-usage_price-{{ $product_row }}" name="products[{{ $product_row }}][usage_price]" value="{{ $bom_product->usage_price }}" class="form-control" readonly /></td>
                        <td class="text-right"><input type="text" id="input-products-amount-{{ $product_row }}" name="products[{{ $product_row }}][amount]" value="{{ $bom_product->amount }}" class="form-control" readonly /></td>
                        <td class="text-left">
                          <button type="button" onclick="$('#bom-row{{ $product_row }}').remove();" data-toggle="tooltip" title="" class="btn btn-danger" data-original-title="Remove"><i class="fa fa-minus-circle"></i></button>
                        </td>
                      </tr>
                      @php $product_row++; @endphp
                      @endforeach

                    </tbody>

                    <tfoot>
                      <tr>
                        <td colspan="7"></td>
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

  // 查料件名稱
  $(document).on('click', '.schProductName', function() {
    $(this).autocomplete({
      'source': function (request, response) {
        $.ajax({
            url: "{{ $product_autocomplete_url }}?equal_is_active=1&filter_name=" + encodeURIComponent(request)+'&extra_columns=usage_unit_name,supplier_name&with=product_units',
            dataType: 'json',
            success: function (json) {
              response(json);
            }
          });
      },
      'select': function (item) {
        var rownum = $(this).closest('[data-rownum]').data("rownum");
        //console.log(JSON.stringify(item));
        $('#input-products-sub_product_id-'+rownum).val(item.product_id);
        $('#input-products-sub_product_name-'+rownum).val(item.name);
        $('#input-products-sub_product_specification-'+rownum).val(item.specification);
        $('#input-products-quantity-'+rownum).val(item.quantity);
        $('#input-products-usage_unit_code-'+rownum).val(item.usage_unit_code);
        $('#input-products-usage_unit_name-'+rownum).val(item.usage_unit_name);
        $('#input-products-sub_product_edit_url-'+rownum).attr('href', item.product_edit_url);
        $('#input-products-sub_product_supplier_short_name-'+rownum).val(item.supplier_short_name);
        
      }
    });
  });

});

var product_row = {{ $product_row }};

function addBOM() {
	html  = '<tr id="bom-row'+product_row+'" data-rownum="'+product_row+'">';
  html += '  <td>';

  html += '    <div class="container input-group col-sm-12">';
  html += '      <div class="col-sm-3">';
  html += '        <input type="text" id="input-products-sub_product_id-'+product_row+'" name="products['+product_row+'][sub_product_id]" value="" class="form-control" readonly>';
  html += '      </div>';
  html += '      <div class="col-sm-8">';
  html += '        <input type="text" id="input-products-sub_product_name-'+product_row+'" name="products['+product_row+'][sub_product_name]" value="" data-rownum="'+product_row+'" class="form-control schProductName" data-oc-target="autocomplete-product_name-'+product_row+'" autocomplete="off">';
  html += '        <ul id="autocomplete-product_name-'+product_row+'" class="dropdown-menu"></ul>';
  html += '      </div>';
  html += '      <div class="col-sm-1">';
  html += '        <div class="input-group-append">';
  html += '          <a href="javascript:void(0);" id="input-products-sub_product_edit_url-'+product_row+'" class="btn btn-outline-secondary"><i class="fas fa-external-link-alt"></i></a>';
  html += '        </div>';
  html += '      </div>';
  html += '    </div>';
  html += '  </td>';
  html += '  <td><input type="text" id="input-products-sub_product_specification-'+product_row+'" name="products['+product_row+'][sub_product_specification]" value="" class="form-control schProductName" autocomplete="off"></td>';
  html += '  <td><input type="text" id="input-products-sub_product_supplier_short_name-'+product_row+'" name="products['+product_row+'][sub_product_supplier_short_name]" value="" class="form-control" autocomplete="off"></td>';
  html += '  <td><input type="text" id="input-products-quantity-'+product_row+'" name="products['+product_row+'][quantity]" value="" class="form-control schProductName" autocomplete="off"></td>';
  html += '  <td><input type="text" id="input-products-usage_unit_name-'+product_row+'" name="products['+product_row+'][usage_unit_name]" value="" class="form-control schProductName" autocomplete="off"></td>';
  html += '    <input type="hidden" id="input-products-usage_unit_code-'+product_row+'" name="products['+product_row+'][usage_unit_code]" value="" class="form-control schProductName" autocomplete="off">';
  html += '  </td>'
  html += '  <td><input type="text" id="input-products-amount-'+product_row+'" name="products['+product_row+'][amount]" value="" class="form-control schProductName" autocomplete="off"></td>';
  html += '  <td class="text-left"><button type="button" onclick="$(\'#bom-row'+product_row+'\').remove();" data-toggle="tooltip" title="" class="btn btn-danger" data-original-title="Remove"><i class="fa fa-minus-circle"></i></button></td>';
  html += '</tr>';
  
	$('#bom tbody').append(html);

	product_row++;
}

$('#asdf').keyup(function(){

});
function calcSubProduct(row) {
  //products[{{ $product_row }}][sub_product_name]
  var price = $('#input-products-usage_price-' + row).val();
  var quantity = $('#input-products-quantity-' + row).val();
  var amount = (price * quantity).toFixed(3);
  $('#input-products-amount-' + row).val(amount);
  calcBom();
}
function calcBom(){
  var total = 0;
  $("[name^='products'][name$='[amount]']").each(function() {
    var value = parseFloat($(this).val()) || 0;
    total += value;
  });
  $('#input-total').val(total);
}

/*
function calcBOMvalue(name, row) {
				var bom_qty = $('input[name=\'product_' + name + '[' + row + '][quantity]\']').val();
				bom_qty = Number(bom_qty).toFixed(2);
				
				var bom_price = $('select[name=\'product_' + name + '[' + row + '][product_id]\']').val();
				bom_price = bom_price.split(':');
				bom_price = bom_price[1];
				bom_price = parseFloat(bom_price).toFixed(4);
				var value = parseFloat(bom_price * bom_qty).toFixed(4);
				calcBOMvalues();
				$('input[name=\'product_' + name + '[' + row + '][value]\']').val(value).trigger('change');
				calcBOMvalueMakingCharge(name, row);
			}
			
function calcBOMvalueMakingCharge(name, row) {
				var value = $('input[name=\'product_' + name + '[' + row + '][value]\']').val();
				value = Number(value).toFixed(4);
				
				var making_charge_percent = $('input[name=\'product_' + name + '[' + row + '][making_charge_percent]\']').val();
				making_charge_percent = Number(making_charge_percent).toFixed(2);
				var making_charge = value * (making_charge_percent/100);
				making_charge = Number(making_charge).toFixed(4);
				calcBOMvalues();
				$('input[name=\'product_' + name + '[' + row + '][making_charge]\']').val(making_charge).trigger('change');
			}

function calcBOMvalues(){
$("#bom input[name*='making_charge']").keyup(function(){
    var making_charge_sum = 0;
    $("#bom input[name*='making_charge']").each(function() {
		if(this.name.indexOf('making_charge_percent') == -1){
		var price = $(this).val();
        making_charge_sum += Number(price);
		}
    });
	making_charge_sum = making_charge_sum.toFixed(4);
	$('#input-bom_making_charge').val(making_charge_sum);
	var bom_price = $('#input-bom_price').val();
	bom_price = Number(bom_price).toFixed(4);
	price = Number(making_charge_sum) + Number(bom_price);
	$('#input-price').val(price.toFixed(4)).trigger('change');
});
$("#bom input[name*='making_charge']").change(function(){
    var making_charge_sum = 0;
    $("#bom input[name*='making_charge']").each(function() {
		if(this.name.indexOf('making_charge_percent') == -1){
		var price = $(this).val();
        making_charge_sum += Number(price);
		}
    });
	making_charge_sum = making_charge_sum.toFixed(4);
	$('#input-bom_making_charge').val(making_charge_sum);
	var bom_price = $('#input-bom_price').val();
	bom_price = Number(bom_price).toFixed(4);
	price = Number(making_charge_sum) + Number(bom_price);
	$('#input-price').val(price.toFixed(4)).trigger('change');
});
$("#bom input[name*='value']").change(function(){
    var bom_price_sum = 0;
    $("#bom input[name*='value']").each(function() {
	if(this.name.indexOf('making_charge_value') == -1){
		var price = $(this).val();
        bom_price_sum += Number(price);
	}
    });
	bom_price_sum = bom_price_sum.toFixed(4);
	$('#input-bom_price').val(bom_price_sum);
	var making_charge_value = $('#input-bom_making_charge').val();
	making_charge_value = Number(making_charge_value).toFixed(4);
	price = Number(bom_price_sum) + Number(making_charge_value);
	$('#input-price').val(price.toFixed(4)).trigger('change');
});
}
*/
</script>
@endsection
