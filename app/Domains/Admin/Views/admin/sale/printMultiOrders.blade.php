<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>外送訂購單</title>
  <link  href="{{ asset('assets2/public/package/bootstrap/css/bootstrap-5.1.3.min.css') }}" rel="stylesheet" crossorigin="anonymous"/>
  <link  href="{{ asset('assets2/public/package/bootstrap/css/bootstrap-icons-1.10.3.css') }}" rel="stylesheet"/>
  <link  href="{{ asset('assets2/public/package/bootstrap/css/bootstrap-table-1.21.2.min.css') }}" rel="stylesheet">
  <style>
    body {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    .order {
      margin: 2px auto;
      padding: 0px;
      width: 8.5in;
      height: 11in;
      display: table;  /* 讓它像表格，提高 break-after 支援度 */
      page-break-after: always; /* 舊版屬性 */
      break-after: page; /* 新版屬性 */
    }
    @media print {
      @page {
        size: 8.5in 11in;
        margin: 3px;
      }
    }
    .commentSign{
      width: 360px;
      position: absolute;
      top: 30px;
      right: 0px;
    }
   .tr-border-top {
    border-top: 3px solid black !important;
    border-bottom: 3px solid black !important;
    border-left:3px solid black !important;
    border-right:3px solid black !important;
   }
   .tr-border-special {
    border-bottom: 3px solid black !important;
    border-left:3px solid black !important;
    border-right:3px solid black !important;
   }

  </style>

</head>
<body>
  @foreach($orders as $order)
  <section  class="order">
    {{-- 聯絡資料表格 --}}
      <table id="contactInfo" class="border-none contact" style="width: 100%;" >
        <tr style="height: 70px;">
          <td class="col-sm-1"><img width="60" src="{{ asset('image/logo.png') }}" alt="Chinabing" title="Chinabing"/></td>
          <td class="text-center"><span style="font-size: 1.5em;">外送訂購單</span></td>
          <td style="width: 160px;">下單日期 {{ $order['header']->order_date_ymd }}<BR></td>
        </tr>
        <tr>
          <td colspan="3">
          <div class="fw-bold"  style="text-align: right;margin-right: 1.5em">外送人員：________</div>
          <div class="fw-bold"  style="text-align: right;margin-right: 1.5em">出餐時間：________</div>
          <span class="fw-bold">訂單編號：</span><span> {{ $order['header']->code }}</span><span class="fw-bold"> &nbsp;&nbsp;
          @if(!empty ($order['header']->multiple_order) && $order['header']->multiple_order!==null)
          <span class="fw-bold">合併單號：</span><span> {{ $order['header']->multiple_order }}</span>
          @endif
          <BR>
            <span class="fw-bold">送達日：</span><span>{{ $order['header']->delivery_date_ymd }}</span>&nbsp;&nbsp;
            <span class="fw-bold">星期：</span><span>{{ $order['header']->delivery_weekday }}</span>&nbsp;&nbsp;
            <span class="fw-bold">時間：</span><span>{{ $order['header']->delivery_time_range }}</span>&nbsp;
            <span class="fw-bold">手機：</span><span>{{ $order['header']->mobile }}</span>&nbsp;
            <span class="fw-bold">訂購人：</span><span>{{ $order['header']->personal_name }}{{$order['header']->salutation_name}}</span>&nbsp;&nbsp;
          </td>
        </tr>
        <tr>
          <td colspan="3">
            <span class="fw-bold">訂餐公司：</span><span>{{ $order['header']->payment_company }}</span>&nbsp;&nbsp;
            <span class="fw-bold">部門：</span><span>{{ $order['header']->payment_department }}</span>&nbsp;&nbsp;
            <span class="fw-bold">市內電話：</span><span>{{ $order['header']->telephone_full }}</span>&nbsp;&nbsp;
            @if($order['header']->is_payment_tin ==1)
            <span class="fw-bold">統編：</span><span>{{ $order['header']->payment_tin }}</span>
            @else
            <span class="fw-bold">不需要統編</span>
            @endif
          </td>
        </tr>
        <tr>
          <td colspan="3">
            <span class="fw-bold">送達公司：</span><span>{{ $order['header']->shipping_company }}</span>&nbsp;&nbsp;
          <span class="fw-bold">送達地址：</span>
          @if($order['header']->shipping_method == 'shipping_pickup')
            自取
          @else
            {{ $order['header']->shipping_address }}&nbsp;&nbsp;
            @if(!empty($order['header']->shipping_comment))
            @if($order['header']->shipping_comment!=='null')
            @endif
            @endif
            &nbsp;&nbsp;
            <!-- <span class="fw-bold">送達聯絡人：</span><span>{{ $order['header']->shipping_personal_name }}</span>&nbsp;&nbsp;
            <span class="fw-bold">聯絡電話：</span><span>{{ $order['header']->shipping_phone }}</span> -->
          @endif
            <!-- <input type="checkbox"> {{"餐飲總額"}}
            <input type="checkbox"> {{"會議盒餐"}}
            <input type="checkbox"> {{"_______"}} -->
          </td>
        </tr>
        <tr>
          <td colspan="3">
          <!-- <span class="fw-bold">送達地址：</span> -->
          @if($order['header']->shipping_method == 'shipping_pickup')
            <!-- 自取 -->
          @else
            <!-- {{ $order['header']->shipping_address }}&nbsp;&nbsp; -->
            <!-- @if(!empty($order['header']->shipping_company)) -->
            <!-- <span class="fw-bold">送達公司：</span><span>{{ $order['header']->shipping_company }} -->
            <!-- @endif -->
            @if(!empty($order['header']->shipping_personal_name))
            @if($order['header']->shipping_personal_name!=='null' && $order['header']->shipping_personal_name!=='undefined' )
            <span class="fw-bold">送達聯絡①：</span><span>{{ $order['header']->shipping_personal_name }} {{$order['header']->shipping_salutation_name}}</span>&nbsp;&nbsp;
            <span class="fw-bold">聯絡電話①：</span><span>{{ $order['header']->shipping_phone }}</span>
            @endif
            @endif
            @if(!empty($order['header']->shipping_personal_name2))
            @if($order['header']->shipping_personal_name2!=='null' && $order['header']->shipping_personal_name2!=='undefined' )
            <span class="fw-bold">送達聯絡⓶：</span><span>{{ $order['header']->shipping_personal_name2 }} {{$order['header']->shipping_salutation_name2}}</span>&nbsp;&nbsp;
            <span class="fw-bold">聯絡電話⓶：</span><span>{{ $order['header']->shipping_phone2 }}</span>
            @endif
            @endif
          @endif
          </td>
          </tr>
          <!-- <tr>
          <td colspan="3">
          <input type="checkbox"> {{"小卡"}}
          外送五步驟：清檢放統備 &nbsp;&nbsp;簽名：_______________
          </td>
          </tr> -->
      </table>
    {{-- end 聯絡資料表格 --}}

    {{-- 客戶備註 --}}
      <table data-toggle="table" class="table table-bordered border border-dark">
        <tbody>
          <tr style="height: 60px;">
            <td class="align-top"   colspan="10">
            <p style="white-space: pre-wrap;margin:0">餐點備註：{{ $order['header']->extra_comment }} @if($order['header']->status_code != 'Confirmed' && $order['header']->status_code != 'CCP') {{ $order['header']->status_name }} @endif</p>
            
              <div style="text-align: right;">
                <input type="checkbox"> {{"小卡"}}
                外送五步驟：清檢放統備 &nbsp;&nbsp;簽名：_______________
            </div>
          </td>
          </tr>
        </tbody>
      </table>
    {{-- end 客戶備註 --}}
            
    @includeIf('admin.sale.partials.ordersMultiPrintingProducts' . $template, ['order' => $order])

    <!-- 統計 -->
      <table id="lunchbox" data-toggle="table" class=" table-bordered border border-dark rounded-3 tr-border-top" style="margin-top:3px;margin-bottom:0px; width:100%">
        <tr>
        <td style="width:100px;" >統計</td>
        <td>
            @if(!empty($order['statistics']['oilRiceBox']['name']))
              {{ $order['statistics']['oilRiceBox']['name'] }}*{{ $order['statistics']['oilRiceBox']['quantity'] }}, 
            @endif
            @if(!empty($order['statistics']['bento']['name']))
              {{ $order['statistics']['bento']['name'] }}*{{ $order['statistics']['bento']['quantity'] }}, 
            @endif
            @if(!empty($order['statistics']['lunchbox']['name']))
              {{ $order['statistics']['lunchbox']['name'] }}*{{ $order['statistics']['lunchbox']['quantity'] }}, 
            @endif
            @if(!empty($order['statistics']['otherFlavorsBoxedMeal']['name']))
              {{ $order['statistics']['otherFlavorsBoxedMeal']['name'] }}*{{ $order['statistics']['otherFlavorsBoxedMeal']['quantity'] }}, 
            @endif
            
            @foreach($order['statistics']['drinks'] ?? [] as $drink)
            {{ $drink['value'] }}:{{ $drink['quantity'] }}, 
            @endforeach
        </td>
        </tr>
      </table>
    <!-- end 統計 -->
    
    <!-- 客戶簽收 -->
      <table data-toggle="table" class="table table-bordered border border-dark tr-border-top">
        <tr>
          <td class="align-top" style="width: 50%;border-right:3px solid black" >
            <p style="white-space: pre-wrap;margin:0">訂單備註：{{ $order['header']->comment }}</p>
          </td>
          <td class="border-right:3px solid black">
            客戶簽收：<BR>
            @if( $order['header']->payment_method ==='cash')
            <input type="checkbox" id="checkedCheckbox" name="checkedCheckbox" checked>
            {{"現金"}}
            @else
            <input type="checkbox">
            {{"現金"}}
            @endif
            <BR>
            @if( $order['header']->payment_method ==='debt')
            <input type="checkbox" id="checkedCheckbox" name="checkedCheckbox" checked> {{"預計匯款日"}}
            @if(!empty($order['header']->scheduled_payment_date))
            {{ $order['header']->scheduled_payment_date}}
            @else
            {{"未填寫付款日"}}
            @endif
            @else
            <input type="checkbox">
            {{"預計匯款日"}}

            @endif
            <!-- 日期：<BR> -->
            <!-- 外送人員：_______________  出發時間：_______________<BR>
            租借外送機車車號：_______________ &nbsp;&nbsp;&nbsp;

            <input type="checkbox" id="input-chk001" >
            <label for="input-chk001">膠台</label>

            <input type="checkbox" id="input-chk002" >
            <label for="input-chk002">推車</label>

            <input type="checkbox" id="input-chk003" >
            <label for="input-chk003">拉繩</label><BR>
            運費代收人：_______________ 代收金額：______<BR>
            貨款代收人：_______________ 代收金額：______ -->

          </td>
          <td class="" style="padding: 0px;border-left:3px solid black">
            <table  class="table  border no-border border-left:3px solid black" style="width: 100%;">
              @foreach($order['order_totals'] as $code => $order_total)
              <tr  >
                <td class="text-end">{{ $order_total->title }}: </td>
                <td class="text-end">{{ $order_total->value }}</td>
              </tr>
              @endforeach
            </table>
          </td>
        </tr>
      </table>
      外送人員簽名：____________
      <input type="checkbox"> 外送機車/車牌：__________
      <input type="checkbox" id="input-chk001" >
      <label for="input-chk001">膠台</label>
      <input type="checkbox" id="input-chk002" >
      <label for="input-chk002">推車</label>
      <input type="checkbox" id="input-chk003" >
      <label for="input-chk003">拉繩</label>
      @if(!empty($order['header']->order_taker))
      接單人員:  {{$order['header']->order_taker }}
      @else
      接單人員:__________
      @endif
      <br>
      <div class="fw-bold"  style="text-align: right;margin-right: 1.5em">{{'製單時間：'}}{{ now() }}</div>
    <!-- End 客戶簽收 -->
  
  </section>
  @endforeach
</body>
</html>
