{{-- $max_columns 不包含飲料欄位 --}}

<!-- 油飯盒系列 -->
  @if(!empty($order['categories']['oilRiceBox']))
    @php $max_columns=18; @endphp
    <table id="oilRiceBox" class=" table-bordered border border-dark tr-border-top " style="margin-top:3px;margin-bottom:0px;">
      <thead>
        <tr>
          <td style="width:230px;" class="fw-bold">{{ $order['categories']['oilRiceBox']['name'] }}</td>
          <td style="width:24px;" class="fw-bold">小計
          @php $column_used_num = 2; @endphp

          @foreach($columns['MainMeal'] ?? [] as $item)
            <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
              {{ $item->short_name }}
            </td>
            @php $column_used_num++; @endphp
          @endforeach

          @foreach($order['categories']['oilRiceBox']['Columns']['SecondaryMainMeal'] ?? [] as $option_value_id => $name)
            <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
              {{ $name }}
            </td>
            @php $column_used_num++; @endphp
          @endforeach

          @php $left = $max_columns-$column_used_num; @endphp
          @for($i = 1; $i <= $left; $i++)
            <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif"> </td>
          @endfor

          @foreach($columns['Drink'] ?? [] as $item)
            <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
              {{ $item->short_name }}
            </td>
            @php $column_used_num++; @endphp
          @endforeach
        </tr>
      </thead>
      <tbody>
        @foreach($order['categories']['oilRiceBox']['items'] ?? [] as $product_id => $product)
        <tr>
          <td>{{ $product['name'] }} ${{ $product['price'] }}</td>
          <td>{{ $product['quantity'] }}</td>
          @php $column_used_num = 2; @endphp

          @foreach($columns['MainMeal'] ?? [] as $item)
            <td style="@if ($loop->last) border-right:3px solid black @endif">
              @foreach($item->option_value_ids as $option_value_id)
                @if(!empty($product['product_options']['MainMeal'][$option_value_id]['quantity']))
                  {{ $product['product_options']['MainMeal'][$option_value_id]['quantity'] }}
                @endif
              @endforeach
            </td>
            @php $column_used_num++; @endphp
          @endforeach

          @foreach($order['categories']['oilRiceBox']['Columns']['SecondaryMainMeal'] ?? [] as $option_value_id => $name)
            <td style="@if ($loop->last) border-right:3px solid black @endif">
              @if(!empty($product['product_options']['SecondaryMainMeal'][$option_value_id]['quantity']))
                {{ $product['product_options']['SecondaryMainMeal'][$option_value_id]['quantity'] }}
              @endif
            </td>
            @php $column_used_num++; @endphp
          @endforeach

          @php $left = $max_columns-$column_used_num; @endphp
          @for($i = 1; $i <= $left; $i++)
            <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif"> </td>
          @endfor

          @foreach($columns['Drink'] ?? [] as $item)
            <td style="@if ($loop->last) border-right:3px solid black @endif">
              @if(!empty($product['product_options']['Drink'][$item->option_value_id]['quantity']))
                {{ $product['product_options']['Drink'][$item->option_value_id]['quantity'] }}
              @endif
            </td>
            @php $column_used_num++; @endphp
        @endforeach
        </tr>
        @endforeach                                                                                      </tbody>
    </table>
  @endif
<!-- end 刈包便當系列 -->

