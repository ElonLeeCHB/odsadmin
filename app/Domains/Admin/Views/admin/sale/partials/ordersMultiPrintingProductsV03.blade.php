    <!-- 油飯盒系列 -->
      @if(!empty($order['categories']['oilRiceBox']))
        <table id="oilRiceBox" class=" table-bordered border border-dark tr-border-top " style="margin-top:3px;margin-bottom:0px;">
          <thead>
            <tr>
              <td style="width:100px;" class="fw-bold">{{ $order['categories']['oilRiceBox']['name'] }}</td>
              <td style="width:24px;" class="fw-bold">小計
              @php $column_used_num = 2; @endphp
              @foreach($order['categories']['oilRiceBox']['Columns']['MainMeal'] as $option_value_id => $name)
                <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
                  {{ $name }}
                </td>
                @php $column_used_num++; @endphp
              @endforeach

              @foreach($order['categories']['oilRiceBox']['Columns']['SecondaryMainMeal'] ?? [] as $option_value_id => $name)
                <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
                  {{ $name }}
                </td>
                @php $column_used_num++; @endphp
              @endforeach

              @php $left = 24-$column_used_num; @endphp
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
            @foreach($order['categories']['oilRiceBox']['items'] ?? [] as $product_id => $row)
            <tr>
              <td>{{ $row['name'] }}</td>
              <td>{{ $row['quantity'] }}</td>
              @php $column_used_num = 2; @endphp
              @foreach($order['categories']['oilRiceBox']['Columns']['MainMeal'] as $option_value_id => $name)
                <td style="@if ($loop->last) border-right:3px solid black @endif">
                  @if(!empty($row['product_options']['MainMeal'][$option_value_id]['quantity']))
                    {{ $row['product_options']['MainMeal'][$option_value_id]['quantity'] }}
                  @endif
                </td>
                @php $column_used_num++; @endphp
              @endforeach

              @foreach($order['categories']['oilRiceBox']['Columns']['SecondaryMainMeal'] ?? [] as $option_value_id => $name)
                <td style="@if ($loop->last) border-right:3px solid black @endif">
                  @if(!empty($row['product_options']['SecondaryMainMeal'][$option_value_id]['quantity']))
                    {{ $row['product_options']['SecondaryMainMeal'][$option_value_id]['quantity'] }}
                  @endif
                </td>
                @php $column_used_num++; @endphp
              @endforeach

              @php $left = 24-$column_used_num; @endphp
              @for($i = 1; $i <= $left; $i++)
                <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif"> </td>
              @endfor

              @foreach($columns['Drink'] ?? [] as $item)
                <td style="@if ($loop->last) border-right:3px solid black @endif">
                  @if(!empty($row['product_options']['Drink'][$item->option_value_id]['quantity']))
                    {{ $row['product_options']['Drink'][$item->option_value_id]['quantity'] }}
                  @endif
                </td>
                @php $column_used_num++; @endphp
            @endforeach
            </tr>
            @endforeach                                                                                      </tbody>
        </table>
      @endif
    <!-- end 刈包便當系列 -->

    <!-- 潤餅便當系列 -->
      @if(!empty($order['categories']['lumpiaBento']))
      <table id="lumpiaBento" class=" table-bordered border border-dark tr-border-top " style="margin-top:3px;margin-bottom:0px;">
        <thead>
          <tr>
            <td style="width:100px;" class="fw-bold">{{ $order['categories']['lumpiaBento']['name'] }}</td>
            <td style="width:24px;" class="fw-bold">小計</td>
            @php $column_used_num = 2; @endphp
            @foreach($columns['lumpiaBento']['MainMeal'] ?? [] as $item)
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

            @php $left = 24-$column_used_num; @endphp
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
          @foreach($order['categories']['lumpiaBento']['items'] ?? [] as $product_id => $row)
          <tr>
            <td>{{ $row['name'] }}</td>
            <td>{{ $row['quantity'] }}</td>
            @php $column_used_num = 2; @endphp
            @foreach($columns['lumpiaBento']['MainMeal'] ?? [] as $item)
              <td style="@if ($loop->last) border-right:3px solid black @endif">
                @if(!empty($row['product_options']['MainMeal'][$item->option_value_id]['quantity']))
                  {{ $row['product_options']['MainMeal'][$item->option_value_id]['quantity'] }}
                @endif
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @foreach($order['categories']['lumpiaBento']['Columns']['SecondaryMainMeal'] ?? [] as $option_value_id => $name)
              <td style="@if ($loop->last) border-right:3px solid black @endif">
                @if(!empty($row['product_options']['SecondaryMainMeal'][$option_value_id]['quantity']))
                  {{ $row['product_options']['SecondaryMainMeal'][$option_value_id]['quantity'] }}
                @endif
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @php $left = 24-$column_used_num; @endphp
            @for($i = 1; $i <= $left; $i++)
              <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif"> </td>
            @endfor

            @foreach($columns['Drink'] ?? [] as $item)
              <td style="@if ($loop->last) border-right:3px solid black @endif">
                @if(!empty($row['product_options']['Drink'][$item->option_value_id]['quantity']))
                  {{ $row['product_options']['Drink'][$item->option_value_id]['quantity'] }}
                @endif
              </td>
              @php $column_used_num++; @endphp
            @endforeach
          </tr>
          @endforeach
        </tbody>
      </table>
      @endif
    <!-- end 潤餅便當系列 -->

    <!-- 刈包便當系列 -->
      @if(!empty($order['categories']['guabaoBento']))
        <table id="guabaoBento" class=" table-bordered border border-dark tr-border-top " style="margin-top:3px;margin-bottom:0px;">
          <thead>
            <tr>
              <td style="width:150px;" class="fw-bold">{{ $order['categories']['guabaoBento']['name'] }}</td>
              <td style="width:24px;" class="fw-bold">小計</td>
              @php $column_used_num = 2; @endphp
              @foreach($columns['guabaoBento']['MainMeal'] ?? [] as $item)
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
            @foreach($order['categories']['guabaoBento']['items'] ?? [] as $product_id => $row)
            <tr>
              <td>{{ $row['name'] }}</td>
              <td>{{ $row['quantity'] }}</td>
              @php $column_used_num = 2; @endphp
              @foreach($columns['guabaoBento']['MainMeal'] ?? [] as $item)
                <td style="@if ($loop->last) border-right:3px solid black @endif">
                  @if(!empty($row['product_options']['MainMeal'][$item->option_value_id]['quantity']))
                    {{ $row['product_options']['MainMeal'][$item->option_value_id]['quantity'] }}
                  @endif
                </td>
                @php $column_used_num++; @endphp
              @endforeach

              @foreach($order['categories']['guabaoBento']['Columns']['SecondaryMainMeal'] ?? [] as $option_value_id => $name)
                <td style="@if ($loop->last) border-right:3px solid black @endif">
                  @if(!empty($row['product_options']['SecondaryMainMeal'][$option_value_id]['quantity']))
                    {{ $row['product_options']['SecondaryMainMeal'][$option_value_id]['quantity'] }}
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
                  @if(!empty($row['product_options']['Drink'][$item->option_value_id]['quantity']))
                    {{ $row['product_options']['Drink'][$item->option_value_id]['quantity'] }}
                  @endif
                </td>
                @php $column_used_num++; @endphp
            @endforeach
            </tr>
            @endforeach                                                                                      </tbody>
        </table>
      @endif
    <!-- end 刈包便當系列 -->

    <!-- 客製便當 包括潤餅、刈包--->
      @if(!empty($order['categories']['customBento']))
      <table id="customBento" class=" table-bordered border border-dark tr-border-top " style="margin-top:3px;margin-bottom:0px;">
        <thead>
          <tr>
            <td style="width:135px;" class="fw-bold">{{ $order['categories']['customBento']['name'] }}</td>
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

            @php $left = 22-$column_used_num; @endphp
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
          @foreach($order['categories']['customBento']['items'] ?? [] as $product_id => $row)
          <tr>
            <td>{{ $row['name'] }} <BR> 價格: {{ $row['final_total']/$row['quantity'] }}</td>
            <td>{{ $row['quantity'] }}</td>
            @php $column_used_num = 2; @endphp
            @foreach($columns['MainMeal'] ?? [] as $item)
              <td style="@if ($loop->last) border-right:3px solid black @endif">
                @foreach($item->option_value_ids as $option_value_id)
                  @if(!empty($row['product_options']['MainMeal'][$option_value_id]['quantity']))
                    {{ $row['product_options']['MainMeal'][$option_value_id]['quantity'] }}
                  @endif
                @endforeach
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @foreach($order['categories']['customBento']['Columns']['SecondaryMainMeal'] ?? [] as $option_value_id => $name)
              <td style="@if ($loop->last) border-right:3px solid black @endif">
                @if(!empty($row['product_options']['SecondaryMainMeal'][$option_value_id]['quantity']))
                  {{ $row['product_options']['SecondaryMainMeal'][$option_value_id]['quantity'] }}
                @endif
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @foreach($order['categories']['customBento']['Columns']['SideDish'] ?? [] as $option_value_id => $name)
              <td style="@if ($loop->last) border-right:3px solid black @endif">
                @if(!empty($row['product_options']['SideDish'][$option_value_id]['quantity']))
                  {{ $row['product_options']['SideDish'][$option_value_id]['quantity'] }}
                @endif
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @php $left = 22-$column_used_num; @endphp
            @for($i = 1; $i <= $left; $i++)
              <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif"> </td>
            @endfor

            @foreach($columns['Drink'] ?? [] as $item)
              <td style="@if ($loop->last) border-right:3px solid black @endif">
                @if(!empty($row['product_options']['Drink'][$item->option_value_id]['quantity']))
                  {{ $row['product_options']['Drink'][$item->option_value_id]['quantity'] }}
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
      <table id="lumpiaLunchBox" class=" table-bordered border border-dark tr-border-top " style="margin-top:3px;margin-bottom:0px;">
        <thead>
          <tr>
            <td style="width:100px;" class="fw-bold">{{ $order['categories']['lumpiaLunchBox']['name'] }}</td>
            <td style="width:24px;" class="fw-bold">小計</td>
            @php $column_used_num = 2; @endphp
            @foreach($columns['lumpiaLunchBox']['MainMeal'] ?? [] as $item)
              <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
                {{ $item->short_name }}
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @foreach($order['categories']['lumpiaLunchBox']['Columns']['SideDish'] ?? [] as $option_value_id => $name)
              <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
                {{ $name }}
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @php $left = 24-$column_used_num; @endphp
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
            <td>{{ $product['name'] }}</td>
            <td>{{ $product['quantity'] }}</td>
            @php $column_used_num = 2; @endphp
            @foreach($columns['lumpiaLunchBox']['MainMeal'] ?? [] as $item)
              @php $option_value_id = $item->option_value_id;  @endphp
              <td style="@if ($loop->last) border-right:3px solid black @endif" data-option_value_id="{{ $option_value_id }}">
                @if(!empty($product['product_options']['MainMeal'][$option_value_id]['quantity']))
                  {{ $product['product_options']['MainMeal'][$option_value_id]['quantity'] }}
                @endif
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

            @php $left = 24-$column_used_num; @endphp
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
            @foreach($columns['lumpiaLunchBox']['MainMeal'] ?? [] as $item)
              @php $option_value_id = $item->option_value_id;  @endphp
              <td style="@if ($loop->last) border-right:3px solid black @endif" data-option_value_id="{{ $option_value_id }}">
                @if(!empty($product['product_options']['MainMeal'][$option_value_id]['SubDrinks']))
                  @foreach($product['product_options']['MainMeal'][$option_value_id]['SubDrinks'] as $drink)
                    {{ mb_substr($drink['short_name'],0,1) }}{{ $drink['quantity'] }}
                  @endforeach
                @endif
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
      <table id="guabaoLunchBox" class=" table-bordered border border-dark tr-border-top " style="margin-top:3px;margin-bottom:0px;">
        <thead>
          <tr>
            <td style="width:100px;" class="fw-bold">{{ $order['categories']['guabaoLunchBox']['name'] }}</td>
            <td style="width:24px;" class="fw-bold">小計</td>
            @php $column_used_num = 2; @endphp
            @foreach($columns['guabaoLunchBox']['MainMeal'] ?? [] as $item)
              <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
                {{ $item->short_name }}
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @foreach($order['categories']['guabaoLunchBox']['Columns']['SideDish'] ?? [] as $option_value_id => $name)
              <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
                {{ $name }}
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @php $left = 24-$column_used_num; @endphp
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
          @foreach($order['categories']['guabaoLunchBox']['items'] ?? [] as $product_id => $row)
          <tr>
            <td>{{ $row['name'] }}</td>
            <td>{{ $row['quantity'] }}</td>
            @php $column_used_num = 2; @endphp
            @foreach($columns['guabaoLunchBox']['MainMeal'] ?? [] as $item)
              @php $option_value_id = $item->option_value_id;  @endphp
              <td style="@if ($loop->last) border-right:3px solid black @endif" data-option_value_id="{{ $option_value_id }}">
                @if(!empty($row['product_options']['MainMeal'][$option_value_id]['quantity']))
                  {{ $row['product_options']['MainMeal'][$option_value_id]['quantity'] }}
                @endif
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @foreach($order['categories']['guabaoLunchBox']['Columns']['SideDish'] ?? [] as $option_value_id => $name)
              <td style="@if ($loop->last) border-right:3px solid black @endif" data-option_value_id="{{ $option_value_id }}">
                @if(!empty($row['product_options']['SideDish'][$option_value_id]['quantity']))
                  {{ $row['product_options']['SideDish'][$option_value_id]['quantity'] }}
                @endif
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @php $left = 24-$column_used_num; @endphp
            @for($i = 1; $i <= $left; $i++)
              <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif"> </td>
            @endfor

            @foreach($columns['Drink'] ?? [] as $item)
              @php $option_value_id = $item->option_value_id;  @endphp
              <td style="@if ($loop->last) border-right:3px solid black @endif" data-option_value_id="{{ $option_value_id }}">
                @if(!empty($row['product_options']['Drink'][$option_value_id]['quantity']))
                  {{ $row['product_options']['Drink'][$option_value_id]['quantity'] }}
                @endif
              </td>
              @php $column_used_num++; @endphp
            @endforeach
          </tr>
          <tr>
            <td>飲料</td>
            <td></td>
            @php $column_used_num = 2; @endphp
            @foreach($columns['guabaoLunchBox']['MainMeal'] ?? [] as $item)
              @php $option_value_id = $item->option_value_id;  @endphp
              <td style="@if ($loop->last) border-right:3px solid black @endif" data-option_value_id="{{ $option_value_id }}">
                @if(!empty($row['product_options']['MainMeal'][$option_value_id]['SubDrinks']))
                  @foreach($row['product_options']['MainMeal'][$option_value_id]['SubDrinks'] as $drink)
                    {{ mb_substr($drink['short_name'],0,1) }}{{ $drink['quantity'] }}
                  @endforeach
                @endif
              </td>
              @php $column_used_num++; @endphp
            @endforeach
          </tr>
          @endforeach
        </tbody>
      </table>
      @endif
    <!-- end 刈包盒餐系列 -->

    <!-- 客製盒餐系列 包括潤餅、刈包-->
      @if(!empty($order['categories']['customLunchbox']))
      <table id="customLunchbox" class=" table-bordered border border-dark tr-border-top " style="margin-top:3px;margin-bottom:0px;">
        <thead>
          <tr>
            <td style="width:136px;" class="fw-bold">{{ $order['categories']['customLunchbox']['name'] }}</td>
            <td style="width:24px;" class="fw-bold">小計</td>
            @php $column_used_num = 2; @endphp
            @foreach($columns['MainMeal'] ?? [] as $item)
              <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
                {{ $item->short_name }}
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
          @foreach($order['categories']['customLunchbox']['items'] ?? [] as $product_id => $row)
          <tr>
            <td>{{ $row['name'] }} <BR> 價格: {{ $row['final_total']/$row['quantity'] }} </td>
            <td>{{ $row['quantity'] }}</td>
            @php $column_used_num = 2; @endphp

            @foreach($columns['MainMeal'] ?? [] as $item)
              <td style="@if ($loop->last) border-right:3px solid black @endif">
                @foreach($item->option_value_ids as $option_value_id)
                  @if(!empty($row['product_options']['MainMeal'][$option_value_id]['quantity']))
                    {{ $row['product_options']['MainMeal'][$option_value_id]['quantity'] }}
                  @endif
                @endforeach
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @foreach($order['categories']['customLunchbox']['Columns']['SideDish'] ?? [] as $option_value_id => $name)
              <td style="@if ($loop->last) border-right:3px solid black @endif" data-option_value_id="{{ $option_value_id }}">
                @if(!empty($row['product_options']['SideDish'][$option_value_id]['quantity']))
                  {{ $row['product_options']['SideDish'][$option_value_id]['quantity'] }}
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
                @if(!empty($row['product_options']['Drink'][$option_value_id]['quantity']))
                  {{ $row['product_options']['Drink'][$option_value_id]['quantity'] }}
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
                  @if(!empty($row['product_options']['MainMeal'][$option_value_id]['SubDrinks']))
                    @foreach($row['product_options']['MainMeal'][$option_value_id]['SubDrinks'] as $drink)
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

    <!-- 分享餐系列 -->
      @if(!empty($order['categories']['sharingMeal']))
      <table id="sharingMeal" class=" table-bordered border border-dark tr-border-top " style="margin-top:3px;margin-bottom:0px;">
        <thead>
          <tr>
            <td style="width:100px;" class="fw-bold">{{ $order['categories']['sharingMeal']['name'] }}</td>
            <td style="width:24px;" class="fw-bold">小計</td>
            @php $column_used_num = 2; @endphp
            @foreach($order['categories']['sharingMeal']['Columns']['MainMeal'] ?? [] as $option_value_id => $name)
              <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
                {{ $name }}
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @php $left = 31-$column_used_num; @endphp
            @for($i = 1; $i <= $left; $i++)
              <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif" class="fw-bold"> </td>
            @endfor
          </tr>
        </thead>
        <tbody>
          @foreach($order['categories']['sharingMeal']['items'] ?? [] as $product_id => $row)
          <tr>
            <td>{{ $row['name'] }}</td>
            <td>{{ $row['quantity'] }}</td>
            @php $column_used_num = 2; @endphp
            @foreach($order['categories']['sharingMeal']['Columns']['MainMeal'] ?? [] as $option_value_id => $name)
              <td style="@if ($loop->last) border-right:3px solid black @endif">
                @if(!empty($row['product_options']['MainMeal'][$option_value_id]['quantity']))
                  {{ $row['product_options']['MainMeal'][$option_value_id]['quantity'] }}
                @endif
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @php $left = 31-$column_used_num; @endphp
            @for($i = 1; $i <= $left; $i++)
              <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif"> </td>
            @endfor
          </tr>
          @endforeach
        </tbody>
      </table>
      @endif
    <!-- end 分享餐系列 -->

    <!-- 單點 -->
      @if(!empty($order['categories']['solo']))
      <table id="lunchbox" data-toggle="table" class="table-bordered border border-dark rounded-3 tr-border-top" style="margin-top:3px;margin-bottom:0px; width:100%">
        <tr>
        <td style="width:100px;" class="fw-bold">單點</td>
        <td>
          @foreach($order['categories']['solo']['items']['product_options']['MainMeal'] ?? [] as $option_value_id => $row)
            {{ $row['value'] }}*{{ $row['quantity'] }}, 
          @endforeach

          @foreach($order['categories']['solo']['items']['product_options']['Other'] ?? [] as $option_value_id => $row)
            {{ $row['value'] }}*{{ $row['quantity'] }}, 
          @endforeach


        </td>
        </tr>
      </table>
      @endif
    <!-- end 單點 -->