@php $product_row = 1; @endphp
<table id="products" class="table table-striped table-bordered table-hover">
  <thead>
    <tr style="z-index:1;">
      <td class="text-left"></td>
      <td class="text-left"></td>
      <td class="text-left">料件流水號</td>
      <td class="text-left">品名</td>
      <td class="text-left">規格</td>
      <td class="text-left" style="width:100px;">庫存單位</td>
      <td class="text-left" style="width:100px;">盤點單位</td>
      <td class="text-left" style="width:100px;">盤點單價</td>
      <td class="text-left" style="width:100px;">盤點數量</td>
      <td class="text-left" style="width:100px;">盤點金額</td>
    </tr>
  </thead>
  <tbody>
    @foreach($counting_products as $counting_product)
    <tr id="product-row{{ $product_row }}" data-rownum="{{ $product_row }}">
      <td class="text-left">
        <button type="button" onclick="$('#product-row{{ $product_row }}').remove();" data-toggle="tooltip" title="" class="btn btn-danger" data-original-title="Remove"><i class="fa fa-minus-circle"></i></button>
      </td>
      <td class="text-right">{{ $product_row }}</td>
      <td class="text-left">
        <div class="col-sm-12">
          <div class="input-group" style="display: flex; align-items: center;">
            <input type="text" id="input-products-id-{{ $product_row }}" name="products[{{ $product_row }}][id]" value="{{ $counting_product->product_id ?? '' }}" class="form-control" readonly>
            <a href="{{ $counting_product->product_edit_url }}" target="_blank" title="料件基本資料" class="btn btn-light"><i class="fas fa-external-link-alt"></i></a>
          </div>

        </div>
      </td>
      <td class="text-left">
        <input type="text" id="input-products-name-{{ $product_row }}" name="products[{{ $product_row }}][name]" value="{{ $counting_product->product_name ?? '' }}" data-rownum="{{ $product_row }}" class="form-control schProductName" data-oc-target="autocomplete-product_name-{{ $product_row }}" autocomplete="off">
        <ul id="autocomplete-product_name-{{ $product_row }}" class="dropdown-menu"></ul>
      </td>
      <td class="text-left">
        <input type="text" id="input-products-specification-{{ $product_row }}" name="products[{{ $product_row }}][specification]" value="{{ $counting_product->product_specification ?? '' }}" class="form-control" readonly>
      </td>
      <td class="text-left">
        <input type="text" id="input-products-stock_unit_name-{{ $product_row }}" name="products[{{ $product_row }}][stock_unit_name]" value="{{ $counting_product->stock_unit_name ?? '' }}" class="form-control" readonly>
      </td>
      <td class="text-left">
        <input type="text" id="input-products-unit_name-{{ $product_row }}" name="products[{{ $product_row }}][unit_name]" value="{{ $counting_product->unit_name ?? '' }}" class="form-control" readonly>
      </td>
      <td class="text-left">
        <input type="text" id="input-products-price-{{ $product_row }}" name="products[{{ $product_row }}][price]" value="{{ $counting_product->price ?? 0 }}" class="form-control productPriceInputs clcProduct" data-rownum="{{ $product_row }}">
      </td>
      <td class="text-left">
        <input type="text" id="input-products-quantity-{{ $product_row }}" name="products[{{ $product_row }}][quantity]" value="{{ $counting_product->quantity }}" class="form-control productPriceInputs clcProduct" data-rownum="{{ $product_row }}">
      </td>
      <td class="text-left">
        <input type="text" id="input-products-amount-{{ $product_row }}" name="products[{{ $product_row }}][amount]" value="{{ $counting_product->amount ?? 0 }}" class="form-control productAmountInputs" data-rownum="{{ $product_row }}" readonly>
      </td>
    </tr>
    @php $product_row++; @endphp
    @endforeach
  </tbody>
  <tfoot>
    <tr>
      <td colspan="10" class="text-left">
        <script> product_row = {{ $product_row }}</script>
        <button type="button" onclick="addCountingProduct(product_row)" data-toggle="tooltip" title="" class="btn btn-primary" data-original-title=""><i class="fa fa-plus-circle"></i></button>
      </td>
    </tr>
  </tfoot>
</table>

<script type="text/javascript">
function addCountingProduct(){
  let html = '';
  html += '<tr id="product-row'+product_row +'" data-rownum="'+product_row +'">';
  html += '  <td class="text-left">';
  html += '    <button type="button" onclick="$(\'#product-row'+product_row +'\').remove();" data-toggle="tooltip" title="" class="btn btn-danger" data-original-title="Remove"><i class="fa fa-minus-circle"></i></button>';
  html += '  </td>';
  html += '  <td class="text-right">'+product_row +'</td>';
  html += '  <td class="text-left">';
  html += '    <input type="text" id="input-products-id-'+product_row +'" name="products['+product_row +'][id]" value="" data-rownum="'+product_row +'" class="form-control" readonly>';
  html += '  </td>';
  html += '  <td class="text-left">';
  html += '    <input type="text" id="input-products-name-'+product_row +'" name="products['+product_row +'][name]" value="" data-rownum="'+product_row +'" class="form-control schProductName" data-oc-target="autocomplete-product_name-'+product_row +'" autocomplete="off">';
  html += '    <ul id="autocomplete-product_name-'+product_row +'" class="dropdown-menu"></ul>';
  html += '  </td>';
  html += '  <td class="text-left">';
  html += '    <input type="text" id="input-products-specification-'+product_row +'" name="products['+product_row +'][specification]" value="" class="form-control" readonly>';
  html += '  </td>';
  html += '  <td class="text-left">';
  html += '    <input type="text" id="input-products-stock_unit_name-'+product_row +'" name="products['+product_row +'][stock_unit_name]" value="" class="form-control" readonly>';
  html += '  </td>';
  html += '  <td class="text-left">';
  html += '    <input type="text" id="input-products-unit_name-'+product_row +'" name="products['+product_row +'][unit_name]" value="" class="form-control" readonly>';
  html += '  </td>';
  html += '  <td class="text-left">';
  html += '    <input type="text" id="input-products-price-'+product_row +'" name="products['+product_row +'][price]" value="" class="form-control productPriceInputs clcProduct" data-rownum="'+product_row +'">';
  html += '  </td>';
  html += '  <td class="text-left">';
  html += '    <input type="text" id="input-products-quantity-'+product_row +'" name="products['+product_row +'][quantity]" value="" class="form-control productPriceInputs clcProduct" data-rownum="'+product_row +'">';
  html += '  </td>';
  html += '  <td class="text-left">';
  html += '    <input type="text" id="input-products-amount-'+product_row +'" name="products['+product_row +'][amount]" value="" class="form-control productAmountInputs" data-rownum="'+product_row +'" readonly>';
  html += '  </td>';
  html += '</tr>';

  $('#products tbody').append(html);

  product_row++;
}
</script></button>
        </td>
      </tr>
    </tfoot>
  </table>