<!-- 便當系列 -->
  @if(!empty($order['categories']['bento']))
    @php $max_columns=18; @endphp
  <table id="bento" class=" table-bordered border border-dark tr-border-top " style="margin-top:3px;margin-bottom:0px;">
    <thead>
      <tr>
        <td style="width:230px;" class="fw-bold">{{ $order['categories']['bento']['name'] }}</td>
        <td style="width:24px;" class="fw-bold">小計</td>
        @php $column_used_num = 2; @endphp

        @foreach($columns['MainMeal'] ?? [] as $item)
          <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
            {{ $item->short_name }}
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @foreach($order['categories']['bento']['Columns']['SecondaryMainMeal'] ?? [] as $option_value_id => $name)
          <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
          {{ $name }}
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @php $left = $max_columns-$column_used_num; @endphp
        @for($i = 1; $i <= $left; $i++)
          <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif"> </td>
        @endfor

        @foreach($columns['Drink'] ?? [] as $item)
          <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
            {{ $item->short_name }}
          </td>
          @php $column_used_num++; @endphp
        @endforeach
      </tr>
    </thead>
    <tbody>
      @foreach($order['categories']['bento']['items'] ?? [] as $product_id => $product)
      <tr>
        <td>{{ $product['name'] }} ${{ $product['price'] }}</td>
        <td>{{ $product['quantity'] }}</td>
        @php $column_used_num = 2; @endphp

        @foreach($columns['MainMeal'] ?? [] as $item)
          <td style="@if ($loop->last) border-right:3px solid black @endif">
            @foreach($item->option_value_ids as $option_value_id)
              @if(!empty($product['product_options']['MainMeal'][$option_value_id]['quantity']))
                {{ $product['product_options']['MainMeal'][$option_value_id]['quantity'] }}
              @endif
            @endforeach
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @foreach($order['categories']['bento']['Columns']['SecondaryMainMeal'] ?? [] as $option_value_id => $name)
          <td style="@if ($loop->last) border-right:3px solid black @endif">
            @if(!empty($product['product_options']['SecondaryMainMeal'][$option_value_id]['quantity']))
              {{ $product['product_options']['SecondaryMainMeal'][$option_value_id]['quantity'] }}
            @endif
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @php $left = $max_columns-$column_used_num; @endphp
        @for($i = 1; $i <= $left; $i++)
          <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif"> </td>
        @endfor

        @foreach($columns['Drink'] ?? [] as $item)
          <td style="@if ($loop->last) border-right:3px solid black @endif">
            @if(!empty($product['product_options']['Drink'][$item->option_value_id]['quantity']))
              {{ $product['product_options']['Drink'][$item->option_value_id]['quantity'] }}
            @endif
          </td>
          @php $column_used_num++; @endphp
        @endforeach
      </tr>
      @endforeach
    </tbody>
  </table>
  @endif
<!-- end 便當系列 -->

<!-- 潤餅便當系列 -->
  @if(!empty($order['categories']['lumpiaBento']))
    @php $max_columns=19; @endphp
  <table id="lumpiaBento" class=" table-bordered border border-dark tr-border-top " style="margin-top:3px;margin-bottom:0px;">
    <thead>
      <tr>
        <td style="width:230px;" class="fw-bold"><img src="{{ asset('assets2/image/bento.png') }}" style="height:20px; vertical-align:middle;"> {{ $order['categories']['lumpiaBento']['name'] }}</td>
        <td style="width:24px;" class="fw-bold">小計</td>
        @php $column_used_num = 2; @endphp

        @foreach($columns['MainMeal'] ?? [] as $item)
          <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
            {{ $item->short_name }}
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @foreach($order['categories']['lumpiaBento']['Columns']['SecondaryMainMeal'] ?? [] as $option_value_id => $name)
          <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
          {{ $name }}
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @php $left = $max_columns-$column_used_num; @endphp
        @for($i = 1; $i <= $left; $i++)
          <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif"> </td>
        @endfor

        @foreach($columns['Drink'] ?? [] as $item)
          <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
            {{ $item->short_name }}
          </td>
          @php $column_used_num++; @endphp
        @endforeach
      </tr>
    </thead>
    <tbody>
      @foreach($order['categories']['lumpiaBento']['items'] ?? [] as $product_id => $product)
      <tr>
        <td>{{ $product['name'] }} ${{ $product['price'] }}</td>
        <td>{{ $product['quantity'] }}</td>
        @php $column_used_num = 2; @endphp

        @foreach($columns['MainMeal'] ?? [] as $item)
          <td style="@if ($loop->last) border-right:3px solid black @endif">
            @foreach($item->option_value_ids as $option_value_id)
              @if(!empty($product['product_options']['MainMeal'][$option_value_id]['quantity']))
                {{ $product['product_options']['MainMeal'][$option_value_id]['quantity'] }}
              @endif
            @endforeach
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @foreach($order['categories']['lumpiaBento']['Columns']['SecondaryMainMeal'] ?? [] as $option_value_id => $name)
          <td style="@if ($loop->last) border-right:3px solid black @endif">
            @if(!empty($product['product_options']['SecondaryMainMeal'][$option_value_id]['quantity']))
              {{ $product['product_options']['SecondaryMainMeal'][$option_value_id]['quantity'] }}
            @endif
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @php $left = $max_columns-$column_used_num; @endphp
        @for($i = 1; $i <= $left; $i++)
          <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif"> </td>
        @endfor

        @foreach($columns['Drink'] ?? [] as $item)
          <td style="@if ($loop->last) border-right:3px solid black @endif">
            @if(!empty($product['product_options']['Drink'][$item->option_value_id]['quantity']))
              {{ $product['product_options']['Drink'][$item->option_value_id]['quantity'] }}
            @endif
          </td>
        @endforeach
      </tr>
      @endforeach
    </tbody>
  </table>
  @endif
