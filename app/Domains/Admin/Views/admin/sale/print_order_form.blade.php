<!doctype html>
<html lang="en">
  <head>
    <base href="{{ $base }}"/>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>外送訂購單</title>

    <script src="{{ asset('assets-admin/javascript/jquery/jquery-3.6.1.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('assets-admin/javascript/bootstrap/js/bootstrap.bundle.min.js?v=5.2.3') }}" type="text/javascript"></script>
    <script src="{{ asset('assets/package/bootstrap/js/bootstrap-table-1.21.2.min.js') }}" type="text/javascript"></script>
    <link  href="{{ asset('assets/package/bootstrap/css/bootstrap-5.1.3.min.css') }}" rel="stylesheet" crossorigin="anonymous"/>
    <link  href="{{ asset('assets/package/bootstrap/css/bootstrap-icons-1.10.3.css') }}" rel="stylesheet"/>
    <link  href="{{ asset('assets/package/bootstrap/css/bootstrap-table-1.21.2.min.css') }}" rel="stylesheet">

  </head>
  <body>
<style>
@media screen, print {
  @page {
    size: A4;
    margin: 2px;
  }
  body{
    font-size: 1em;
    padding:0px;
  }
  table {
    border-bottom: double;
  }
  td{
    padding: 0px !important;
  }
  .contact td{
    padding: 0px !important;
  }

  .wMainMeal{
    width: 9%;
  }
  .underline{
    text-decoration: underline;
  }
  .boderBottom{
    border-bottom: 1px solid black;
  }
  .border-none {
    border-collapse: collapse;
    border: none;
  }
  .commentSign{
    width: 360px;
    position: absolute;
    top: 30px;
    right: 0px;
  }

  body {
    font-family: sourcehanserif, sans-serif;
  }
}
</style>

<?php $orderIndex = 0; ?>

@foreach($orders as $orderData)

