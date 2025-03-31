<!doctype html>
<html lang="en">
  <head>
    <base href="{{ $base }}"/>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>每日備料表</title>

    <script src="assets-admin/javascript/jquery/jquery-3.6.1.min.js" type="text/javascript"></script>
    <script src="assets-admin/javascript/bootstrap/js/bootstrap.bundle.min.js?v=5.2.3" type="text/javascript"></script>
    <script src="assets/package/bootstrap/js/bootstrap-table-1.21.2.min.js" type="text/javascript"></script>
    <link  href="assets/package/bootstrap/css/bootstrap-5.1.3.min.css" rel="stylesheet" crossorigin="anonymous"/>
    <link  href="assets/package/bootstrap/css/bootstrap-icons-1.10.3.css" rel="stylesheet"/>
    <link  href="assets/package/bootstrap/css/bootstrap-table-1.21.2.min.css" rel="stylesheet">

  </head>
  <body>

<style>
@media screen, print {
  @page {
    size: A4;
    size: landscape;
    margin: 0px;
  }
  body{
    font-size: 0.8em;
    padding:15px;
  }
  table {
    border-bottom: double;
  }
  td{
    /*padding: 0px !important;*/
    padding-top: 0px !important;
    padding-left: 0px !important;
    padding-right: 3px !important;
    padding-bottom: 0px !important;
  }
}
</style>



<div class="table-responsive text-start mx-auto" >
  需求日期： {{ $statistics['info']['required_date_ymd'] ?? ''}} &nbsp;
  套餐數(盒餐、便當、油飯盒):{{ $statistics['info']['total_package'] ?? 0 }}, &nbsp;
  盒餐:{{ $statistics['info']['total_lunchbox'] ?? 0 }}, &nbsp;
  便當:{{ $statistics['info']['total_bento'] ?? 0 }}, &nbsp;
  油飯盒:{{ $statistics['info']['total_oil_rice_box'] ?? 0 }}, &nbsp;
  <table class="table table-bordered table-hover mx-auto">
    <thead>
      <tr>
        <td class="text-start"> </td>
        <td class="text-start" style="width:100px;">時間</td>
        <td class="text-start" style="width:80px;">訂單編號<br>(末4碼)</td>
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
        <td colspan="3" class="text-start"><span style="font-size: 12px">6吋{{ $statistics['allDay_6in'] ?? 0}}, 大{{ $statistics['allDay_bgb'] ?? 0}}, 小{{ $statistics['allDay_sgb'] ?? 0}}, 春{{ $statistics['allDay_sr'] ?? 0}}</span></td>
        @foreach($statistics['sales_ingredients_table_items'] as $th_product_id => $saleable_product_material_name)
        <td>
          {{ $statistics['allDay'][$th_product_id] ?? 0 }}
        </td>
        @endforeach
      </tr>
      <tr id="option-value-row-0">
        <td colspan="3" class="text-start"><span style="font-size: 12px">6吋{{ $statistics['am_6in'] ?? 0}}, 大{{ $statistics['am_bgb'] ?? 0}}, 小{{ $statistics['am_sgb'] ?? 0}}, 春{{ $statistics['am_sr'] ?? 0}}</span></td>
        @foreach($statistics['sales_ingredients_table_items'] as $th_product_id => $saleable_product_material_name)
          <td>
            {{ $statistics['am'][$th_product_id] ?? 0 }}
          </td>
        @endforeach
      </tr>
      <tr>
        <td colspan="3" class="text-start"><span style="font-size: 12px">6吋{{ $statistics['pm_6in'] ?? 0}}, 大{{ $statistics['pm_bgb'] ?? 0}}, 小{{ $statistics['pm_sgb'] ?? 0}}, 春{{ $statistics['pm_sr'] ?? 0}}</span></td>
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
        <td data-bs-toggle="tooltip" data-bs-html="true" class="text-end" title="
        <div class='text-start'>
            {{ $order['tooltip'] ?? '' }}
        </div>">
          @if(isset($order['order_id']))
              <a href="{{env('APP_URL')}}/#/ordered/{{ $order['order_id'] }}"
                data-bs-toggle="tooltip"
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
  </body>
</html>