<!-- end 潤餅便當系列 -->

<!-- 刈包便當系列 -->
  @if(!empty($order['categories']['guabaoBento']))
    @php $max_columns=19; @endphp
    <table id="guabaoBento" class=" table-bordered border border-dark tr-border-top " style="margin-top:3px;margin-bottom:0px;">
      <thead>
        <tr>
          <td style="width:230px;" class="fw-bold"><img src="{{ asset('assets2/image/bento.png') }}" style="height:20px; vertical-align:middle;">  {{ $order['categories']['guabaoBento']['name'] }}</td>
          <td style="width:24px;" class="fw-bold">小計</td>
          @php $column_used_num = 2; @endphp

          @foreach($columns['MainMeal'] ?? [] as $item)
            <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
              {{ $item->short_name }}
            </td>
            @php $column_used_num++; @endphp
          @endforeach

          @foreach($order['categories']['guabaoBento']['Columns']['SecondaryMainMeal'] ?? [] as $option_value_id => $name)
            <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
              {{ $name }}
            </td>
            @php $column_used_num++; @endphp
          @endforeach

          @php $left = $max_columns-$column_used_num; @endphp
          @for($i = 1; $i <= $left; $i++)
            <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif"> </td>
          @endfor

          @foreach($columns['Drink'] ?? [] as $item)
            <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
              {{ $item->short_name }}
            </td>
            @php $column_used_num++; @endphp
          @endforeach
        </tr>
      </thead>
      <tbody>
        @foreach($order['categories']['guabaoBento']['items'] ?? [] as $product_id => $product)
        <tr>
          <td>{{ $product['name'] }} ${{ $product['price'] }}</td>
          <td>{{ $product['quantity'] }}</td>
          @php $column_used_num = 2; @endphp

          @foreach($columns['MainMeal'] ?? [] as $item)
            <td style="@if ($loop->last) border-right:3px solid black @endif">
              @foreach($item->option_value_ids as $option_value_id)
                @if(!empty($product['product_options']['MainMeal'][$option_value_id]['quantity']))
                  {{ $product['product_options']['MainMeal'][$option_value_id]['quantity'] }}
                  
                @endif
              @endforeach
            </td>
            @php $column_used_num++; @endphp
          @endforeach

          @foreach($order['categories']['guabaoBento']['Columns']['SecondaryMainMeal'] ?? [] as $option_value_id => $name)
            <td style="@if ($loop->last) border-right:3px solid black @endif">
              @if(!empty($product['product_options']['SecondaryMainMeal'][$option_value_id]['quantity']))
                {{ $product['product_options']['SecondaryMainMeal'][$option_value_id]['quantity'] }}
              @endif
            </td>
            @php $column_used_num++; @endphp
          @endforeach

          @php $left = $max_columns-$column_used_num; @endphp
          @for($i = 1; $i <= $left; $i++)
            <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif"> </td>
          @endfor

          @foreach($columns['Drink'] ?? [] as $item)
            <td style="@if ($loop->last) border-right:3px solid black @endif">
              @if(!empty($product['product_options']['Drink'][$item->option_value_id]['quantity']))
                {{ $product['product_options']['Drink'][$item->option_value_id]['quantity'] }}
              @endif
            </td>
          @endforeach
        </tr>
        @endforeach
      </tbody>
    </table>
  @endif
<!-- end 刈包便當系列 -->

