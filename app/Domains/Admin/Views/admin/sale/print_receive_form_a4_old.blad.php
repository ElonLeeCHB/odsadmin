<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>外送訂購單</title>
  <script src="{{ asset('assets-admin/javascript/jquery/jquery-3.6.1.min.js') }}" differ></script>
  <script src="{{ asset('assets-admin/javascript/bootstrap/js/bootstrap.bundle.min.js?v=5.2.3') }}" differ></script>
  <script src="{{ asset('assets/package/bootstrap/js/bootstrap-table-1.21.2.min.js') }}" differ></script>
  <link  href="{{ asset('assets/package/bootstrap/css/bootstrap-5.1.3.min.css') }}" rel="stylesheet" crossorigin="anonymous"/>
  <link  href="{{ asset('assets/package/bootstrap/css/bootstrap-icons-1.10.3.css') }}" rel="stylesheet"/>
  <link  href="{{ asset('assets/package/bootstrap/css/bootstrap-table-1.21.2.min.css') }}" rel="stylesheet">
  <style>
    body {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    #printableArea {
        margin: 2px auto;
        padding: 0px;
    }
    @media screen, print {
        #printableArea {
            width: 202mm; /* A4 width */
            height: 290mm; /* A4 height */
        }
        @page {
            size: A4;
            margin: 2px;
        }
    }

    .commentSign{
      width: 360px;
      position: absolute;
      top: 30px;
      right: 0px;
    }
  </style>