<!-- Outer container, full page width and height, red border -->
<table class="border-none" style="margin: 0px;padding: 0px; width: 100%;">
  <tbody>
    <tr>
      <td>
      {{-- 聯絡資料表格 --}}
        <table class="border-none contact" style="width: 100%;" >
          <tr style="height: 70px;">
            <td class="col-sm-1"><img width="60" src="{{ asset('image/logo.png') }}" alt="Chinabing" title="Chinabing"/></td>
            <td class="text-center"><span style="font-size: 1.5em;">外送訂購單</span></td>
            <td style="width: 28%;">訂單編號 {{ $orderData['order']->code }}<BR></td>
          </tr>
          <tr>
            <td colspan="3">
              <span class="fw-bold">送達日：</span><span>{{ $orderData['order']->delivery_date }}</span>&nbsp;&nbsp;
              <span class="fw-bold">星期：</span><span>{{ $orderData['order']->delivery_weekday }}</span>&nbsp;&nbsp;
              <span class="fw-bold">時間：</span><span>{{ $orderData['order']->delivery_time_range }}</span>&nbsp;&nbsp;
              <span class="fw-bold">訂購人：</span><span>{{ $orderData['order']->personal_name }}</span>&nbsp;&nbsp;
              <span class="fw-bold">手機：</span><span>{{ $orderData['order']->mobile }}</span>
            </td>
          </tr>
          <tr>
            <td colspan="3">
              <span class="fw-bold">市內電話：</span><span>{{ $orderData['order']->telephone_text }}</span>&nbsp;&nbsp;
              <span class="fw-bold">訂餐公司：</span><span>{{ $orderData['order']->payment_company }}</span>&nbsp;&nbsp;
              <span class="fw-bold">部門：</span><span>{{ $orderData['order']->payment_department }}</span>&nbsp;&nbsp;
              <span class="fw-bold">統編：</span><span>{{ $orderData['order']->payment_tin }}</span>
            </td>
          </tr>


            <tr>
              <td colspan="3">
              <span class="fw-bold">送達地址：</span>
              @if($orderData['order']->shipping_method == 'shipping_pickup')
                自取
              @else
                {{ $orderData['order']->shipping_address }}&nbsp;&nbsp;
                @if(!empty($orderData['order']->shipping_company))
                <span class="fw-bold">送達公司：</span><span>{{ $orderData['order']->shipping_company }}
                @endif
                &nbsp;&nbsp;<BR>
                <span class="fw-bold">送達聯絡人：</span><span>{{ $orderData['order']->shipping_personal_name }}</span>&nbsp;&nbsp;
                <span class="fw-bold">聯絡電話：</span><span>{{ $orderData['order']->shipping_phone }}</span>
              @endif
              </td>
            </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td>

        {{-- 客戶備註 --}}
        <table data-toggle="table" class="table table-bordered border border-dark">
          <tbody>
            <tr style="height: 60px;">
              <td class="align-top"  colspan="10">客戶備註：{{ $orderData['order']->comment }}
                <div class="commentSign" >外送五步驟：清檢放統備 &nbsp;&nbsp;簽名：{{ $underline }}</div>
              </td>
            </tr>
          </tbody>
        </table>


        @if(!empty($orderData['final_products']))

          {{-- 便當 --}}
          <table data-toggle="table" class="table table-bordered border border-dark" style="margin-top:3px;margin-bottom:0px;">
            <tbody>
              @php 
                $bento_title_showed = 0;
              @endphp
              @foreach($orderData['final_products'] as $order_product)
                @if($order_product['main_category_code'] == 'bento')
                  @if($bento_title_showed == 0)
                    <tr>
                      <td></td>
                      <td style="width:65px;">數量</td>
                      <td class="wMainMeal fw-bold">全素薯</td>
                      <td class="wMainMeal fw-bold">蛋素薯</td>
                      <td class="wMainMeal fw-bold">薯泥</td>
                      <td class="wMainMeal fw-bold">炸蝦</td>
                      <td class="wMainMeal fw-bold">炒雞</td>
                      <td class="wMainMeal fw-bold">酥魚</td>
                      <td class="wMainMeal fw-bold">培根</td>
                      <td class="wMainMeal fw-bold">滷肉</td>
                      <td class="wMainMeal fw-bold">呱呱卷</td>
                    </tr>
                    @php $bento_title_showed = 1; @endphp
                  @endif
                  <tr>
                    <td rowspan="3">{{ $order_product['name'] }}</td>
                    <td rowspan="3">{{ $order_product['quantity'] }}</td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1046]))  
                    {{ $order_product['main_meal']['option_values'][1046]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1047]))  
                    {{ $order_product['main_meal']['option_values'][1047]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1017]))  
                    {{ $order_product['main_meal']['option_values'][1017]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1018]))  
                    {{ $order_product['main_meal']['option_values'][1018]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1019]))  
                    {{ $order_product['main_meal']['option_values'][1019]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1020]))  
                    {{ $order_product['main_meal']['option_values'][1020]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1021]))  
                    {{ $order_product['main_meal']['option_values'][1021]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1022]))  
                    {{ $order_product['main_meal']['option_values'][1022]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1072]))  
                    {{ $order_product['main_meal']['option_values'][1072]['quantity'] ?? 0 }}
                    @endif
                    </td>
                  </tr>
                  <tr>
                    <td colspan="9">
                      @if(!empty($order_product['drink']))
                        @foreach($order_product['drink']['option_values'] as $product_option_value_id => $value)
                          {{ $value['name'] }}*{{ $value['quantity'] }}, 
                        @endforeach
                      @endif
                    </td>
                  </tr>
                  <tr style="height:3px">
                    <td colspan="9">
                      @if(!empty($order_product['comment']))
                        備註：{{ $order_product['comment'] }}
                      @endif
                    </td>
                  </tr>
                @endif
              @endforeach
            </tbody>
          </table>

          {{-- 標準盒餐 --}}
          <table data-toggle="table" class="table table-bordered border border-dark rounded-3" style="margin-top:3px;margin-bottom:0px;">
            <tbody>
              @php $lunchbox_title_showed = 0;@endphp
              @foreach($orderData['final_products'] as $order_product)
                {{-- 標準盒餐 --}}
                @if($order_product['main_category_code'] == 'lunchbox')
                  @if($lunchbox_title_showed == 0)
                    <tr>
                      <td></td>
                      <td style="width:65px;">數量</td>
                      <td class="wMainMeal fw-bold">全素薯</td>
                      <td class="wMainMeal fw-bold">蛋素薯</td>
                      <td class="wMainMeal fw-bold">薯泥</td>
                      <td class="wMainMeal fw-bold">炸蝦</td>
                      <td class="wMainMeal fw-bold">炒雞</td>
                      <td class="wMainMeal fw-bold">酥魚</td>
                      <td class="wMainMeal fw-bold">培根</td>
                      <td class="wMainMeal fw-bold">滷肉</td>
                      <td class="wMainMeal fw-bold">呱呱卷</td>
                    </tr>
                    @php $lunchbox_title_showed = 1; @endphp
                  @endif
                  <tr>
                    <td rowspan="3">{{ $order_product['name'] }}</td>
                    <td rowspan="3">{{ $order_product['quantity'] }}</td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1046]))  
                    {{ $order_product['main_meal']['option_values'][1046]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1047]))  
                    {{ $order_product['main_meal']['option_values'][1047]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1017]))  
                    {{ $order_product['main_meal']['option_values'][1017]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1018]))  
                    {{ $order_product['main_meal']['option_values'][1018]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1019]))  
                    {{ $order_product['main_meal']['option_values'][1019]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1020]))  
                    {{ $order_product['main_meal']['option_values'][1020]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1021]))  
                    {{ $order_product['main_meal']['option_values'][1021]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1022]))  
                    {{ $order_product['main_meal']['option_values'][1022]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1072]))  
                    {{ $order_product['main_meal']['option_values'][1072]['quantity'] ?? 0 }}
                    @endif
                    </td>
                  </tr>
                  <tr>



                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1046]['drink']))
                      @foreach($order_product['main_meal']['option_values'][1046]['drink'] as $drink_option_value_id => $drink)
                      {{ $drink['name'] }}*{{ $drink['quantity'] }}<BR>
                      @endforeach
                      @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1047]['drink']))
                      @foreach($order_product['main_meal']['option_values'][1047]['drink'] as $drink_option_value_id => $drink)
                      {{ $drink['name'] }}*{{ $drink['quantity'] }}<BR>
                      @endforeach
                      @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1017]['drink']))
                      @foreach($order_product['main_meal']['option_values'][1017]['drink'] as $drink_option_value_id => $drink)
                      {{ $drink['name'] }}*{{ $drink['quantity'] }}<BR>
                      @endforeach
                      @endif
                    </td>
                    <td>
                      @if(!empty($order_product['main_meal']['option_values'][1018]['drink']))
                      @foreach($order_product['main_meal']['option_values'][1018]['drink'] as $drink_option_value_id => $drink)
                      {{ $drink['name'] }}*{{ $drink['quantity'] }}<BR>
                      @endforeach
                      @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1019]['drink']))
                      @foreach($order_product['main_meal']['option_values'][1019]['drink'] as $drink_option_value_id => $drink)
                      {{ $drink['name'] }}*{{ $drink['quantity'] }}<BR>
                      @endforeach
                      @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1020]['drink']))
                      @foreach($order_product['main_meal']['option_values'][1020]['drink'] as $drink_option_value_id => $drink)
                      {{ $drink['name'] }}*{{ $drink['quantity'] }}<BR>
                      @endforeach
                      @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1021]['drink']))
                      @foreach($order_product['main_meal']['option_values'][1021]['drink'] as $drink_option_value_id => $drink)
                      {{ $drink['name'] }}*{{ $drink['quantity'] }}<BR>
                      @endforeach
                      @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1022]['drink']))
                      @foreach($order_product['main_meal']['option_values'][1022]['drink'] as $drink_option_value_id => $drink)
                      {{ $drink['name'] }}*{{ $drink['quantity'] }}<BR>
                      @endforeach
                      @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1072]['drink']))
                      @foreach($order_product['main_meal']['option_values'][1072]['drink'] as $drink_option_value_id => $drink)
                      {{ $drink['name'] }}*{{ $drink['quantity'] }}<BR>
                      @endforeach
                      @endif
                    </td>
                  </tr>
                  <tr style="height:3px">
                    <td colspan="9">
                      @if(!empty($order_product['comment']))
                      備註：{{ $order_product['comment'] }}
                      @endif
                    </td>
                  </tr>
                @endif
              @endforeach
            </tbody>
          </table>

          {{-- 單點潤餅 --}}
          <table data-toggle="table" class="table table-bordered border border-dark" style="margin-top:3px;margin-bottom:0px;">
            <tbody>
              @php $burrito_title_showed = 0; @endphp
              @foreach($orderData['final_products'] as $order_product)
                @if($order_product['main_category_code'] == 'burrito3i')
                  @if($bento_title_showed == 0)
                  <tr>
                    <td></td>
                    <td style="width:65px;">數量</td>
                    <td class="wMainMeal fw-bold">全素薯</td>
                    <td class="wMainMeal fw-bold">蛋素薯</td>
                    <td class="wMainMeal fw-bold">薯泥</td>
                    <td class="wMainMeal fw-bold">炸蝦</td>
                    <td class="wMainMeal fw-bold">炒雞</td>
                    <td class="wMainMeal fw-bold">酥魚</td>
                    <td class="wMainMeal fw-bold">培根</td>
                    <td class="wMainMeal fw-bold">滷肉</td>
                  </tr>
                  @endif
              <tr>
                <td rowspan="2">{{ $order_product['name'] }}</td>
                <td rowspan="2">{{ $order_product['quantity'] }}</td>
                <td>
                    @if(!empty($order_product['main_meal']['option_values'][1046]))  
                    {{ $order_product['main_meal']['option_values'][1046]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1047]))  
                    {{ $order_product['main_meal']['option_values'][1047]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1017]))  
                    {{ $order_product['main_meal']['option_values'][1017]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1018]))  
                    {{ $order_product['main_meal']['option_values'][1018]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1019]))  
                    {{ $order_product['main_meal']['option_values'][1019]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1020]))  
                    {{ $order_product['main_meal']['option_values'][1020]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1021]))  
                    {{ $order_product['main_meal']['option_values'][1021]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1022]))  
                    {{ $order_product['main_meal']['option_values'][1022]['quantity'] ?? 0 }}
                    @endif
                    </td>
              </tr>
              @endif
              @endforeach
              <tr>
                <td colspan="8" style="height:3px">
                  @if(!empty($order_product['comment']))
                    <hr>
                    備註：{{ $order_product['comment'] }}
                  @endif
                </td>
              </tr>
            </tbody>
          </table>

          {{-- 其它單點 --}}
          <table data-toggle="table" class="table table-bordered border border-dark" style="margin-top:3px;margin-bottom:0px;">
            <tbody>
              @php 
                $bento_title_showed = 0;
              @endphp
              @foreach($orderData['final_products'] as $order_product)
                @if($order_product['main_category_code'] == 'other_combo')
                  @if($bento_title_showed == 0)
                    <tr>
                      <td></td>
                      <td style="width:65px;">數量</td>
                      <td class="wMainMeal fw-bold">全素薯</td>
                      <td class="wMainMeal fw-bold">蛋素薯</td>
                      <td class="wMainMeal fw-bold">薯泥</td>
                      <td class="wMainMeal fw-bold">炸蝦</td>
                      <td class="wMainMeal fw-bold">炒雞</td>
                      <td class="wMainMeal fw-bold">酥魚</td>
                      <td class="wMainMeal fw-bold">培根</td>
                      <td class="wMainMeal fw-bold">滷肉</td>
                      <td class="wMainMeal fw-bold">呱呱卷</td>
                    </tr>
                    @php $bento_title_showed = 1; @endphp
                  @endif
                  <tr>
                    <td rowspan="3">{{ $order_product['name'] }}</td>
                    <td rowspan="3">{{ $order_product['quantity'] }}</td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1046]))  
                    {{ $order_product['main_meal']['option_values'][1046]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1047]))  
                    {{ $order_product['main_meal']['option_values'][1047]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1017]))  
                    {{ $order_product['main_meal']['option_values'][1017]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1018]))  
                    {{ $order_product['main_meal']['option_values'][1018]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1019]))  
                    {{ $order_product['main_meal']['option_values'][1019]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1020]))  
                    {{ $order_product['main_meal']['option_values'][1020]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1021]))  
                    {{ $order_product['main_meal']['option_values'][1021]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1022]))  
                    {{ $order_product['main_meal']['option_values'][1022]['quantity'] ?? 0 }}
                    @endif
                    </td>
                    <td>無單點</td>
                  </tr>
                  <tr>
                    <td colspan="9">
                      @if(!empty($order_product['drink']))
                        @foreach($order_product['drink']['option_values'] as $product_option_value_id => $value)
                          {{ $value['name'] }}*{{ $value['quantity'] }}, 
                        @endforeach
                      @endif
                    </td>
                  </tr>
                  <tr style="height:3px">
                    <td colspan="9">
                      @if(!empty($order_product['comment']))
                        備註：{{ $order_product['comment'] }}
                      @endif
                    </td>
                  </tr>
                @endif
              @endforeach
            </tbody>
          </table>

          {{-- 餐點備註 --}}
          <table data-toggle="table" class="table table-bordered border border-dark" style="margin-top:3px;margin-bottom:0px;">
            <tbody>
              <tr>
                <td class="align-top" style="height: 50px;">餐點備註：{{ $orderData['order']->extra_comment }}</td>
              </tr>
            </tbody>
          </table>

          {{-- 統計 --}}
          <table data-toggle="table" class="table table-bordered border border-dark" style="margin-top:3px;margin-bottom:0px;">
            <tbody>
              {{-- 統計 --}}
              <tr>
                <td>統計主餐</td>
                <td style="width:65px;">{{ $orderData['statics']['main_meal']['total'] ?? '' }}</td>
                <td class="wMainMeal">{{ $orderData['statics']['main_meal']['option_values'][1046]['quantity'] ?? '' }}</td>
                <td class="wMainMeal">{{ $orderData['statics']['main_meal']['option_values'][1047]['quantity'] ?? '' }}</td>
                <td class="wMainMeal">{{ $orderData['statics']['main_meal']['option_values'][1017]['quantity'] ?? '' }}</td>
                <td class="wMainMeal">{{ $orderData['statics']['main_meal']['option_values'][1018]['quantity'] ?? '' }}</td>
                <td class="wMainMeal">{{ $orderData['statics']['main_meal']['option_values'][1019]['quantity'] ?? '' }}</td>
                <td class="wMainMeal">{{ $orderData['statics']['main_meal']['option_values'][1020]['quantity'] ?? '' }}</td>
                <td class="wMainMeal">{{ $orderData['statics']['main_meal']['option_values'][1021]['quantity'] ?? '' }}</td>
                <td class="wMainMeal">{{ $orderData['statics']['main_meal']['option_values'][1022]['quantity'] ?? '' }}</td>
                <td class="wMainMeal">{{ $orderData['statics']['main_meal']['option_values'][1072]['quantity'] ?? '' }}</td>
              </tr>
              <tr>
                <td>統計其它</td>
                <td colspan="10">
                  @if(!empty($orderData['statics']['drink']))
                    飲料：
                            @foreach($orderData['statics']['drink']['option_values'] as $key =>$drink)
                              {{ $drink['name'] }}*{{ $drink['quantity'] }}, 
                            @endforeach
                  @endif
                </td>
              </tr>
            </tbody>
          </table>
        @endif
        
        {{-- 客戶簽收 --}}
        <table data-toggle="table" class="table table-bordered border border-dark" style="margin-top:3px;margin-bottom:0px;">
          <tr>
            <td class="align-top">
            客戶簽收：{{ $underline }}  日期：<BR>
            外送人員：{{ $underline }}  出發時間：{{ $underline }}<BR>
            租借外送機車車號：{{ $underline }} &nbsp;&nbsp;&nbsp;

            <input type="checkbox" id="input-chk001" >
            <label for="input-chk001">膠台</label>

            <input type="checkbox" id="input-chk002" > 
            <label for="input-chk002">推車</label>

            <input type="checkbox" id="input-chk003" > 
            <label for="input-chk003">拉繩</label><BR>
            運費代收人：{{ $underline }} 代收金額：______<BR>
            貨款代收人：{{ $underline }} 代收金額：______ 

            </td>
            <td class="col-sm-3 align-top" style="padding: 0px;">
              <table data-toggle="table" class="table  border no-border" style="width: 100%;">
                @foreach($orderData['order_totals'] as $code => $order_total)
                <tr class="boderBottom">
                  <td class="text-end">{{ $order_total->title }}: </td>
                  <td class="text-end">{{ $order_total->value }}</td>
                </tr>
                @endforeach
              </table>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </tbody>
</table>

<?php $orderIndex++; ?>

@if( $orderIndex != $countOrders )
<p style="page-break-after: always;" ></p>
@endif

@endforeach

  </body>
</html>