<!doctype html>
<html lang="en">
  <head>
    <base href="{{ $base }}"/>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>每日備料表</title>

    <script src="admin-asset/javascript/jquery/jquery-3.6.1.min.js" type="text/javascript"></script>
    <script src="admin-asset/javascript/bootstrap/js/bootstrap.bundle.min.js?v=5.2.3" type="text/javascript"></script>
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
    padding: 0px !important;
    
  }

  .material_item{
    width: 30px;;
  }

}
</style>



<div class="table-responsive text-end mx-auto" >
  <table class="table table-bordered table-hover mx-auto">
    <tbody id="tbody_body_records">
      <tr>
          <td colspan="3"></td>
          @foreach($sales_ingredients_table_items as $product_id => $product_name)
          <td style="width:30px"></td>
          @endforeach
      </tr>
      <tr id="option-value-row-0">
        <td colspan="3">全日統計</td>
        @foreach($sales_ingredients_table_items as $saleable_product_material_id => $saleable_product_material_name)
        <td>
          @if(!empty($mrequisitions['all_day']))
          @foreach($mrequisitions['all_day'] as $material_product_id => $record)
            @if($saleable_product_material_id == $material_product_id)
              {{ $record['quantity'] }}
            @endif
          @endforeach
          @endif
        </td>
        @endforeach
      </tr>
      <tr id="option-value-row-0">
        <td colspan="3">上午統計</td>
        @foreach($sales_ingredients_table_items as $saleable_product_material_id => $saleable_product_material_name)
        <td>
          @if(!empty($mrequisitions['am']))
          @foreach($mrequisitions['am'] as $material_product_id => $record)
            @if($saleable_product_material_id == $material_product_id)
              {{ $record['quantity'] }}
            @endif
          @endforeach
          @endif
        </td>
        @endforeach
      </tr>
      <tr id="option-value-row-0" style="border-bottom: 2px solid black;">
        <td colspan="3">下午統計</td>
        @foreach($sales_ingredients_table_items as $saleable_product_material_id => $saleable_product_material_name)
        <td>
          @if(!empty($mrequisitions['pm']))
          @foreach($mrequisitions['pm'] as $material_product_id => $record)
            @if($saleable_product_material_id == $material_product_id)
              {{ $record['quantity'] }}
            @endif
          @endforeach
          @endif
        </td>
        @endforeach
      </tr>
      <tr style="border-bottom: 2px solid black;">
        <td class="text-start">時間</td>
        <td class="text-start">訂單流水號</td>
        <td class="text-end">地址簡稱</td>
        @foreach($sales_ingredients_table_items as $saleable_product_material_id => $saleable_product_material_name)
          <?php
          $characters = mb_str_split($saleable_product_material_name);
          $saleable_product_material_name = implode('<BR>', $characters);
          ?>
          <td style="width:30px;" class="text-align: center;">
            {!! $saleable_product_material_name !!}
          </td>
        @endforeach
      </tr>

      @if(!empty($mrequisitions['details']))
      <?php $flag = 0; ?>
      @foreach($mrequisitions['details'] as $details_key => $detail_record)

        <?php $dateNum = str_replace(':','',$detail_record['required_date_hi']); ?>

        @if($dateNum > 1259 && $flag == 0)
        <tr style="border-bottom: 2px solid black;">
          <td colspan="34"></td>
        </tr>
        <?php $flag = 1; ?>
        @endif

      <tr class="bordered">
        <td class="text-end">{{ $detail_record['required_date_hi'] }}</td>
        <td class="text-end">{{ $detail_record['order_code'] ?? $detail_record['source_id'] }}</td>
        <td class="text-end">{{ $detail_record['shipping_road_abbr'] }}</td>
        @foreach($sales_ingredients_table_items as $product_id => $product_name)
        <td>
        {{ $detail_record['items'][$product_id]['quantity'] ?? ''}}
        </td>
        @endforeach
      </tr>



      @endforeach
      @endif


      </tbody>
  </table>
</div>
  </body>
</html>