<!-- 客製便當 包括潤餅、刈包--->
  @if(!empty($order['categories']['customBento']))
  <table id="customBento" class=" table-bordered border border-dark tr-border-top " style="margin-top:3px;margin-bottom:0px;">
    <thead>
      <tr>
        <td style="width:160px;" class="fw-bold">{{ $order['categories']['customBento']['name'] }}</td>
        <td style="width:24px;" class="fw-bold">小計</td>
        @php $column_used_num = 2; @endphp
        
        @foreach($columns['MainMeal'] ?? [] as $item)
          <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
            {{ $item->short_name }}
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @foreach($order['categories']['customBento']['Columns']['SecondaryMainMeal'] ?? [] as $option_value_id => $name)
          <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
          {{ $name }}
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @foreach($order['categories']['customBento']['Columns']['SideDish'] ?? [] as $option_value_id => $name)
          <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
            {{ $name }}
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @php $left = 23-$column_used_num; @endphp
        @for($i = 1; $i <= $left; $i++)
          <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif"> </td>
        @endfor

        @foreach($columns['Drink'] ?? [] as $item)
          <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
            {{ $item->short_name }}
          </td>
          @php $column_used_num++; @endphp
        @endforeach
      </tr>
    </thead>
    <tbody>
      @foreach($order['categories']['customBento']['items'] ?? [] as $product_id => $product)
      <tr>
        <td>{{ $product['name'] }} ${{ $product['price']}}</td>
        <td>{{ $product['quantity'] }}</td>
        @php $column_used_num = 2; @endphp

        @foreach($columns['MainMeal'] ?? [] as $item)
          <td style="@if ($loop->last) border-right:3px solid black @endif">
            @foreach($item->option_value_ids as $option_value_id)
              @if(!empty($product['product_options']['MainMeal'][$option_value_id]['quantity']))
                {{ $product['product_options']['MainMeal'][$option_value_id]['quantity'] }}
              @endif
            @endforeach
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @foreach($order['categories']['customBento']['Columns']['SecondaryMainMeal'] ?? [] as $option_value_id => $name)
          <td style="@if ($loop->last) border-right:3px solid black @endif">
            @if(!empty($product['product_options']['SecondaryMainMeal'][$option_value_id]['quantity']))
              {{ $product['product_options']['SecondaryMainMeal'][$option_value_id]['quantity'] }}
            @endif
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @foreach($order['categories']['customBento']['Columns']['SideDish'] ?? [] as $option_value_id => $name)
          <td style="@if ($loop->last) border-right:3px solid black @endif">
            @if(!empty($product['product_options']['SideDish'][$option_value_id]['quantity']))
              {{ $product['product_options']['SideDish'][$option_value_id]['quantity'] }}
            @endif
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @php $left = 23-$column_used_num; @endphp
        @for($i = 1; $i <= $left; $i++)
          <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif"> </td>
        @endfor

        @foreach($columns['Drink'] ?? [] as $item)
          <td style="@if ($loop->last) border-right:3px solid black @endif">
            @if(!empty($product['product_options']['Drink'][$item->option_value_id]['quantity']))
              {{ $product['product_options']['Drink'][$item->option_value_id]['quantity'] }}
            @endif
          </td>
          @php $column_used_num++; @endphp
        @endforeach
      </tr>
      @endforeach
    </tbody>
  </table>
  @endif
<!-- end 便當系列 -->

<!-- 潤餅盒餐系列 -->
  @if(!empty($order['categories']['lumpiaLunchBox']))
    @php $max_columns=19; @endphp
  <table id="lumpiaLunchBox" class=" table-bordered border border-dark tr-border-top " style="margin-top:3px;margin-bottom:0px;">
    <thead>
      <tr>
        <td style="width:230px;" class="fw-bold"><img src="{{ asset('assets2/image/lunchbox.png') }}" style="height:20px; vertical-align:middle;"> {{ $order['categories']['lumpiaLunchBox']['name'] }}</td>
        <td style="width:24px;" class="fw-bold">小計</td>
        @php $column_used_num = 2; @endphp

        @foreach($columns['MainMeal'] ?? [] as $item)
          <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
            {{ $item->short_name }}
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @foreach($order['categories']['lumpiaLunchBox']['Columns']['SecondaryMainMeal'] ?? [] as $option_value_id => $name)
          <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
          {{ $name }}
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @foreach($order['categories']['lumpiaLunchBox']['Columns']['SideDish'] ?? [] as $option_value_id => $name)
          <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
            {{ $name }}
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @php $left = $max_columns-$column_used_num; @endphp
        @for($i = 1; $i <= $left; $i++)
          <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif"> </td>
        @endfor

        @foreach($columns['Drink'] ?? [] as $item)
          <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
            {{ $item->short_name }}
          </td>
          @php $column_used_num++; @endphp
        @endforeach
      </tr>
    </thead>
    <tbody>
      @foreach($order['categories']['lumpiaLunchBox']['items'] ?? [] as $product_id => $product)
      <tr>
        <td>{{ $product['name'] }} ${{ $product['price'] }}</td>
        <td>{{ $product['quantity'] }}</td>
        @php $column_used_num = 2; @endphp

        @foreach($columns['MainMeal'] ?? [] as $item)
          <td style="@if ($loop->last) border-right:3px solid black @endif">
            @foreach($item->option_value_ids as $option_value_id)
              @if(!empty($product['product_options']['MainMeal'][$option_value_id]['quantity']))
                {{ $product['product_options']['MainMeal'][$option_value_id]['quantity'] }}
              @endif
            @endforeach
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @foreach($order['categories']['lumpiaLunchBox']['Columns']['SecondaryMainMeal'] ?? [] as $option_value_id => $name)
          <td style="@if ($loop->last) border-right:3px solid black @endif">
            @if(!empty($product['product_options']['SecondaryMainMeal'][$option_value_id]['quantity']))
              {{ $product['product_options']['SecondaryMainMeal'][$option_value_id]['quantity'] }}
            @endif
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @foreach($order['categories']['lumpiaLunchBox']['Columns']['SideDish'] ?? [] as $option_value_id => $name)
          <td style="@if ($loop->last) border-right:3px solid black @endif" data-option_value_id="{{ $option_value_id }}">
            @if(!empty($product['product_options']['SideDish'][$option_value_id]['quantity']))
              {{ $product['product_options']['SideDish'][$option_value_id]['quantity'] }}
            @endif
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @php $left = $max_columns-$column_used_num; @endphp
        @for($i = 1; $i <= $left; $i++)
          <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif"> </td>
        @endfor

        @foreach($columns['Drink'] ?? [] as $item)
          @php $option_value_id = $item->option_value_id;  @endphp
          <td style="@if ($loop->last) border-right:3px solid black @endif" data-option_value_id="{{ $option_value_id }}">
            @if(!empty($product['product_options']['Drink'][$option_value_id]['quantity']))
              {{ $product['product_options']['Drink'][$option_value_id]['quantity'] }}
            @endif
          </td>
          @php $column_used_num++; @endphp
        @endforeach
      </tr>
      <tr>
        <td>飲料</td>
        <td></td>
        @php $column_used_num = 2; @endphp
        @foreach($columns['MainMeal'] ?? [] as $item)
          <td style="@if ($loop->last) border-right:3px solid black @endif" data-option_value_id="{{ $option_value_id }}">
            @foreach($item->option_value_ids as $option_value_id)
              @if(!empty($product['product_options']['MainMeal'][$option_value_id]['SubDrinks']))
                @foreach($product['product_options']['MainMeal'][$option_value_id]['SubDrinks'] as $drink)
                  {{ mb_substr($drink['short_name'],0,1) }}{{ $drink['quantity'] }}
                @endforeach
              @endif
            @endforeach
          </td>
          @php $column_used_num++; @endphp
        @endforeach
      </tr>
      @endforeach
    </tbody>
  </table>
  @endif