</head>
<body>
    <div id="printableArea">
      {{-- 聯絡資料表格 --}}
      <table class="border-none contact" style="width: 100%;" >
        <tr style="height: 70px;">
          <td class="col-sm-1"><img width="60" src="{{ asset('image/logo.png') }}" alt="Chinabing" title="Chinabing"/></td>
          <td class="text-center"><span style="font-size: 1.5em;">外送訂購單</span></td>
          <td style="width: 160px;">訂單編號 {{ $order->code }}<BR></td>
        </tr>
        <tr>
          <td colspan="3">
            <span class="fw-bold">送達日：</span><span>{{ $order->delivery_date }}</span>&nbsp;&nbsp;
            <span class="fw-bold">星期：</span><span>{{ $order->delivery_weekday }}</span>&nbsp;&nbsp;
            <span class="fw-bold">時間：</span><span>{{ $order->delivery_time_range }}</span>&nbsp;&nbsp;
            <span class="fw-bold">訂購人：</span><span>{{ $order->personal_name }}</span>&nbsp;&nbsp;
            <span class="fw-bold">手機：</span><span>{{ $order->mobile }}</span>
          </td>
        </tr>
        <tr>
          <td colspan="3">
            <span class="fw-bold">市內電話：</span><span>{{ $order->telephone_full }}</span>&nbsp;&nbsp;
            <span class="fw-bold">訂餐公司：</span><span>{{ $order->payment_company }}</span>&nbsp;&nbsp;
            <span class="fw-bold">部門：</span><span>{{ $order->payment_department }}</span>&nbsp;&nbsp;
            <span class="fw-bold">統編：</span><span>{{ $order->payment_tin }}</span>
          </td>
        </tr>
        <tr>
          <td colspan="3">
          <span class="fw-bold">送達地址：</span>
          @if($order->shipping_method == 'shipping_pickup')
            自取
          @else
            {{ $order->address }}&nbsp;&nbsp;
            @if(!empty($order->shipping_company))
            <span class="fw-bold">送達公司：</span><span>{{ $order->shipping_company }}
            @endif
            &nbsp;&nbsp;<BR>
            <span class="fw-bold">送達聯絡人：</span><span>{{ $order->shipping_personal_name }}</span>&nbsp;&nbsp;
            <span class="fw-bold">聯絡電話：</span><span>{{ $order->shipping_phone }}</span>
          @endif
          </td>
          </tr>
      </table>

      {{-- 客戶備註 --}}
      <table data-toggle="table" class="table table-bordered border border-dark">
        <tbody>
          <tr style="height: 60px;">
            <td class="align-top"  colspan="10">客戶備註：{{ $order->comment }}
              <div class="commentSign" >外送五步驟：清檢放統備 &nbsp;&nbsp;簽名：_______________</div>
            </td>
          </tr>
        </tbody>
      </table>

      {{-- 餐點備註 --}}
      <table data-toggle="table" class="table table-bordered border border-dark" style="margin-top:3px;margin-bottom:0px;">
        <tbody>
          <tr>
            <td class="align-top" style="height: 50px;">餐點備註：{{ $order->extra_comment }}</td>
          </tr>
        </tbody>
      </table>

      {{-- 統計 --}}
      <table data-toggle="table" class="table table-bordered border border-dark" style="margin-top:3px;margin-bottom:0px;">
        <tbody>
          <tr>
            <td>統計資料</td>
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
            <td class="wMainMeal fw-bold">雙份油飯</td>
          </tr>
          {{-- 統計 --}}
          <tr>
            <td>主餐</td>
            <td style="width:65px;">{{ $statics['main_meal']['total'] ?? '' }}</td>
            <td class="wMainMeal">{{ $statics['main_meal']['option_values'][1046]['quantity'] ?? '' }}</td>
            <td class="wMainMeal">{{ $statics['main_meal']['option_values'][1047]['quantity'] ?? '' }}</td>
            <td class="wMainMeal">{{ $statics['main_meal']['option_values'][1017]['quantity'] ?? '' }}</td>
            <td class="wMainMeal">{{ $statics['main_meal']['option_values'][1018]['quantity'] ?? '' }}</td>
            <td class="wMainMeal">{{ $statics['main_meal']['option_values'][1019]['quantity'] ?? '' }}</td>
            <td class="wMainMeal">{{ $statics['main_meal']['option_values'][1020]['quantity'] ?? '' }}</td>
            <td class="wMainMeal">{{ $statics['main_meal']['option_values'][1021]['quantity'] ?? '' }}</td>
            <td class="wMainMeal">{{ $statics['main_meal']['option_values'][1022]['quantity'] ?? '' }}</td>
            <td class="wMainMeal">{{ $statics['main_meal']['option_values'][1072]['quantity'] ?? '' }}</td>
            <td class="wMainMeal">{{ $statics['main_meal']['option_values'][1092]['quantity'] ?? '' }}</td>

          <tr>
            <td>其它</td>
            <td colspan="10">
              @if(!empty($statics['drink']))
                飲料：
                        @foreach($statics['drink']['option_values'] as $key =>$drink)
                          {{ $drink['name'] }}*{{ $drink['quantity'] }},
                        @endforeach
              @endif
            </td>
          </tr>
        </tbody>
      </table>

      {{-- 客戶簽收 --}}
      <table data-toggle="table" class="table table-bordered border border-dark" style="margin-top:3px;margin-bottom:0px;">
        <tr>
          <td class="align-top">
          客戶簽收：_______________  日期：<BR>
          外送人員：_______________  出發時間：_______________<BR>
          租借外送機車車號：_______________ &nbsp;&nbsp;&nbsp;

          <input type="checkbox" id="input-chk001" >
          <label for="input-chk001">膠台</label>

          <input type="checkbox" id="input-chk002" >
          <label for="input-chk002">推車</label>

          <input type="checkbox" id="input-chk003" >
          <label for="input-chk003">拉繩</label><BR>
          運費代收人：_______________ 代收金額：______<BR>
          貨款代收人：_______________ 代收金額：______

          </td>
          <td class="col-sm-3 align-top" style="padding: 0px;">
            <table data-toggle="table" class="table  border no-border" style="width: 100%;">
              @foreach($order_totals as $code => $order_total)
              <tr class="boderBottom">
                <td class="text-end">{{ $order_total->title }}: </td>
                <td class="text-end">{{ $order_total->value }}</td>
              </tr>
              @endforeach
            </table>
          </td>
        </tr>
      </table>


      @if(!empty($final_products))
        {{-- 便當 --}}
        <table data-toggle="table" class="table table-bordered border border-dark" style="margin-top:3px;margin-bottom:0px;">
          <tbody>
            @php
              $bento_title_showed = 0;
            @endphp
            @foreach($final_products as $key =>$order_product)
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
                  <td>{{ $order_product['name'] }}</td>
                  <td>{{ $order_product['quantity'] }}</td>
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

                {{-- 如果有飲料 --}}
                @if(!empty($order_product['drink']))
                <tr>
                  <td colspan="11"><span style="font-size: 10px;">飲料：</span>

                      @foreach($order_product['drink']['option_values'] as $product_option_value_id => $value)
                        {{ $value['name'] }}*{{ $value['quantity'] }},
                      @endforeach

                  </td>
                </tr>
                @endif

                {{-- 如果有備註 --}}
                @if(!empty($order_product['comment']))
                <tr style="height:3px">
                  <td colspan="11"><span style="font-size: 10px;">商品備註：</span>{{ $order_product['comment'] }}</td>
                </tr>
                @endif
                @php unset($final_products[$key]) @endphp
              @endif
            @endforeach
          </tbody>
        </table>

        {{-- 標準盒餐 --}}
        <table data-toggle="table" class="table table-bordered border border-dark rounded-3" style="margin-top:3px;margin-bottom:0px;">
          <tbody>
            @php $lunchbox_title_showed = 0;@endphp
            @foreach($final_products as $key => $order_product)
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
                  <td>{{ $order_product['name'] }}</td>
                  <td>{{ $order_product['quantity'] }}</td>
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
                  <td></td>
                  <td></td>
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
                @if(!empty($order_product['comment']))
                <tr style="height:3px">
                  <td colspan="11"><span style="font-size: 10px;">商品備註：</span>{{ $order_product['comment'] }}</td>
                </tr>
                @endif

                @php unset($final_products[$key]) @endphp
              @endif
            @endforeach
          </tbody>
        </table>


        {{-- 油飯 --}}
        <table data-toggle="table" class="table table-bordered border border-dark" style="margin-top:3px;margin-bottom:0px;">
          <tbody>
            @php
              $riceBox_title_showed = 1;
            @endphp
            @foreach($final_products as $key =>$order_product)
              @if($order_product['main_category_code'] == 'riceBox')
                @if($riceBox_title_showed == 1)
                  <tr>
                    <td></td>
                    <td style="width:65px;">數量</td>
                    <td class="wMainMeal fw-bold">雙份油飯</td>


                  </tr>
                  @php $bento_title_showed = 1; @endphp
                @endif
                <tr>
                  <td>{{ $order_product['name'] }}</td>
                  <td>{{ $order_product['quantity'] }}</td>
                  <td>
                  @if(!empty($order_product['main_meal']['option_values'][1092]))
                  {{ $order_product['main_meal']['option_values'][1092]['quantity'] ?? 0 }}
                  @endif
                  </td>



                </tr>

                {{-- 如果有飲料 --}}
                @if(!empty($order_product['drink']))
                <tr>
                  <td colspan="11"><span style="font-size: 10px;">飲料：</span>

                      @foreach($order_product['drink']['option_values'] as $product_option_value_id => $value)
                        {{ $value['name'] }}*{{ $value['quantity'] }},
                      @endforeach

                  </td>
                </tr>
                @endif

                {{-- 如果有備註 --}}
                @if(!empty($order_product['comment']))
                <tr style="height:3px">
                  <td colspan="11"><span style="font-size: 10px;">商品備註：</span>{{ $order_product['comment'] }}</td>
                </tr>
                @endif
                @php unset($final_products[$key]) @endphp
              @endif
            @endforeach
          </tbody>
        </table>





        {{-- 其它商品組 --}}
        <table data-toggle="table" class="table table-bordered border border-dark" style="margin-top:3px;margin-bottom:0px;">
          @foreach($final_products as $key => $order_product)
          <tr>
            <td>其它商品組：
            @foreach($order_product['product_options'] as $option_name => $options)
            【{{ $option_name }}】

              @foreach($options as $option_value_name => $quantity)
                {{ $option_value_name }}*{{ $quantity }}

              @endforeach
            @endforeach

            </td>
          </tr>
          @endforeach
        </table>
      @endif

    </div>


</body>
</html>
