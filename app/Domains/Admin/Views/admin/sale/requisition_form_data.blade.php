
<strong>需求日期： {{ $statistics['info']['required_date_ymd'] ?? ''}}</strong> <BR>
  更新時間： {{ $statistics['info']['cache_created_at'] ?? '' }} &nbsp; 上下午分界點：13:00 開始算下午<BR>
  套餐數:{{ $statistics['info']['total_package'] ?? 0 }}, &nbsp;
  盒餐:{{ $statistics['info']['total_lunchbox'] ?? 0 }}, &nbsp;
  便當：{{ $statistics['info']['total_bento'] ?? 0 }}, &nbsp;
  油飯盒:{{ $statistics['info']['total_oilRiceBox'] ?? 0 }}, &nbsp;
  3吋潤餅:{{ $statistics['info']['total_3inlumpia'] ?? 0 }}, &nbsp;
  6吋潤餅:{{ ceil($statistics['info']['total_6inlumpia']) ?? 0 }}({{ $info['total_3inlumpia'] ?? 0 }}/2), &nbsp;
  小刈包:{{ $statistics['info']['total_small_guabao'] ?? 0 }}, &nbsp;
  大刈包:{{ $statistics['info']['total_big_guabao'] ?? 0 }}, &nbsp;


<div class="table-responsive text-end mx-auto" id="tableContainer">
  <style>
    #tableContainer {
    max-height: 550px; /* 设置表格容器的最大高度 */
    overflow-y: auto; /* 启用垂直滚动条 */
    position: relative; /* 确保相对定位 */
    }
    #tableContainer thead {
    background-color: #f2f2f2; /* 可选：设置表头的背景颜色 */
    position: sticky;
    top: 0; /* 表头初始位置在顶部 */
    z-index: 1; /* 使表头在上方 */
    }
  </style>

  <table class="table table-bordered table-hover mx-auto">
    <thead>
      <tr>
        <td class="text-start"> </td>
        <td class="text-start">時間</td>
        <td class="text-start">訂單編號<br>(末4碼)</td>
        @foreach($statistics['sales_ingredients_table_items'] as $map_product_id => $name)
          <?php
          $characters = mb_str_split($name);
          $new_name = implode('<BR>', $characters);
          ?>
          <td style="width:30px;">{!! $new_name !!}</td>
        @endforeach
      </tr>
    </thead>
    <tbody id="tbody_body_records">
      <tr id="option-value-row-0">
        <td colspan="3" class="text-start">全日6吋潤餅: 307</td>
        @foreach($statistics['sales_ingredients_table_items'] as $th_product_id => $saleable_product_material_name)
        <td>
          {{ $statistics['allDay'][$th_product_id] ?? 0 }}
        </td>
        @endforeach
      </tr>
      <tr id="option-value-row-0">
        <td colspan="3" class="text-start">上午6吋潤餅: 213</td>
        @foreach($statistics['sales_ingredients_table_items'] as $th_product_id => $saleable_product_material_name)
          <td>
            {{ $statistics['am'][$th_product_id] ?? 0 }}
          </td>
        @endforeach
      </tr>
      <tr>
        <td colspan="3" class="text-start">下午6吋潤餅: 94</td>
        @foreach($statistics['sales_ingredients_table_items'] as $th_product_id => $saleable_product_material_name)
          <td>
            {{ $statistics['pm'][$th_product_id] ?? 0 }}
          </td>
        @endforeach
      </tr>
      
      @php $count = 0; @endphp;
      @foreach($statistics['order_list'] ?? [] as $key => $order)
      <tr>
        <td class="text-end" rowspan="2">{{ $key+1 }}</td>
        <td class="text-end">{{ $order['delivery_time_range'] ?? '' }}</td>
        <td class="text-end">
          @if(isset($order['order_id']))
              <a href="{{env('APP_URL')}}/#/ordered/{{ $order['order_id'] }}"
                data-bs-toggle="tooltip"
                title="訂單連結"
                target="_blank">
                {{ $order['order_code']}}
              </a>
          @else
              ''
          @endif
        </td>

        @foreach($statistics['sales_ingredients_table_items'] as $map_product_id => $saleable_product_material_name)
        <td rowspan="2">
            {{ $order['items'][$map_product_id]['quantity'] ?? ''}}
        </td>
        @endforeach
      </tr>
      <tr>
        <td class="text-start" colspan=2>{{ $order['shipping_road_abbr'] }}</td>
      </tr>
      @php $count++; @endphp
      @endforeach

      
    </tbody>
  </table>
</div>
  