<!-- end 潤餅盒餐系列 -->

<!-- 刈包盒餐系列 -->
  @if(!empty($order['categories']['guabaoLunchBox']))
    @php $max_columns=19; @endphp
  <table id="guabaoLunchBox" class=" table-bordered border border-dark tr-border-top " style="margin-top:3px;margin-bottom:0px;">
    <thead>
      <tr>
        <td style="width:230px;" class="fw-bold">{{ $order['categories']['guabaoLunchBox']['name'] }}</td>
        <td style="width:24px;" class="fw-bold">小計</td>
        @php $column_used_num = 2; @endphp

        @foreach($columns['MainMeal'] ?? [] as $item)
          <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
            {{ $item->short_name }}
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @foreach($order['categories']['guabaoLunchBox']['Columns']['SecondaryMainMeal'] ?? [] as $option_value_id => $name)
          <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
          {{ $name }}
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @foreach($order['categories']['guabaoLunchBox']['Columns']['SideDish'] ?? [] as $option_value_id => $name)
          <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
            {{ $name }}
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @php $left = $max_columns-$column_used_num; @endphp
        @for($i = 1; $i <= $left; $i++)
          <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif"> </td>
        @endfor

        @foreach($columns['Drink'] ?? [] as $item)
          <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
            {{ $item->short_name }}
          </td>
          @php $column_used_num++; @endphp
        @endforeach
      </tr>
    </thead>
    <tbody>
      @foreach($order['categories']['guabaoLunchBox']['items'] ?? [] as $product_id => $product)
      <tr>
        <td>{{ $product['name'] }} ${{ $product['price'] }}</td>
        <td>{{ $product['quantity'] }}</td>
        @php $column_used_num = 2; @endphp

        @foreach($columns['MainMeal'] ?? [] as $item)
          <td style="@if ($loop->last) border-right:3px solid black @endif">
            @foreach($item->option_value_ids as $option_value_id)
              @if(!empty($product['product_options']['MainMeal'][$option_value_id]['quantity']))
                {{ $product['product_options']['MainMeal'][$option_value_id]['quantity'] }}
              @endif
            @endforeach
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @foreach($order['categories']['guabaoLunchBox']['Columns']['SecondaryMainMeal'] ?? [] as $option_value_id => $name)
          <td style="@if ($loop->last) border-right:3px solid black @endif">
            @if(!empty($product['product_options']['SecondaryMainMeal'][$option_value_id]['quantity']))
              {{ $product['product_options']['SecondaryMainMeal'][$option_value_id]['quantity'] }}
            @endif
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @foreach($order['categories']['guabaoLunchBox']['Columns']['SideDish'] ?? [] as $option_value_id => $name)
          <td style="@if ($loop->last) border-right:3px solid black @endif" data-option_value_id="{{ $option_value_id }}">
            @if(!empty($product['product_options']['SideDish'][$option_value_id]['quantity']))
              {{ $product['product_options']['SideDish'][$option_value_id]['quantity'] }}
            @endif
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @php $left = $max_columns-$column_used_num; @endphp
        @for($i = 1; $i <= $left; $i++)
          <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif"> </td>
        @endfor

        @foreach($columns['Drink'] ?? [] as $item)
          @php $option_value_id = $item->option_value_id;  @endphp
          <td style="@if ($loop->last) border-right:3px solid black @endif" data-option_value_id="{{ $option_value_id }}">
            @if(!empty($product['product_options']['Drink'][$option_value_id]['quantity']))
              {{ $product['product_options']['Drink'][$option_value_id]['quantity'] }}
            @endif
          </td>
          @php $column_used_num++; @endphp
        @endforeach
      </tr>

      <tr>
        <td>飲料</td>
        <td></td>
        @php $column_used_num = 2; @endphp
        @foreach($columns['MainMeal'] ?? [] as $item)
          <td style="@if ($loop->last) border-right:3px solid black @endif" data-option_value_id="{{ $option_value_id }}">
            @foreach($item->option_value_ids as $option_value_id)
              @if(!empty($product['product_options']['MainMeal'][$option_value_id]['SubDrinks']))
                @foreach($product['product_options']['MainMeal'][$option_value_id]['SubDrinks'] as $drink)
                  {{ mb_substr($drink['short_name'],0,1) }}{{ $drink['quantity'] }}
                @endforeach
              @endif
            @endforeach
          </td>
          @php $column_used_num++; @endphp
        @endforeach
      </tr>
      @endforeach
    </tbody>
  </table>


  <table id="guabaoLunchBox" class=" table-bordered border border-dark tr-border-top " style="margin-top:3px;margin-bottom:0px;">

  </table>
  @endif
