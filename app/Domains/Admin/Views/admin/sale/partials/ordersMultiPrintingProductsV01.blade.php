    <!-- 潤餅便當系列 -->
      @if(!empty($order['categories']['lumpiaBento']))
      <table id="lumpiaBento" class=" table-bordered border border-dark tr-border-top " style="margin-top:3px;margin-bottom:0px;">
        <thead>
          <tr>
            <td style="width:100px;" class="fw-bold">{{ $order['categories']['lumpiaBento']['name'] }}</td>
            <td style="width:24px;" class="fw-bold">小計</td>
            @php $column_used_num = 2; @endphp
            @foreach($order['categories']['lumpiaBento']['Columns']['MainMeal'] ?? [] as $option_value_id => $name)
              <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
                {{ $name }}
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @foreach($order['categories']['lumpiaBento']['Columns']['SecondaryMainMeal'] ?? [] as $option_value_id => $name)
              <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
                {{ $name }}
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @foreach($order['categories']['lumpiaBento']['Columns']['SideDish'] ?? [] as $option_value_id => $name)
              <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
                {{ $name }}
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @foreach($order['categories']['lumpiaBento']['Columns']['Drink'] ?? [] as $option_value_id => $name)
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
          @foreach($order['categories']['lumpiaBento']['items'] ?? [] as $product_id => $row)
          <tr>
            <td>{{ $row['name'] }}</td>
            <td>{{ $row['quantity'] }}</td>
            @php $column_used_num = 2; @endphp
            @foreach($order['categories']['lumpiaBento']['Columns']['MainMeal'] ?? [] as $option_value_id => $name)
              <td style="@if ($loop->last) border-right:3px solid black @endif">
                @if(!empty($row['product_options']['MainMeal'][$option_value_id]['quantity']))
                  {{ $row['product_options']['MainMeal'][$option_value_id]['quantity'] }}
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

            @foreach($order['categories']['lumpiaBento']['Columns']['SideDish'] ?? [] as $option_value_id => $name)
              <td style="@if ($loop->last) border-right:3px solid black @endif">
                @if(!empty($row['product_options']['SideDish'][$option_value_id]['quantity']))
                  {{ $row['product_options']['SideDish'][$option_value_id]['quantity'] }}
                @endif
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @foreach($order['categories']['lumpiaBento']['Columns']['Drink'] ?? [] as $option_value_id => $name)
              <td style="@if ($loop->last) border-right:3px solid black @endif">
                @if(!empty($row['product_options']['Drink'][$option_value_id]['quantity']))
                  {{ $row['product_options']['Drink'][$option_value_id]['quantity'] }}
                @endif
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @php $left = 31-$column_used_num; @endphp
            @for($i = 1; $i <= $left; $i++)
              <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif"> </td>
            @endfor
          </tr>
          @endforeach                                                                                      </tbody>
      </table>
      @endif
    <!-- end 潤餅便當系列 -->

    <!-- 刈包便當系列 -->
      @if(!empty($order['categories']['guabaoBento']))
        <table id="guabaoBento" class=" table-bordered border border-dark tr-border-top " style="margin-top:3px;margin-bottom:0px;">
          <thead>
            <tr>
              <td style="width:100px;" class="fw-bold">{{ $order['categories']['guabaoBento']['name'] }}</td>
              <td style="width:24px;" class="fw-bold">小計</td>
              @php $column_used_num = 2; @endphp
              @foreach($order['categories']['guabaoBento']['Columns']['MainMeal'] ?? [] as $option_value_id => $name)
                <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
                  {{ $name }}
                </td>
                @php $column_used_num++; @endphp
              @endforeach

              @foreach($order['categories']['guabaoBento']['Columns']['SecondaryMainMeal'] ?? [] as $option_value_id => $name)
                <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
                  {{ $name }}
                </td>
                @php $column_used_num++; @endphp
              @endforeach

              @foreach($order['categories']['guabaoBento']['Columns']['SideDish'] ?? [] as $option_value_id => $name)
                <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
                  {{ $name }}
                </td>
                @php $column_used_num++; @endphp
              @endforeach

              @foreach($order['categories']['guabaoBento']['Columns']['Drink'] ?? [] as $option_value_id => $name)
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
            @foreach($order['categories']['guabaoBento']['items'] ?? [] as $product_id => $row)
            <tr>
              <td>{{ $row['name'] }}</td>
              <td>{{ $row['quantity'] }}</td>
              @php $column_used_num = 2; @endphp
              @foreach($order['categories']['guabaoBento']['Columns']['MainMeal'] ?? [] as $option_value_id => $name)
                <td style="@if ($loop->last) border-right:3px solid black @endif">
                  @if(!empty($row['product_options']['MainMeal'][$option_value_id]['quantity']))
                    {{ $row['product_options']['MainMeal'][$option_value_id]['quantity'] }}
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

              @foreach($order['categories']['guabaoBento']['Columns']['SideDish'] ?? [] as $option_value_id => $name)
                <td style="@if ($loop->last) border-right:3px solid black @endif">
                  @if(!empty($row['product_options']['SideDish'][$option_value_id]['quantity']))
                    {{ $row['product_options']['SideDish'][$option_value_id]['quantity'] }}
                  @endif
                </td>
                @php $column_used_num++; @endphp
              @endforeach

              @foreach($order['categories']['guabaoBento']['Columns']['Drink'] ?? [] as $option_value_id => $name)
                <td style="@if ($loop->last) border-right:3px solid black @endif">
                  @if(!empty($row['product_options']['Drink'][$option_value_id]['quantity']))
                    {{ $row['product_options']['Drink'][$option_value_id]['quantity'] }}
                  @endif
                </td>
                @php $column_used_num++; @endphp
              @endforeach

              @php $left = 31-$column_used_num; @endphp
              @for($i = 1; $i <= $left; $i++)
                <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif"> </td>
              @endfor
            </tr>
            @endforeach                                                                                      </tbody>
        </table>
      @endif
    <!-- end 刈包便當系列 -->

    <!-- 潤餅盒餐系列 -->
      @if(!empty($order['categories']['lumpiaLunchBox']))
      <table id="lumpiaLunchBox" class=" table-bordered border border-dark tr-border-top " style="margin-top:3px;margin-bottom:0px;">
        <thead>
          <tr>
            <td style="width:100px;" class="fw-bold">{{ $order['categories']['lumpiaLunchBox']['name'] }}</td>
            <td style="width:24px;" class="fw-bold">小計</td>
            @php $column_used_num = 2; @endphp
            @foreach($order['categories']['lumpiaLunchBox']['Columns']['MainMeal'] ?? [] as $option_value_id => $name)
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

            @foreach($order['categories']['lumpiaLunchBox']['Columns']['Drink'] ?? [] as $option_value_id => $name)
              <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
                {{ $name }}
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @php $left = 31-$column_used_num; @endphp
            @for($i = 1; $i <= $left; $i++)
              <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif"> </td>
            @endfor
          </tr>
        </thead>
        <tbody>
          @foreach($order['categories']['lumpiaLunchBox']['items'] ?? [] as $product_id => $row)
          <tr>
            <td>{{ $row['name'] }}</td>
            <td>{{ $row['quantity'] }}</td>
            @php $column_used_num = 2; @endphp
            @foreach($order['categories']['lumpiaLunchBox']['Columns']['MainMeal'] ?? [] as $option_value_id => $name)
              <td style="@if ($loop->last) border-right:3px solid black @endif" data-option_value_id="{{ $option_value_id }}">
                @if(!empty($row['product_options']['MainMeal'][$option_value_id]['quantity']))
                  {{ $row['product_options']['MainMeal'][$option_value_id]['quantity'] }}
                @endif
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @foreach($order['categories']['lumpiaLunchBox']['Columns']['SideDish'] ?? [] as $option_value_id => $name)
              <td style="@if ($loop->last) border-right:3px solid black @endif" data-option_value_id="{{ $option_value_id }}">
                @if(!empty($row['product_options']['SideDish'][$option_value_id]['quantity']))
                  {{ $row['product_options']['SideDish'][$option_value_id]['quantity'] }}
                @endif
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @foreach($order['categories']['lumpiaLunchBox']['Columns']['Drink'] ?? [] as $option_value_id => $name)
              <td style="@if ($loop->last) border-right:3px solid black @endif" data-option_value_id="{{ $option_value_id }}">
                @if(!empty($row['product_options']['Drink'][$option_value_id]['quantity']))
                  {{ $row['product_options']['Drink'][$option_value_id]['quantity'] }}
                @endif
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @php $left = 31-$column_used_num; @endphp
            @for($i = 1; $i <= $left; $i++)
              <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif"> </td>
            @endfor
          </tr>
          <tr>
            <td>飲料</td>
            <td></td>
            @php $column_used_num = 2; @endphp
            @foreach($order['categories']['lumpiaLunchBox']['Columns']['MainMeal'] ?? [] as $option_value_id => $name)
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
    <!-- end 潤餅盒餐系列 -->

    <!-- 刈包盒餐系列 -->
      @if(!empty($order['categories']['quabaoLunchBox']))
      <table id="quabaoLunchBox" class=" table-bordered border border-dark tr-border-top " style="margin-top:3px;margin-bottom:0px;">
        <thead>
          <tr>
            <td style="width:100px;" class="fw-bold">{{ $order['categories']['quabaoLunchBox']['name'] }}</td>
            <td style="width:24px;" class="fw-bold">小計</td>
            @php $column_used_num = 2; @endphp
            @foreach($order['categories']['quabaoLunchBox']['Columns']['MainMeal'] ?? [] as $option_value_id => $name)
              <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
                {{ $name }}
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @foreach($order['categories']['quabaoLunchBox']['Columns']['SideDish'] ?? [] as $option_value_id => $name)
              <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
                {{ $name }}
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @foreach($order['categories']['quabaoLunchBox']['Columns']['Drink'] ?? [] as $option_value_id => $name)
              <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
                {{ $name }}
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @php $left = 31-$column_used_num; @endphp
            @for($i = 1; $i <= $left; $i++)
              <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif"> </td>
            @endfor
          </tr>
        </thead>
        <tbody>
          @foreach($order['categories']['quabaoLunchBox']['items'] ?? [] as $product_id => $row)
          <tr>
            <td>{{ $row['name'] }}</td>
            <td>{{ $row['quantity'] }}</td>
            @php $column_used_num = 2; @endphp
            @foreach($order['categories']['quabaoLunchBox']['Columns']['MainMeal'] ?? [] as $option_value_id => $name)
              <td style="@if ($loop->last) border-right:3px solid black @endif" data-option_value_id="{{ $option_value_id }}">
                @if(!empty($row['product_options']['MainMeal'][$option_value_id]['quantity']))
                  {{ $row['product_options']['MainMeal'][$option_value_id]['quantity'] }}
                @endif
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @foreach($order['categories']['quabaoLunchBox']['Columns']['SideDish'] ?? [] as $option_value_id => $name)
              <td style="@if ($loop->last) border-right:3px solid black @endif" data-option_value_id="{{ $option_value_id }}">
                @if(!empty($row['product_options']['SideDish'][$option_value_id]['quantity']))
                  {{ $row['product_options']['SideDish'][$option_value_id]['quantity'] }}
                @endif
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @foreach($order['categories']['quabaoLunchBox']['Columns']['Drink'] ?? [] as $option_value_id => $name)
              <td style="@if ($loop->last) border-right:3px solid black @endif" data-option_value_id="{{ $option_value_id }}">
                @if(!empty($row['product_options']['Drink'][$option_value_id]['quantity']))
                  {{ $row['product_options']['Drink'][$option_value_id]['quantity'] }}
                @endif
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @php $left = 31-$column_used_num; @endphp
            @for($i = 1; $i <= $left; $i++)
              <td style="width:24px; @if ($i == $left) border-right:3px solid black @endif"> </td>
            @endfor
          </tr>
          <tr>
            <td>飲料</td>
            <td></td>
            @php $column_used_num = 2; @endphp
            @foreach($order['categories']['quabaoLunchBox']['Columns']['MainMeal'] ?? [] as $option_value_id => $name)
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
      <table id="sharingMeal" class=" table-bordered border border-dark tr-border-top " style="margin-top:3px;margin-bottom:0px;">
        <thead>
          <tr>
            <td style="width:100px;" class="fw-bold">單點</td>
            @php $column_used_num = 2; @endphp

            @foreach($order['categories']['solo']['items']['product_options']['MainMeal'] ?? [] as $option_value_id => $row)
              <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
                {{ $row['value'] }}
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @foreach($order['categories']['solo']['items']['product_options']['Other'] ?? [] as $option_value_id => $row)
              <td style="width:24px;" class="fw-bold">
                {{ $row['value'] }}
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
          <tr>
            <td> </td>
            @php $column_used_num = 2; @endphp

            @foreach($order['categories']['solo']['items']['product_options']['MainMeal'] ?? [] as $option_value_id => $row)
              <td style="width:24px; @if ($loop->last) border-right:3px solid black @endif" class="fw-bold">
                {{ $row['quantity'] }}
              </td>
              @php $column_used_num++; @endphp
            @endforeach
            
            @foreach($order['categories']['solo']['items']['product_options']['Other'] ?? [] as $option_value_id => $row)
              <td style="width:24px;" class="fw-bold">
                {{ $row['quantity'] }}
              </td>
              @php $column_used_num++; @endphp
            @endforeach

            @php $left = 31-$column_used_num; @endphp
            @for($i = 1; $i <= $left; $i++)
              <td style="@if ($i == $left) border-right:3px solid black @endif"> </td>
            @endfor
          </tr>
        </tbody>
      </table>
      @endif
    <!-- end 單點潤餅 -->