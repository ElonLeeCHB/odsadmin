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

套餐數:{{ $statics['info']['total_package'] ?? 0 }}; &nbsp;
盒餐:{{ $statics['info']['total_lunchbox'] ?? 0 }}; &nbsp;
便當:{{ $statics['info']['total_bento'] ?? 0 }}; &nbsp;
油飯盒:{{ $statics['info']['total_oilRiceBox'] ?? 0 }}; &nbsp; 
3吋潤餅:{{ $statics['info']['total_3inlumpia'] ?? 0 }}; &nbsp;
小刈包:{{ $statics['info']['total_small_guabao'] ?? 0 }}; &nbsp;
大刈包:{{ $statics['info']['total_big_guabao'] ?? 0 }};  
<div class="table-responsive text-end mx-auto" >
  <table class="table table-bordered table-hover mx-auto">
    <tbody id="tbody_body_records">
      <tr id="option-value-row-0">
        <td colspan="2">全日</td>
        @foreach($sales_ingredients_table_items as $saleable_product_material_id => $saleable_product_material_name)
        <td style="width:31px;">
          @if(!empty($statics['allDay']))
          @foreach($statics['allDay'] as $material_product_id => $record)
            @if($saleable_product_material_id == $material_product_id)
              {{ $record['quantity'] }}
            @endif
          @endforeach
          @endif
        </td>
        @endforeach
      </tr>
      <tr id="option-value-row-0">
        <td colspan="2">上午</td>
        @foreach($sales_ingredients_table_items as $saleable_product_material_id => $saleable_product_material_name)
        <td>
          @if(!empty($statics['am']))
          @foreach($statics['am'] as $material_product_id => $record)
            @if($saleable_product_material_id == $material_product_id)
              {{ $record['quantity'] }}
            @endif
          @endforeach
          @endif
        </td>
        @endforeach
      </tr>
      <tr id="option-value-row-0" style="border-bottom: 2px solid black;">
        <td colspan="2">下午</td>
        @foreach($sales_ingredients_table_items as $saleable_product_material_id => $saleable_product_material_name)
        <td>
          @if(!empty($statics['pm']))
          @foreach($statics['pm'] as $material_product_id => $record)
            @if($saleable_product_material_id == $material_product_id)
              {{ $record['quantity'] }}
            @endif
          @endforeach
          @endif
        </td>
        @endforeach
      </tr>
      <tr style="border-bottom: 2px solid black;">
        <td class="text-start" colspan="2">時間</td>
        @foreach($sales_ingredients_table_items as $saleable_product_material_id => $saleable_product_material_name)
          <?php
          $characters = mb_str_split($saleable_product_material_name);
          $saleable_product_material_name = implode('<BR>', $characters);
          ?>
          <td class="text-align: center;">
            {!! $saleable_product_material_name !!}
          </td>
        @endforeach
      </tr>

      @if(!empty($statics['orders']))
        <?php $flag = 0; ?>
        @foreach($statics['orders'] as $details_key => $order)

          @php
            $required_date_hi = \Carbon\Carbon::parse($order['required_date'])->format('H:i');
            $required_date_hi_num = \Carbon\Carbon::parse($order['required_date'])->format('Hi');
          @endphp

          @if($required_date_hi_num > 1300 && $flag == 0)
          <tr style="border-bottom: 2px solid black;">
            <td colspan="34"></td>
          </tr>
          <?php $flag = 1; ?>
          @endif

          <tr class="bordered">
            <td class="text-end">{{ $required_date_hi ?? ''}}</td>
            <td class="text-end">{{ $order['order_code'] ?? $order['source_id'] }}</td>
            @foreach($sales_ingredients_table_items as $product_id => $product_name)
            <td rowspan=2>
            {{ $order['items'][$product_id]['quantity'] ?? ''}}
            </td>
            @endforeach
          </tr>

          <tr>
            <td class="text-start" colspan=2>{{ $order['shipping_road_abbr'] }}</td>
          </tr>
        @endforeach
      @endif

      </tbody>
  </table>
</div>
  </body>
</html>
