<link  href="{{ asset('assets/stylesheet/path/sale/order_form.css') }}" rel="stylesheet" type="text/css"/>

<table class="table table-product-content" id="product-row-{{ $product_row }}-content-table">

  @if($is_main_meal_title)
  <tr>
    <td class="text-start">全素薯</td>
    <td class="text-start">蛋素薯</td>
    <td class="text-start">薯泥</td>
    <td class="text-start">炸蝦</td>
    <td class="text-start">炒雞</td>
    <td class="text-start">酥魚</td>
    <td class="text-start">培根</td>
    <td class="text-start">滷肉</td>
    <td class="text-start">呱呱卷</td>
  </tr>
  @endif

  {{-- 主餐 --}}
  @if(!empty($product_options['main_meal']))
    @php
      $poid        = $product_options['main_meal']['product_option_id'];
      $option_name = $product_options['main_meal']['option_name'];
      $option_type = $product_options['main_meal']['option_type'];
      $option_code = 'main_meal';
    @endphp
    <input type="hidden" name="order_products[{{ $product_row }}][order_product_options][{{ $poid }}][name]" value="{{ $product_options[$option_code]['option_name'] }}" >
    <input type="hidden" name="order_products[{{ $product_row }}][order_product_options][{{ $poid }}][type]" value="{{ $product_options[$option_code]['option_type'] }}">

    <tr class="tr_main_meal">
      {{-- 標準主餐 --}}
      @foreach($product_options['main_meal']['product_option_values'] as $product_option_value)
        @php
          $ovid = $product_option_value->option_value_id;
          $povid = $product_option_value->id;
          $main_meal_povids[] = $povid;
          $value = $product_option_value->name;
          $main_meal_po_names[] = $value;
          $quantity = $order_product_options[$order_product_id][$poid][$povid]['quantity'] ?? 0;
          $opoid    = $order_product_options[$order_product_id][$poid][$povid]['order_product_option_id'] ?? '';
        @endphp
        <td>
          <input type="number" name="order_products[{{ $product_row }}][order_product_options][{{ $poid }}][product_option_values][{{ $povid }}][quantity]" value="{{ $quantity }}" class="form-control input_main_meal" data-ovid="{{ $ovid }}" style="width:65px;padding:2px;">
          <input type="hidden" name="order_products[{{ $product_row }}][order_product_options][{{ $poid }}][product_option_values][{{ $povid }}][value]" value="{{ $value }}">
          <input type="hidden" name="order_products[{{ $product_row }}][order_product_options][{{ $poid }}][product_option_values][{{ $povid }}][opoid]" value="{{ $opoid }}">
        </td>
      @endforeach
    </tr>
  @endif

  {{-- 飲料 --}}
  @if(!empty($product_options['drink']) && $product_options['drink']['is_fixed'] == 1)
    @php
      $poid        = $product_options['drink']['product_option_id'];
      $option_name = $product_options['drink']['option_name'];
      $option_type = $product_options['drink']['option_type'];
      $option_code = 'drink';
      $opoid    = $order_product_options[$order_product_id][$poid][$povid]['order_product_option_id'] ?? '';
    @endphp
    <input type="hidden" name="order_products[{{ $product_row }}][order_product_options][{{ $poid }}][name]" value="{{ $product_options[$option_code]['option_name'] }}" >
    <input type="hidden" name="order_products[{{ $product_row }}][order_product_options][{{ $poid }}][type]" value="{{ $product_options[$option_code]['option_type'] }}">

    <tr class="tr_drink">
      {{-- 盒餐飲料 --}}
      @for($i = 0; $i < 9; $i++)
        <td>
        @php 
          $poid = $product_options['drink']['product_option_id'];
          $parent_povid = $main_meal_povids[$i];
          $parent_pov_name = $main_meal_po_names[$i];
        @endphp
        @foreach($product_options['drink']['product_option_values'] as $product_option_value)
          @php
            $povid = $product_option_value->id;
            $value =  $order_product_options[$order_product_id][$poid][$povid]['sub'][$parent_povid]['value'] ?? $product_option_value->name;
            $quantity = $order_product_options[$order_product_id][$poid][$povid]['sub'][$parent_povid]['quantity'] ?? 0;
          @endphp
          {{ $product_option_value->name }} 
          <input type="text"   name="order_products[{{ $product_row }}][order_product_options][{{ $poid }}][product_option_values][{{ $povid }}][parent_povid][{{ $parent_povid }}][quantity]" value="{{ $quantity }}" style="width:38px;" class="text-start input-drink"><BR>
          <input type="hidden" name="order_products[{{ $product_row }}][order_product_options][{{ $poid }}][product_option_values][{{ $povid }}][parent_povid][{{ $parent_povid }}][value]" value="{{ $value }}">
          <input type="hidden" name="order_products[{{ $product_row }}][order_product_options][{{ $poid }}][product_option_values][{{ $povid }}][parent_povid][{{ $parent_povid }}][parent_povid]" value="{{ $parent_povid }}">
          <input type="hidden" name="order_products[{{ $product_row }}][order_product_options][{{ $poid }}][product_option_values][{{ $povid }}][parent_povid][{{ $parent_povid }}][parent_pov_name]" value="{{ $parent_pov_name }}">
          <input type="hidden" name="order_products[{{ $product_row }}][order_product_options][{{ $poid }}][product_option_values][{{ $povid }}][opoid]" value="{{ $opoid }}">
        @endforeach
        </td>
      @endfor
    </tr>
  @endif
  {{-- end 飲料 --}}

  {{-- 通用選項迴圈--}}
    @foreach($loop_product_options as $product_option)
      @php 
        $poid = $product_option['product_option_id'];

        $tr_hiddable_option = ''; //用於定位tr
        
        $dnone = '';
        if($product_option['is_hidden'] == 1){
          $tr_hiddable_option = ' tr_hiddable_option';
          $dnone = ' d-none';
        }
        $dnone = ''; //上面的隱藏暫時不用。覺得沒必要。

      @endphp

      <input type="hidden" name="order_products[{{ $product_row }}][order_product_options][{{ $poid }}][name]" value="{{ $product_option['option_name'] }}" >
      <input type="hidden" name="order_products[{{ $product_row }}][order_product_options][{{ $poid }}][type]" value="{{ $product_option['option_type'] }}">

      <tr class="tr_order_product_option{{ $dnone }}{{ $tr_hiddable_option }}">
        <td colspan="9">
          【{{ $product_option['option_name'] }}】 <BR>

          @if($product_option['option_type'] == 'options_with_qty')
            @foreach($product_option['product_option_values'] as $product_option_value)
            
              @php 
                $ovid = $product_option_value->option_value_id;
                $povid = $product_option_value->id;
                $pov_value = $product_option_value->name;
                $quantity = $order_product_options[$order_product_id][$poid][$povid]['quantity'] ?? 0;
                $opoid    = $order_product_options[$order_product_id][$poid][$povid]['order_product_option_id'] ?? '';
              @endphp
              <div style="float:left;" >
              {{ $product_option_value->name }} <input type="number" value="{{ $quantity }}" class="options_with_qty" style="width:50px;" name="order_products[{{ $product_row }}][order_product_options][{{ $poid }}][product_option_values][{{ $povid }}][quantity]" data-option_code="{{ $product_option['option_code'] }}" data-option-value="{{ $product->name .'_'. $pov_value }}" data-option-value-id="{{ $ovid }}" data-is_default="{{ $product_option_value->is_default }}" data-option-price="{{ $product_option_value->price }}" data-element="options_with_qty">&nbsp;&nbsp; 
              </div>
              <input type="hidden" value="{{ $pov_value }}" name="order_products[{{ $product_row }}][order_product_options][{{ $poid }}][product_option_values][{{ $povid }}][value]">
              <input type="hidden" value="{{ $opoid }}" name="order_products[{{ $product_row }}][order_product_options][{{ $poid }}][product_option_values][{{ $povid }}][opoid]">
            @endforeach

          @elseif($product_option['option_type'] == 'checkbox')
            @foreach($product_option['product_option_values'] as $product_option_value)
              @php
                $povid = $product_option_value->id; 
                $pov_value = $product_option_value->name;
                $opoid = $order_product_options[$order_product_id][$poid][$povid]['order_product_option_id'] ?? '';
                $inpPrifixId = 'input-product-'.$product_row.'-product_options-'.$poid.'-'.$povid;
                $inpPrifixName = 'order_products['.$product_row.'][order_product_options]['.$poid.'][product_option_values]['.$povid.']';

                $strChecked = '';
                if(!empty($opoid)){
                  $strChecked = ' checked';
                }
              @endphp
            <label for="{{ $inpPrifixId }}">{{ $product_option_value->name }}</label>
            <input type="checkbox" name="{{ $inpPrifixName }}[checked]" value='{{ $povid }}' id="{{ $inpPrifixId }}" {{ $strChecked }}>
            <input type="hidden"   name="{{ $inpPrifixName }}[value]"   value="{{ $pov_value }}">&nbsp; 
            <input type="hidden"   name="{{ $inpPrifixName }}[opoid]"   value="{{ $opoid }}">

            @endforeach

          @elseif($product_option['option_type'] == 'radio')
            @foreach($product_option['product_option_values'] as $product_option_value)
              @php
                $povid = $product_option_value['id']; 
                $id = 'input-product-'.$product_row.'-product_options-'.$poid.'-'.$povid;
                $inpPrifixName = 'order_products['.$product_row.'][order_product_options]['.$poid.']';
              @endphp
              <div>
                <label for="{{ $inpPrifixId }}">{{ $product_option_value->name }}</label>
                <input type="radio" value='0' id="{{ $inpPrifixId }}" name="{{ $inpPrifixName }}">
              </div>
            @endforeach

          @endif

        </td>
      </tr>
    @endforeach
    {{-- end 通用選項迴圈 --}}

  {{-- 商品備註 --}}
    <tr>
      <td colspan="9">
        <input type="text" value="{{ $order_product->comment ?? '' }}" name="order_products[{{ $product_row }}][comment]" 
          placeholder="這是備註  (列序 {{ $product_row }}, {{ $main_category_name }}, {{ $main_category_code }}, 商品代號 {{ $product->id }} {{ $product->model }}, )" class="form-control">

          <input type="hidden" id="product-row-{{ $product_row }}-hidden_main_category_code" value="{{ $main_category_code }}">
          <input type="hidden" id="product-row-{{ $product_row }}-hidden_name" value="{{ $name }}">
          <input type="hidden" id="product-row-{{ $product_row }}-hidden_model" value="{{ $model }}">
          <input type="hidden" id="product-row-{{ $product_row }}-hidden_price" value="{{ $price }}">
          <input type="hidden" id="product-row-{{ $product_row }}-hidden_total" value="{{ $total }}">
          <input type="hidden" id="product-row-{{ $product_row }}-hidden_sort_order" value="{{ $order_product->sort_order ?? 9999 }}">

      </td>
    </tr>
    {{-- end 商品備註 --}}

    {{-- 統計內容 --}}
      <tr>
        <td colspan="9">
          主餐潤餅數量：<span id="input-product-{{ $product_row }}-burrito_total">333</span>, &nbsp;飲料數量：<span id="input-product-{{ $product_row }}-drink_total">333</span>
        </td>
      </tr>
      {{-- end 統計內容 --}}

</table>
<BR>