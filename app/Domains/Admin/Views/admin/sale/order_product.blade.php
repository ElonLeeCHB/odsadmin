
<tr id="product-row-{{ $product_row }}" data-product-row="{{ $product_row }}">
  <td class="align-top" style="width:200px;padding-right:3px;">
    <div class="input-group">
      <select id="input-product-{{ $product_row }}-product_id" name="order_products[{{ $product_row }}][product_id]" onchange="getProductDetails(this)">
        <option>--</option>
        @foreach($salable_products as $product)
          <option value="{{ $product->id }}" @if($product->id == $selected_product_id) selected @endif>{{ $product->name }}</option>
        @endforeach
      </select>
      <div class="input-group-append">
        <button type="button" class="btn btn-danger" onclick="removeProduct({{ $product_row }});" title="移除" style="font-size:10px"><i class="fa fa-minus-circle"></i></button>
      </div>
    </div>
    
    <input type="hidden" name="order_products[{{ $product_row }}][order_product_id]" value="{{ $order_product_id ?? '' }}">
    <input type="hidden" id="input-product-{{ $product_row }}-main_category_code"  name="order_products[{{ $product_row }}][main_category_code]" value="{{ $main_category_code }}">
    <input type="hidden" id="input-product-{{ $product_row }}-model" name="order_products[{{ $product_row }}][model]" value="{{ $model }}">

    <div class="input-group" style="margin-top:10px;">
      <!--{{--
      <label for="product-row-{{ $product_row }}-hidden_option">顯示選項</label>&nbsp;
      <input type="checkbox" id="product-row-{{ $product_row }}-hidden_option" name="switch_hidden_option" value="0" class="form-check-input" data-element="switch_hidden_option">&nbsp;&nbsp;
      --}}-->
      順序 <input type="text" id="input-product-{{ $product_row }}-sort_order" name="order_products[{{ $product_row }}][sort_order]" value="{{ $product_row }}" style="width:40px;" maxlength="2" >
    </div>
  </td>
  <td colspan="8" style="padding: 0px;" id="product-row-{{ $product_row }}-options" class="align-top">
    {{--選項--}}
    {!! $product_options_html ?? '' !!}

    {{--end 選項--}}
  </td>
  <td style="width:200px;">
    <table class="orderTotal">
      <tr>
        <td class="text-end">數量</td>
        <td class="text-end">單價</td>
        <td class="text-end">金額</td>
      </tr>
      <tr>
        <td class="text-end align-top" style="width:65px;">
          <input type="text" id="input-product-{{ $product_row }}-quantity" name="order_products[{{ $product_row }}][quantity]" value="{{ $quantity ?? 1}}" class="form-control text-start" data-element="quantity">

          {{-- 為了讓接單人員可以在接電話當下，輸入總數200份，某主餐選2，不用把主餐湊足200。所以新增此隱藏欄位，單純計算當前主餐加總，不要加到商品數量。待空閒時再分配主餐。 --}}
          <input type="hidden" id="input-product-{{ $product_row }}-main_meal_quantity" name="order_products[{{ $product_row }}][main_meal_quantity]" value="0" class="form-control text-end" data-element="main_meal_quantity">
          <input type="hidden" id="input-product-{{ $product_row }}-main_meal_quantity_no_veg" name="order_products[{{ $product_row }}][main_meal_quantity_no_veg]" value="0">
        </td>
        <td class="text-end align-top" style="width:65px;">
          <input type="text" id="input-product-{{ $product_row }}-price" name="order_products[{{ $product_row }}][price]" value="{{ $price }}" class="form-control text-start" data-element="price">
        </td>
        <td class="text-end align-top" style="width:65px;">
          <input type="text" id="input-product-{{ $product_row }}-total" name="order_products[{{ $product_row }}][total]" value="{{ $total }}" readonly class="form-control text-start" data-element="order_product_total" >
        </td>
      </tr>
      <tr>
        <td>待分配數</td>
        <td class="text-end">選項金額</td>
        <td class="text-end">小計</td>
      </tr>
      <tr>
        <td><input id="input-product-{{ $product_row }}-unassigned_qty" nmae="order_products[{{ $product_row }}][unassigned_qty]" value="" class="form-control text-start"></td>
        <td><input type="text" value="{{ $options_total }}" id="input-product-{{ $product_row }}-options_total" name="order_products[{{ $product_row }}][options_total]" class="form-control text-start" ></td>
        <td><input type="text" value="{{ $final_total }}" id="input-product-{{ $product_row }}-final_total" name="order_products[{{ $product_row }}][final_total]" class="form-control text-start" style="background-color:lightgreen;"" ></td>
      </tr>
    </table>
  </td>


</tr>