<!-- end 刈包盒餐系列 -->

<!-- 客製盒餐系列 包括潤餅、刈包-->
  @if(!empty($order['categories']['customLunchbox']))
  <table id="customLunchbox" class=" table-bordered border border-dark tr-border-top " style="margin-top:3px;margin-bottom:0px;">
    <thead>
      <tr>
        <td style="width:230px;" class="fw-bold">{{ $order['categories']['customLunchbox']['name'] }}</td>
        <td style="width:24px;" class="fw-bold">小計</td>
        @php $column_used_num = 2; @endphp
        
        @foreach($columns['MainMeal'] ?? [] as $item)
          <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
            {{ $item->short_name }}
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @foreach($order['categories']['customLunchbox']['Columns']['SecondaryMainMeal'] ?? [] as $option_value_id => $name)
          <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
            {{ $name }}
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @foreach($order['categories']['customLunchbox']['Columns']['SideDish'] ?? [] as $option_value_id => $name)
          <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
            {{ $name }}
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @php $left = 21-$column_used_num; @endphp
        @for($i = 1; $i <= $left; $i++)
          <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif"> </td>
        @endfor

        @foreach($columns['Drink'] ?? [] as $item)
          <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
            {{ $item->short_name }}
          </td>
          @php $column_used_num++; @endphp
        @endforeach
      </tr>
    </thead>
    <tbody>
      @foreach($order['categories']['customLunchbox']['items'] ?? [] as $product_id => $product)
      <tr>
        <td>{{ $product['name'] }} ${{ $product['price'] }} </td>
        <td>{{ $product['quantity'] }}</td>
        @php $column_used_num = 2; @endphp

        @foreach($columns['MainMeal'] ?? [] as $item)
          <td style="@if ($loop->last) border-right:3px solid black @endif">
            @foreach($item->option_value_ids as $option_value_id)
              @if(!empty($product['product_options']['MainMeal'][$option_value_id]['quantity']))
                {{ $product['product_options']['MainMeal'][$option_value_id]['quantity'] }}
              @endif
            @endforeach
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @foreach($order['categories']['customLunchbox']['Columns']['SecondaryMainMeal'] ?? [] as $option_value_id => $name)
          <td style="@if ($loop->last) border-right:3px solid black @endif" data-option_value_id="{{ $option_value_id }}">
            @if(!empty($product['product_options']['SecondaryMainMeal'][$option_value_id]['quantity']))
              {{ $product['product_options']['SecondaryMainMeal'][$option_value_id]['quantity'] }}
            @endif
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @foreach($order['categories']['customLunchbox']['Columns']['SideDish'] ?? [] as $option_value_id => $name)
          <td style="@if ($loop->last) border-right:3px solid black @endif" data-option_value_id="{{ $option_value_id }}">
            @if(!empty($product['product_options']['SideDish'][$option_value_id]['quantity']))
              {{ $product['product_options']['SideDish'][$option_value_id]['quantity'] }}
            @endif
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @php $left = 21-$column_used_num; @endphp
        @for($i = 1; $i <= $left; $i++)
          <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif"> </td>
        @endfor

        @foreach($columns['Drink'] ?? [] as $item)
          @php $option_value_id = $item->option_value_id;  @endphp
          <td style="@if ($loop->last) border-right:3px solid black @endif" data-option_value_id="{{ $option_value_id }}">
            @if(!empty($product['product_options']['Drink'][$option_value_id]['quantity']))
              {{ $product['product_options']['Drink'][$option_value_id]['quantity'] }}
            @endif
          </td>
          @php $column_used_num++; @endphp
        @endforeach
      </tr>

      <tr>
        <td>飲料</td>
        <td></td>
        @php $column_used_num = 2; @endphp
        @foreach($columns['MainMeal'] ?? [] as $item)
          <td style="@if ($loop->last) border-right:3px solid black @endif">
            @foreach($item->option_value_ids as $option_value_id)
              @if(!empty($product['product_options']['MainMeal'][$option_value_id]['SubDrinks']))
                @foreach($product['product_options']['MainMeal'][$option_value_id]['SubDrinks'] as $drink)
                  {{ mb_substr($drink['short_name'],0,1) }}{{ $drink['quantity'] }}
                @endforeach
              @endif
            @endforeach
          </td>
          @php $column_used_num++; @endphp
        @endforeach
      </tr>
      @endforeach



    </tbody>
  </table>
  @endif
<!-- end 客製盒餐系列 -->

<!-- 其它口味餐點 -->
  @if(!empty($order['categories']['otherFlavors']))
    @php $max_columns=24; @endphp


  <table id="otherFlavors" class=" table-bordered border border-dark tr-border-top " style="margin-top:3px;margin-bottom:0px;">
    <thead>
      <tr>
        <td style="width:230px;" class="fw-bold">{{ $order['categories']['otherFlavors']['name'] }}</td>
        <td style="width:24px;" class="fw-bold">小計</td>
        @php $column_used_num = 2; @endphp

        @foreach($columns['MainMeal'] ?? [] as $item)
          <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
            {{ $item->short_name }}
          </td>
          @php $column_used_num++; @endphp
        @endforeach

        @php $left = $max_columns-$column_used_num; @endphp
        @for($i = 1; $i <= $left; $i++)
          <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif" class="fw-bold"> </td>
        @endfor
      </tr>
    </thead>
      <tbody>
        @foreach($order['categories']['otherFlavors']['items'] ?? [] as $product_id => $product)
        <tr>
          <td>{{ $product['name'] }} ${{ $product['price'] }}</td>
          <td>{{ $product['quantity'] }}</td>
          @php $column_used_num = 2; @endphp

          @foreach($columns['MainMeal'] ?? [] as $item)
            <td style="@if ($loop->last) border-right:3px solid black @endif">
              @foreach($item->option_value_ids as $option_value_id)
                @if(!empty($product['product_options']['MainMeal'][$option_value_id]['quantity']))
                  {{ $product['product_options']['MainMeal'][$option_value_id]['quantity'] }}
                @endif
              @endforeach
            </td>
            @php $column_used_num++; @endphp
          @endforeach

          @php $left = $max_columns-$column_used_num; @endphp
          @for($i = 1; $i <= $left; $i++)
            <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif"> </td>
          @endfor
        </tr>
        @endforeach
      </tbody>
  </table>
  @endif
<!-- end 分享餐系列 -->


@if(!empty($order['categories']['soloDrinkLumpiaGuabao']))
@php $max_columns=19; @endphp
@php $column_used_num = 0; @endphp
<!-- 單點潤餅、刈包、飲料、豆花 -->
<table id="soloDrinkLumpiaGuabao" class=" table-bordered border border-dark tr-border-top " style="margin-top:3px;margin-bottom:0px;">
      <thead>
        <tr>
          <td style="width:230px;" class="fw-bold">{{ $order['categories']['soloDrinkLumpiaGuabao']['name'] }}</td>
          <td style="width:24px;" class="fw-bold">小計</td>
          @php $column_used_num = 2; @endphp

          @foreach($columns['MainMeal'] ?? [] as $item)
            <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
              {{ $item->short_name }}
            </td>
            @php $column_used_num++; @endphp
          @endforeach

          @foreach($columns['Douhua'] ?? [] as $item)
            <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
              {{ $item->short_name }}
            </td>
            @php $column_used_num++; @endphp
          @endforeach

          @php $left = $max_columns-$column_used_num; @endphp
          @for($i = 1; $i <= $left; $i++)
            <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif"> </td>
          @endfor

          @foreach($columns['Drink'] ?? [] as $item)
            <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
              {{ $item->short_name }}
            </td>
            @php $column_used_num++; @endphp
          @endforeach
        </tr>
      </thead>
      <tbody>
        @foreach($order['categories']['soloDrinkLumpiaGuabao']['items'] ?? [] as $product_id => $product)
        <tr>
          <td>{{ $product['name'] }} ${{ $product['price'] }}</td>
          <td>{{ $product['quantity'] }}</td>
          @php $column_used_num = 2; @endphp

          @foreach($columns['MainMeal'] ?? [] as $item)
            <td style="@if ($loop->last) border-right:3px solid black @endif">
              @foreach($item->option_value_ids as $option_value_id)
                @if(!empty($product['product_options']['Lumpia6inch'][$option_value_id]['quantity']))
                  {{ $product['product_options']['Lumpia6inch'][$option_value_id]['quantity'] }}
                @endif
              @endforeach
            </td>
            @php $column_used_num++; @endphp
          @endforeach

          @foreach($columns['Douhua'] ?? [] as $item)
            <td style="@if ($loop->last) border-right:3px solid black @endif">
              @foreach($item->option_value_ids as $option_value_id)
                @if(!empty($product['product_options']['Douhua'][$option_value_id]['quantity']))
                  {{ $product['product_options']['Douhua'][$option_value_id]['quantity'] }}
                @endif
              @endforeach
            </td>
            @php $column_used_num++; @endphp
          @endforeach

          @php $left = $max_columns-$column_used_num; @endphp
          @for($i = 1; $i <= $left; $i++)
            <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif"> </td>
          @endfor

          @foreach($columns['Drink'] ?? [] as $item)
            <td style="@if ($loop->last) border-right:3px solid black @endif">
              @if(!empty($product['product_options']['Drink'][$item->option_value_id]['quantity']))
                {{ $product['product_options']['Drink'][$item->option_value_id]['quantity'] }}
              @endif
            </td>
          @endforeach
        </tr>
        @endforeach
      </tbody>
</table>
<!-- end 單點飲料 -->
@endif

<!-- 其它 -->
@if(!empty($order['categories']['otherCategory']))
  <table id="lunchbox" data-toggle="table" class="table-bordered border border-dark rounded-3 tr-border-top" style="margin-top:3px;margin-bottom:0px; width:100%">
    <tr>
    <td style="width:100px;" class="fw-bold">單點(A)</td>
    <td>
      @foreach($order['categories']['otherCategory']['items'] ?? [] as $product_id => $row)
        {{ $row['display_name'] }},
      @endforeach
    </td>
    </tr>
  </table>
  @endif
<!-- end 單點 -->


<!-- 單點 1062 其它商品組 -->
@if(!empty($order['categories']['solo1062']))
  <table id="lunchbox" data-toggle="table" class="table-bordered border border-dark rounded-3 tr-border-top" style="margin-top:3px;margin-bottom:0px; width:100%">
    <tr>
      <td style="width:100px;" >單點(B)</td>
      <td>
        @foreach($order['categories']['solo1062']['items']['product_options']['Lumpia6inch'] ?? [] as $option_value_id => $row)
          {{ $row['value'] }}*{{ $row['quantity'] }}, <BR>
        @endforeach
        
        @if(!empty($order['categories']['solo1062']['items']['product_options']['BigGuabao'] ))
          @foreach($order['categories']['solo1062']['items']['product_options']['BigGuabao'] ?? [] as $option_value_id => $row)
            {{ $row['value'] }}*{{ $row['quantity'] }}, <BR>
          @endforeach
        @endif

        @if(!empty($order['categories']['solo1062']['items']['product_options']['Other']))
          
          @foreach($order['categories']['solo1062']['items']['product_options']['Other'] ?? [] as $option_value_id => $row)
            {{ $row['value'] }}*{{ $row['quantity'] }}, 
          @endforeach
        @endif
    </td>
    </tr>
  </table>
  @endif
<!-- end 單點 -->