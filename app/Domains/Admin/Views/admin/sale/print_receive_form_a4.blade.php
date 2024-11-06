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
@foreach($orders as $key =>$order)
    <div id="printableArea">
      {{-- 聯絡資料表格 --}}
      <table class="border-none contact" style="width: 100%;" >
        <tr style="height: 70px;">
          <td class="col-sm-1"><img width="60" src="{{ asset('image/logo.png') }}" alt="Chinabing" title="Chinabing"/></td>
          <td class="text-center"><span style="font-size: 1.5em;">外送訂購單</span></td>
          <td style="width: 160px;">下單日期 {{ $order['order']->order_date }}<BR></td>
        </tr>
        <tr>
          <td colspan="3">
          <div class="fw-bold"  style="text-align: right;margin-right: 1.5em">外送人員：________</div>
          <div class="fw-bold"  style="text-align: right;margin-right: 1.5em">出餐時間：________</div>
          <span class="fw-bold">訂單編號：</span><span> {{ $order['order']->code }}</span><span class="fw-bold"> &nbsp;&nbsp;
          @if(!empty ($order['order']->multiple_order) && $order['order']->multiple_order!==null)
          <span class="fw-bold">合併單號：</span><span> {{ $order['order']->multiple_order }}</span>
          @endif
          <BR>
            <span class="fw-bold">送達日：</span><span>{{ $order['order']->delivery_date }}</span>&nbsp;&nbsp;
            <span class="fw-bold">星期：</span><span>{{ $order['order']->delivery_weekday }}</span>&nbsp;&nbsp;
            <span class="fw-bold">時間：</span><span>{{ $order['order']->delivery_time_range }}</span>&nbsp;
            <span class="fw-bold">手機：</span><span>{{ $order['order']->mobile }}</span>&nbsp;
            <span class="fw-bold">訂購人：</span><span>{{ $order['order']->personal_name }}{{$order['order']->salutation_id}}</span>&nbsp;&nbsp;
          </td>
        </tr>
        <tr>
          <td colspan="3">
            <span class="fw-bold">訂餐公司：</span><span>{{ $order['order']->payment_company }}</span>&nbsp;&nbsp;
            <span class="fw-bold">部門：</span><span>{{ $order['order']->payment_department }}</span>&nbsp;&nbsp;
            <span class="fw-bold">市內電話：</span><span>{{ $order['order']->telephone_full }}</span>&nbsp;&nbsp;
            @if($order['order']->is_payment_tin ==1)
            <span class="fw-bold">統編：</span><span>{{ $order['order']->payment_tin }}</span>
            @else
            <span class="fw-bold">不需要統編</span>
            @endif
          </td>
        </tr>
        <tr>
          <td colspan="3">
            <span class="fw-bold">送達公司：</span><span>{{ $order['order']->shipping_comment }}
          <span class="fw-bold">送達地址：</span>
          @if($order['order']->shipping_method == 'shipping_pickup')
            自取
          @else
            {{ $order['order']->address }}&nbsp;&nbsp;
            @if(!empty($order['order']->shipping_comment))
            @if($order['order']->shipping_comment!=='null')
            @endif
            @endif
            &nbsp;&nbsp;
            <!-- <span class="fw-bold">送達聯絡人：</span><span>{{ $order['order']->shipping_personal_name }}</span>&nbsp;&nbsp;
            <span class="fw-bold">聯絡電話：</span><span>{{ $order['order']->shipping_phone }}</span> -->
          @endif
            <!-- <input type="checkbox"> {{"餐飲總額"}}
            <input type="checkbox"> {{"會議盒餐"}}
            <input type="checkbox"> {{"_______"}} -->
          </td>
        </tr>
        <tr>
          <td colspan="3">
          <!-- <span class="fw-bold">送達地址：</span> -->
          @if($order['order']->shipping_method == 'shipping_pickup')
            <!-- 自取 -->
          @else
            <!-- {{ $order['order']->address }}&nbsp;&nbsp; -->
            <!-- @if(!empty($order['order']->shipping_company)) -->
            <!-- <span class="fw-bold">送達公司：</span><span>{{ $order['order']->shipping_company }} -->
            <!-- @endif -->
            @if(!empty($order['order']->shipping_personal_name))
            @if($order['order']->shipping_personal_name!=='null' && $order['order']->shipping_personal_name!=='undefined' )
            <span class="fw-bold">送達聯絡①：</span><span>{{ $order['order']->shipping_personal_name }} {{$order['order']->shipping_salutation_id}}</span>&nbsp;&nbsp;
            <span class="fw-bold">聯絡電話①：</span><span>{{ $order['order']->shipping_phone }}</span>
            @endif
            @endif
            @if(!empty($order['order']->shipping_personal_name2))
            @if($order['order']->shipping_personal_name2!=='null' && $order['order']->shipping_personal_name2!=='undefined' )
            <span class="fw-bold">送達聯絡⓶：</span><span>{{ $order['order']->shipping_personal_name2 }} {{$order['order']->shipping_salutation_id2}}</span>&nbsp;&nbsp;
            <span class="fw-bold">聯絡電話⓶：</span><span>{{ $order['order']->shipping_phone2 }}</span>
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

      {{-- 客戶備註 --}}
      <table data-toggle="table" class="table table-bordered border border-dark">
        <tbody>
          <tr style="height: 60px;">
            <td class="align-top"   colspan="10">
            <p style="white-space: pre-wrap;margin:0">餐點備註：{{ $order['order']->extra_comment }}</p>
              <div style="text-align: right;">
                <input type="checkbox"> {{"小卡"}}
                外送五步驟：清檢放統備 &nbsp;&nbsp;簽名：_______________
            </div>
          </td>
          </tr>
        </tbody>
      </table>

      <!-- {{-- 餐點備註 --}}
      <table data-toggle="table" class="table table-bordered border border-dark" style="margin-top:3px;margin-bottom:0px;">
        <tbody>
          <tr>
            <td class="align-top" style="height: 50px;">餐點備註：{{ $order['order']->extra_comment }}</td>
          </tr>
        </tbody>
      </table> -->

      {{-- 統計 --}}
      <!-- <table data-toggle="table" class="table table-bordered border border-dark" style="margin-top:3px;margin-bottom:0px;">
        <tbody>
          <tr>
            <td>統計資料</td>
            <td style="width:65px;">數量</td>
            <td class=" fw-bold">全素</td>
            <td class=" fw-bold">蛋素</td>
            <td class=" fw-bold">薯泥</td>
            <td class=" fw-bold">炸蝦</td>
            <td class=" fw-bold">末雞</td>
            <td class=" fw-bold">酥魚</td>
            <td class=" fw-bold">培根</td>
            <td class=" fw-bold">滷肉</td>
            <td class=" fw-bold">呱呱卷</td>
            <td class=" fw-bold">雙份油飯</td>
          </tr>
          {{-- 統計 --}}
          <tr>
            <td>主餐</td>
            <td style="width:65px;">{{ $statics['main_meal']['total'] ?? '' }}</td>
            <td class="">{{ $statics['main_meal']['option_values'][1046]['quantity'] ?? '' }}</td>
            <td class="">{{ $statics['main_meal']['option_values'][1047]['quantity'] ?? '' }}</td>
            <td class="">{{ $statics['main_meal']['option_values'][1017]['quantity'] ?? '' }}</td>
            <td class="">{{ $statics['main_meal']['option_values'][1018]['quantity'] ?? '' }}</td>
            <td class="">{{ $statics['main_meal']['option_values'][1019]['quantity'] ?? '' }}</td>
            <td class="">{{ $statics['main_meal']['option_values'][1020]['quantity'] ?? '' }}</td>
            <td class="">{{ $statics['main_meal']['option_values'][1021]['quantity'] ?? '' }}</td>
            <td class="">{{ $statics['main_meal']['option_values'][1022]['quantity'] ?? '' }}</td>
            <td class="">{{ $statics['main_meal']['option_values'][1072]['quantity'] ?? '' }}</td>
            <td class="">{{ $statics['main_meal']['option_values'][1092]['quantity'] ?? '' }}</td>

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
      </table> -->



      @if(!empty($order['final_products']))
      {{-- 油飯 --}}
        <table data-toggle="table" class=" table-bordered border border-dark tr-border-top " style="margin-top:3px;margin-bottom:0px;">
          <tbody>
            @php
              $oilRiceBox_title_showed = 1;
            @endphp
            @foreach($order['final_products'] as $key =>$order_product)
            @if($order_product['main_category_code'] == 'oilRiceBox')
              @if($oilRiceBox_title_showed == 1)
                  <tr>
                    <td  style="width:115px;">油飯便當系列</td>
                    <td style="width:24px;" class=" fw-bold">小計</td>
                    <!-- <td class=" fw-bold">極品</td> -->
                    <td style="width:24px;" class=" fw-bold">控肉</td>
                    <!-- <td style="width:24px;" class=" fw-bold">雞胸</td> -->
                    <td style="width:24px;" class=" fw-bold">雞翅</td>
                    <td style="width:24px;" class=" fw-bold">雞腿</td>
                    <td style="width:24px;" class=" fw-bold">滷牛</td>
                    <td style="width:24px;" class=" fw-bold">鮭魚</td>
                    <td style="width:24px;" class=" fw-bold">牛丸</td>
                    <!-- <td style="width:24px;" class=" fw-bold">贈豆</td> -->
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;border-right:3px solid black" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold">微豆</td>
                    <td style="width:24px;" class=" fw-bold">無豆</td>
                    <td style="width:24px;" class=" fw-bold">紅茶</td>
                    <td style="width:24px;" class=" fw-bold">奶茶</td>
                    <td style="width:24px;" class=" fw-bold">濃湯</td>
                    <td style="width:24px;" class=" fw-bold">甜湯</td>
                    <td style="width:24px;" class=" fw-bold">贈花</td>

                  </tr>
                  @endif
                  @php $oilRiceBox_title_showed = 0; @endphp
                <tr>
                  <td>{{ $order_product['name'] }}</td>
                  <td>{{ $order_product['quantity'] }}</td>
                  <!-- <td>
                  @if(!empty($order_product['main_meal']['option_values'][1092]))
                  {{ $order_product['main_meal']['option_values'][1092]['quantity'] ?? 0 }}
                  @endif
                  </td> -->
                  <td>
                    @if(!empty($order_product['product_options']['副主餐']['控肉']))
                  {{ $order_product['product_options']['副主餐']['控肉'] ?? 0 }}
                  @endif
                    </td>
                  <!-- <td>
                    @if(!empty($order_product['product_options']['副主餐']['清蒸嫰雞胸']))
                  {{ $order_product['product_options']['副主餐']['清蒸嫰雞胸'] ?? 0 }}
                  @endif
                    </td> -->
                    <td>
                    @if(!empty($order_product['product_options']['配菜']['紐澳良雞翅']))
                  {{ $order_product['product_options']['配菜']['紐澳良雞翅'] ?? 0 }}
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['product_options']['副主餐']['滷雞腿']))
                  {{ $order_product['product_options']['副主餐']['滷雞腿'] ?? 0 }}
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['product_options']['副主餐']['滷牛腱']))
                  {{ $order_product['product_options']['副主餐']['滷牛腱'] ?? 0 }}
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['product_options']['副主餐']['鮭魚']))
                  {{ $order_product['product_options']['副主餐']['鮭魚'] ?? 0 }}
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['product_options']['副主餐']['紅酒牛肉丸']))
                  {{ $order_product['product_options']['副主餐']['紅酒牛肉丸'] ?? 0 }}
                  @endif
                    </td>
                    <!-- <td> @if(!empty($order_product['product_options']['配菜']['贈品豆花']))
                  {{ $order_product['product_options']['配菜']['贈品豆花'] ?? 0 }}
                  @endif </td> -->
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td style="border-right:3px solid black"></td>
                    <td>
                    @if(!empty($order_product['drink']['option_values']['1023']))
                    @if(!empty($order_product['drink']['option_values']['1023']['name']==='微豆'))
                  {{ $order_product['drink']['option_values']['1023']['quantity']}}
                  @endif
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['drink']['option_values']['1024']))
                    @if(!empty($order_product['drink']['option_values']['1024']['name']==='無豆'))
                  {{ $order_product['drink']['option_values']['1024']['quantity']}}
                  @endif
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['drink']['option_values']['1025']))
                    @if(!empty($order_product['drink']['option_values']['1025']['name']==='紅茶'))
                  {{ $order_product['drink']['option_values']['1025']['quantity']}}
                  @endif
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['drink']['option_values']['1026']))
                    @if(!empty($order_product['drink']['option_values']['1026']['name']==='奶茶'))
                  {{ $order_product['drink']['option_values']['1026']['quantity']}}
                  @endif
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['drink']['option_values']['1027']))
                    @if(!empty($order_product['drink']['option_values']['1027']['name']==='濃湯'))
                  {{ $order_product['drink']['option_values']['1027']['quantity']}}
                  @endif
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['drink']['option_values']['1028']))
                    @if(!empty($order_product['drink']['option_values']['1028']['name']==='季節甜品'))
                  {{ $order_product['drink']['option_values']['1028']['quantity']}}
                  @endif
                  @endif
                    </td>
                    <td> @if(!empty($order_product['product_options']['配菜']['贈品豆花']))
                  {{ $order_product['product_options']['配菜']['贈品豆花'] ?? 0 }}
                  @endif </td>


                </tr>

                {{-- 如果有飲料 --}}
                <!-- @if(!empty($order_product['drink']))
                <tr>
                  <td colspan="11"><span style="font-size: 10px;">飲料：</span>

                      @foreach($order_product['drink']['option_values'] as $product_option_value_id => $value)
                        {{ $value['name'] }}*{{ $value['quantity'] }},
                      @endforeach

                  </td>
                </tr>
                @endif -->

                {{-- 如果有備註 --}}
                @if(!empty($order_product['comment']))
                <tr style="height:3px">
                  <td colspan="11"><span style="font-size: 10px;">商品備註：</span>{{ $order_product['comment'] }}</td>
                </tr>
                @endif
                @if($order['final_products'][$key]['product_id']!==1697)
                @php unset($order['final_products'][$key]) @endphp
                @endif
                @endif
                @endforeach
          </tbody>
        </table>
        <table data-toggle="table" class="table table-bordered border border-dark tr-border-special" style="margin-top:3px;margin-bottom:0px;">
          @foreach($order['final_products'] as $key => $order_product)
            @if($order_product['product_id']===1697)
            <tr>
              <td>備註: 客製油飯盒 * {{ $order_product['quantity'] }}份。
              @if(!empty($order_product['price']))
                {{ $order_product['price'] }}
              @else
                0
                @endif
                元
                /@
              <!-- @if(!empty($order_product['main_meal']))
              【{{ $order_product['main_meal']['name'] }}{{":"}}】
              @foreach($order_product['main_meal']['option_values'] as $option_name => $options)
              @if($options['name']!='極品油飯')
                  {{ $options['name'] }}
                  *{{ $options['quantity'] }}
              @endif
              @endforeach
              @endif -->
              @if(!empty($order_product['product_options']))
              @foreach($order_product['product_options'] as $option_name => $options)
              @if($option_name!=='飲料' && $option_name!=='主餐')
              【{{ $option_name }}{{":"}}】

                @foreach($options as $option_value_name => $quantity)
                @if($option_value_name !=='梅汁番茄' && $option_value_name !=='鹽水煮蛋' && $option_value_name !=='時蔬' && $option_value_name !=='筍絲' && $option_value_name !=='酸菜' && $option_value_name !=='酥炸菇菇' && $option_value_name !=='香滷豆干')
                  {{ $option_value_name }}*{{ $quantity }}
                @endif
                @endforeach
              @endif
              @endforeach
              @endif

                <!-- @if(!empty($order_product['drink']))
                【{{"飲料:"}}】
                      @foreach($order_product['drink']['option_values'] as $product_option_value_id => $value)
                        {{ $value['name'] }}*{{ $value['quantity'] }},
                      @endforeach

                @endif -->

              </td>
            </tr>
            @endif
            @endforeach
          </table>
        {{-- 便當 --}}
        <table class=" table-bordered border border-dark tr-border-top " style="margin-top:3px;margin-bottom:0px;">
          <tbody>
            @php
              $bento_title_showed = 0;
            @endphp
            @foreach($order['final_products'] as $key =>$order_product)
            @if($order_product['main_category_code'] == 'bento')
                @if($bento_title_showed == 0)
                  <tr>
                    <td style="width:115px;">潤餅便當系列</td>
                    <td style="width:24px;" class=" fw-bold">小計</td>
                    <td style="width:24px;" class=" fw-bold">主廚</td>
                    <td style="width:24px;" class=" fw-bold">全素</td>
                    <td style="width:24px;" class=" fw-bold">蛋素</td>
                    <td style="width:24px;" class=" fw-bold">薯泥</td>
                    <td style="width:24px;" class=" fw-bold">炸蝦</td>
                    <td style="width:24px;" class=" fw-bold">芥雞</td>
                    <td style="width:24px;" class=" fw-bold">酥魚</td>
                    <td style="width:24px;" class=" fw-bold">培根</td>
                    <td style="width:24px;" class=" fw-bold">滷肉</td>
                    <td style="width:24px;border-right:3px solid black" class=" fw-bold">春捲</td>
                    <!-- <td style="width:24px;" class=" fw-bold">雞胸</td>
                    <td style="width:24px;" class=" fw-bold">雞腿</td>
                    <td style="width:24px;" class=" fw-bold">滷牛</td>
                    <td style="width:24px;" class=" fw-bold">鮭魚</td>
                    <td style="width:24px;" class=" fw-bold">牛丸</td>
                    <td style="width:24px;" class=" fw-bold">素排</td> -->
                    <td style="width:24px;" class=" fw-bold">烤瓜</td>
                    <td style="width:24px;" class=" fw-bold">薯球</td>
                    <td style="width:24px;" class=" fw-bold">蛋塔</td>
                    <td style="width:24px;"></td>
                    <td style="width:24px;"></td>
                    <td style="width:24px;"></td>
                    <td style="width:24px;"></td>
                    <td style="width:24px;"></td>
                    <td style="width:24px;"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;border-right:3px solid black" class=" fw-bold"></td>
                    <!-- <td style="width:24px;" class=" fw-bold">小計</td> -->
                    <td style="width:24px;" class=" fw-bold">微豆</td>
                    <td style="width:24px;" class=" fw-bold">無豆</td>
                    <td style="width:24px;" class=" fw-bold">紅茶</td>
                    <td style="width:24px;" class=" fw-bold">奶茶</td>
                    <td style="width:24px;" class=" fw-bold">濃湯</td>
                    <td style="width:24px;" class=" fw-bold">甜湯</td>
                    <!-- <td class=" fw-bold">呱呱卷</td> -->
                  </tr>
                  @php $bento_title_showed = 1; @endphp
                @endif
                <tr>
                  <td>{{ $order_product['name'] }}</td>
                  <td>{{ $order_product['quantity'] }}</td>
                  <td>
                  @if(!empty($order_product['main_meal']['option_values'][1083]))
                  {{ $order_product['main_meal']['option_values'][1083]['quantity'] ?? 0 }}
                  @elseif(!empty($order_product['main_meal']['option_values'][1102]))
                  {{ $order_product['main_meal']['option_values'][1102]['quantity'] ?? 0 }}
                  @endif
                  </td>
                  <td>
                  @if(!empty($order_product['main_meal']['option_values'][1046]))
                  {{ $order_product['main_meal']['option_values'][1046]['quantity'] ?? 0 }}
                  @elseif(!empty($order_product['main_meal']['option_values'][1104]))
                  {{ $order_product['main_meal']['option_values'][1104]['quantity'] ?? 0 }}
                  @endif
                  </td>
                  <td>
                  @if(!empty($order_product['main_meal']['option_values'][1047]))
                  {{ $order_product['main_meal']['option_values'][1047]['quantity'] ?? 0 }}
                  @elseif(!empty($order_product['main_meal']['option_values'][1105]))
                  {{ $order_product['main_meal']['option_values'][1105]['quantity'] ?? 0 }}
                  @endif
                  </td>
                  <td>
                  @if(!empty($order_product['main_meal']['option_values'][1017]))
                  {{ $order_product['main_meal']['option_values'][1017]['quantity'] ?? 0 }}
                  @elseif(!empty($order_product['main_meal']['option_values'][1096]))
                  {{ $order_product['main_meal']['option_values'][1096]['quantity'] ?? 0 }}
                  @endif
                  </td>
                  <td>
                  @if(!empty($order_product['main_meal']['option_values'][1018]))
                  {{ $order_product['main_meal']['option_values'][1018]['quantity'] ?? 0 }}
                  @elseif(!empty($order_product['main_meal']['option_values'][1097]))
                  {{ $order_product['main_meal']['option_values'][1097]['quantity'] ?? 0 }}
                  @endif
                  </td>
                  <td>
                  @if(!empty($order_product['main_meal']['option_values'][1019]))
                  {{ $order_product['main_meal']['option_values'][1019]['quantity'] ?? 0 }}
                  @elseif(!empty($order_product['main_meal']['option_values'][1098]))
                  {{ $order_product['main_meal']['option_values'][1098]['quantity'] ?? 0 }}
                  @endif
                  </td>
                  <td>
                  @if(!empty($order_product['main_meal']['option_values'][1020]))
                  {{ $order_product['main_meal']['option_values'][1020]['quantity'] ?? 0 }}
                  @elseif(!empty($order_product['main_meal']['option_values'][1099]))
                  {{ $order_product['main_meal']['option_values'][1099]['quantity'] ?? 0 }}
                  @endif
                  </td>
                  <td>
                  @if(!empty($order_product['main_meal']['option_values'][1021]))
                  {{ $order_product['main_meal']['option_values'][1021]['quantity'] ?? 0 }}
                  @elseif(!empty($order_product['main_meal']['option_values'][1100]))
                  {{ $order_product['main_meal']['option_values'][1100]['quantity'] ?? 0 }}
                  @endif
                  </td>
                  <td>
                  @if(!empty($order_product['main_meal']['option_values'][1022]))
                  {{ $order_product['main_meal']['option_values'][1022]['quantity'] ?? 0 }}
                  @elseif(!empty($order_product['main_meal']['option_values'][1101]))
                  {{ $order_product['main_meal']['option_values'][1101]['quantity'] ?? 0 }}
                  @endif
                  </td>
                  <td style="border-right:3px solid black">
                  @if(!empty($order_product['main_meal']['option_values'][1093]))
                  {{ $order_product['main_meal']['option_values'][1093]['quantity'] ?? 0 }}
                  @endif
                  </td>
                  <!-- <td>
                  @if(!empty($order_product['main_meal']['option_values'][1072]))
                  {{ $order_product['main_meal']['option_values'][1072]['quantity'] ?? 0 }}
                  @endif
                  </td> -->
                  <!-- <td style="width:30px;" class=" fw-bold">雞胸</td>
                    <td style="width:30px;" class=" fw-bold">雞腿</td>
                    <td style="width:30px;" class=" fw-bold">滷牛</td>
                    <td style="width:30px;" class=" fw-bold">鮭魚</td>
                    <td style="width:30px;" class=" fw-bold">牛丸</td>
                    <td style="width:30px;" class=" fw-bold">素排</td>
                    <td style="width:30px;" class=" fw-bold">烤瓜</td>
                    <td style="width:30px;" class=" fw-bold">薯球</td>
                    <td style="width:30px;" class=" fw-bold">蛋塔</td>
                    <td style="width:30px;" class=" fw-bold"></td>
                    <td style="width:30px;" class=" fw-bold">小計</td>
                    <td style="width:30px;" class=" fw-bold">微豆</td>
                    <td style="width:30px;" class=" fw-bold">無豆</td>
                    <td style="width:30px;" class=" fw-bold">紅茶</td>
                    <td style="width:30px;" class=" fw-bold">奶茶</td>
                    <td style="width:30px;" class=" fw-bold">濃湯</td>
                    <td style="width:30px;" class=" fw-bold">甜湯</td>
                    <td> -->
                    <!-- <td>
                    @if(!empty($order_product['product_options']['副主餐']['清蒸嫰雞胸']))
                  {{ $order_product['product_options']['副主餐']['清蒸嫰雞胸'] ?? 0 }}
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['product_options']['副主餐']['滷雞腿']))
                  {{ $order_product['product_options']['副主餐']['滷雞腿'] ?? 0 }}
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['product_options']['副主餐']['滷牛腱']))
                  {{ $order_product['product_options']['副主餐']['滷牛腱'] ?? 0 }}
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['product_options']['副主餐']['鮭魚']))
                  {{ $order_product['product_options']['副主餐']['鮭魚'] ?? 0 }}
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['product_options']['副主餐']['紅酒牛肉丸']))
                  {{ $order_product['product_options']['副主餐']['紅酒牛肉丸'] ?? 0 }}
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['product_options']['副主餐']['素肉排']))
                  {{ $order_product['product_options']['副主餐']['素肉排'] ?? 0 }}
                  @endif
                    </td> -->
                    <td>
                    @if(!empty($order_product['product_options']['配菜']['烤地瓜']))
                  {{ $order_product['product_options']['配菜']['烤地瓜'] ?? 0 }}
                  @endif
                    </td>
                     <td>
                    @if(!empty($order_product['product_options']['配菜']['薯球']))
                  {{ $order_product['product_options']['配菜']['薯球'] ?? 0 }}
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['product_options']['配菜']['手作蛋塔']))
                  {{ $order_product['product_options']['配菜']['手作蛋塔'] ?? 0 }}
                  @endif
                    </td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td style="border-right:3px solid black"></td>
                    <td>
                    @if(!empty($order_product['drink']['option_values']['1023']))
                    @if(!empty($order_product['drink']['option_values']['1023']['name']==='微豆'))
                  {{ $order_product['drink']['option_values']['1023']['quantity']}}
                  @endif
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['drink']['option_values']['1024']))
                    @if(!empty($order_product['drink']['option_values']['1024']['name']==='無豆'))
                  {{ $order_product['drink']['option_values']['1024']['quantity']}}
                  @endif
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['drink']['option_values']['1025']))
                    @if(!empty($order_product['drink']['option_values']['1025']['name']==='紅茶'))
                  {{ $order_product['drink']['option_values']['1025']['quantity']}}
                  @endif
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['drink']['option_values']['1026']))
                    @if(!empty($order_product['drink']['option_values']['1026']['name']==='奶茶'))
                  {{ $order_product['drink']['option_values']['1026']['quantity']}}
                  @endif
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['drink']['option_values']['1027']))
                    @if(!empty($order_product['drink']['option_values']['1027']['name']==='濃湯'))
                  {{ $order_product['drink']['option_values']['1027']['quantity']}}
                  @endif
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['drink']['option_values']['1028']))
                    @if(!empty($order_product['drink']['option_values']['1028']['name']==='季節甜品'))
                  {{ $order_product['drink']['option_values']['1028']['quantity']}}
                  @endif
                  @endif
                    </td>


                </tr>

                {{-- 如果有飲料 --}}
                <!-- @if(!empty($order_product['drink']))
                <tr>
                  <td colspan="11"><span style="font-size: 10px;">飲料：</span>

                      @foreach($order_product['drink']['option_values'] as $product_option_value_id => $value)
                        {{ $value['name'] }}*{{ $value['quantity'] }},
                      @endforeach

                  </td>
                </tr>
                @endif -->

                {{-- 如果有備註 --}}
                @if(!empty($order_product['comment']))
                <tr style="height:3px">
                  <td colspan="11"><span style="font-size: 10px;">商品備註：</span>{{ $order_product['comment'] }}</td>
                </tr>
                @endif
                @if($order['final_products'][$key]['product_id']!==1043)
                @php unset($order['final_products'][$key]) @endphp
                @endif
                @endif
                @endforeach
              </tbody>
            </table>
            <table data-toggle="table" class="table table-bordered border border-dark tr-border-special" style="margin-top:3px;margin-bottom:0px;">
            @foreach($order['final_products'] as $key => $order_product)
            @if($order_product['product_id']===1043)
            <tr>
              <td>備註: 客製便當 * {{ $order_product['quantity'] }}份。
              @if(!empty($order_product['price']))
                {{ $order_product['price'] }}
              @else
                0
                @endif
                元  /@
              @if(!empty($order_product['product_options']))
                @foreach($order_product['product_options'] as $option_name => $options)
                  @if($option_name!=='飲料' && $option_name!=='主餐')
                  【{{ $option_name }}{{":"}}】

                    @foreach($options as $option_value_name => $quantity)
                      @if($option_value_name !=='梅汁番茄' && $option_value_name !=='鹽水煮蛋' && $option_value_name !=='時蔬' && $option_value_name !=='香菇油飯' && $option_value_name !=='梅汁番茄' && $option_value_name !=='酥炸菇菇' && $option_value_name !=='香滷豆干')
                        {{ $option_value_name }}*{{ $quantity }}
                      @endif
                    @endforeach
                  @endif
                @endforeach
              @endif
                <!-- @if(!empty($order_product['drink']))
                【{{"飲料:"}}】
                      @foreach($order_product['drink']['option_values'] as $product_option_value_id => $value)
                        {{ $value['name'] }}*{{ $value['quantity'] }},
                      @endforeach

                @endif -->

              </td>
            </tr>
            @endif
            @endforeach
          </table>
        {{-- 標準盒餐 --}}
        <table data-toggle="table" class=" table-bordered border border-dark rounded-3 tr-border-top" style="margin-top:3px;margin-bottom:0px;">
          <tbody>
            @php $lunchbox_title_showed = 0;@endphp
            @foreach($order['final_products'] as $key => $order_product)
              {{-- 標準盒餐 --}}
              @if($order_product['main_category_code'] == 'lunchbox')
                @if($lunchbox_title_showed == 0)
                  <tr>
                    <td style="width:115px;">盒餐系列</td>
                    <td style="width:24px;" class=" fw-bold">小計</td>
                    <td style="width:24px;" class=" fw-bold">主廚</td>
                    <td style="width:24px;" class=" fw-bold">全素</td>
                    <td style="width:24px;" class=" fw-bold">蛋素</td>
                    <td style="width:24px;" class=" fw-bold">薯泥</td>
                    <td style="width:24px;" class=" fw-bold">炸蝦</td>
                    <td style="width:24px;" class=" fw-bold">芥雞</td>
                    <td style="width:24px;" class=" fw-bold">酥魚</td>
                    <td style="width:24px;" class=" fw-bold">培根</td>
                    <td style="width:24px;" class=" fw-bold">滷肉</td>
                    <td style="width:24px;border-right:3px solid black" class=" fw-bold">春捲</td>
                    <td style="width:24px;" class=" fw-bold">地瓜</td>
                    <td style="width:24px;" class=" fw-bold">蛋塔</td>
                    <!-- <td style="width:24px;" class=" fw-bold">酥餅</td> -->
                    <td style="width:24px;" class=" fw-bold">雞翅</td>
                    <!-- <td style="width:24px;" class=" fw-bold">芋糕</td> -->
                    <!-- <td style="width:24px;" class=" fw-bold">番茄</td> -->
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;border-right:3px solid black" class=" fw-bold"></td>
                    <td style="width:24px;" class= " fw-bold">微豆</td>
                    <td style="width:24px;" class=" fw-bold">無豆</td>
                    <td style="width:24px;" class=" fw-bold">紅茶</td>
                    <td style="width:24px;" class=" fw-bold">奶茶</td>
                    <td style="width:24px;" class=" fw-bold">濃湯</td>
                    <td style="width:24px;" class=" fw-bold">甜湯</td>
                    <!-- <td class=" fw-bold">呱呱卷</td> -->
                  </tr>
                  @php $lunchbox_title_showed = 1; @endphp
                @endif
                <tr>
                  <td>{{ $order_product['name'] }}</td>
                  <td>{{ $order_product['quantity'] }}</td>
                  <td>
                  @if(!empty($order_product['main_meal']['option_values'][1083]))
                  {{ $order_product['main_meal']['option_values'][1083]['quantity'] ?? 0 }}
                  @elseif(!empty($order_product['main_meal']['option_values'][1102]))
                  {{ $order_product['main_meal']['option_values'][1102]['quantity'] ?? 0 }}
                  @endif
                  </td>
                  <td>
                  @if(!empty($order_product['main_meal']['option_values'][1046]))
                  {{ $order_product['main_meal']['option_values'][1046]['quantity'] ?? 0 }}
                  @elseif(!empty($order_product['main_meal']['option_values'][1104]))
                  {{ $order_product['main_meal']['option_values'][1104]['quantity'] ?? 0 }}
                  @endif
                  </td>
                  <td>
                  @if(!empty($order_product['main_meal']['option_values'][1047]))
                  {{ $order_product['main_meal']['option_values'][1047]['quantity'] ?? 0 }}
                  @elseif(!empty($order_product['main_meal']['option_values'][1105]))
                  {{ $order_product['main_meal']['option_values'][1105]['quantity'] ?? 0 }}
                  @endif
                  </td>
                  <td>
                  @if(!empty($order_product['main_meal']['option_values'][1017]))
                  {{ $order_product['main_meal']['option_values'][1017]['quantity'] ?? 0 }}
                  @elseif(!empty($order_product['main_meal']['option_values'][1096]))
                  {{ $order_product['main_meal']['option_values'][1096]['quantity'] ?? 0 }}
                  @endif
                  </td>
                  <td>
                  @if(!empty($order_product['main_meal']['option_values'][1018]))
                  {{ $order_product['main_meal']['option_values'][1018]['quantity'] ?? 0 }}
                  @elseif(!empty($order_product['main_meal']['option_values'][1097]))
                  {{ $order_product['main_meal']['option_values'][1097]['quantity'] ?? 0 }}
                  @endif
                  </td>
                  <td>
                  @if(!empty($order_product['main_meal']['option_values'][1019]))
                  {{ $order_product['main_meal']['option_values'][1019]['quantity'] ?? 0 }}
                  @elseif(!empty($order_product['main_meal']['option_values'][1098]))
                  {{ $order_product['main_meal']['option_values'][1098]['quantity'] ?? 0 }}
                  @endif
                  </td>
                  <td>
                  @if(!empty($order_product['main_meal']['option_values'][1020]))
                  {{ $order_product['main_meal']['option_values'][1020]['quantity'] ?? 0 }}
                  @elseif(!empty($order_product['main_meal']['option_values'][1099]))
                  {{ $order_product['main_meal']['option_values'][1099]['quantity'] ?? 0 }}
                  @endif
                  </td>
                  <td>
                  @if(!empty($order_product['main_meal']['option_values'][1021]))
                  {{ $order_product['main_meal']['option_values'][1021]['quantity'] ?? 0 }}
                  @elseif(!empty($order_product['main_meal']['option_values'][1100]))
                  {{ $order_product['main_meal']['option_values'][1100]['quantity'] ?? 0 }}
                  @endif
                  </td>
                  <td>
                  @if(!empty($order_product['main_meal']['option_values'][1022]))
                  {{ $order_product['main_meal']['option_values'][1022]['quantity'] ?? 0 }}
                  @elseif(!empty($order_product['main_meal']['option_values'][1101]))
                  {{ $order_product['main_meal']['option_values'][1101]['quantity'] ?? 0 }}
                  @endif
                  </td>
                  <td style="border-right:3px solid black">
                  @if(!empty($order_product['main_meal']['option_values'][1093]))
                  {{ $order_product['main_meal']['option_values'][1093]['quantity'] ?? 0 }}
                  @endif
                  </td>
                  <!-- <td>
                  @if(!empty($order_product['main_meal']['option_values'][1072]))
                  {{ $order_product['main_meal']['option_values'][1072]['quantity'] ?? 0 }}
                  @endif
                  </td> -->
                  <!-- <td style="width:27px;" class=" fw-bold">地瓜</td>
                    <td style="width:27px;" class=" fw-bold">蛋塔</td>
                    <td style="width:27px;" class=" fw-bold">酥餅</td>
                    <td style="width:27px;" class=" fw-bold">雞翅</td>
                    <td style="width:27px;" class=" fw-bold">芋糕</td>
                    <td style="width:27px;" class=" fw-bold">番茄</td> -->
                  <td>
                    @if(!empty($order_product['product_options']['配菜']['烤地瓜']))
                  {{ $order_product['product_options']['配菜']['烤地瓜'] ?? 0}}
                  @elseif(!empty($order_product['product_options']['配菜']['炸地瓜']))
                  {{ $order_product['product_options']['配菜']['炸地瓜']?? 0}}
                  @else
                  {{''}}
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['product_options']['配菜']['手作蛋塔']))
                  {{ $order_product['product_options']['配菜']['手作蛋塔'] ?? 0 }}
                  @endif
                    </td>
                    <!-- <td>
                    @if(!empty($order_product['product_options']['配菜']['香蔥酥餅']))
                  {{ $order_product['product_options']['配菜']['香蔥酥餅'] ?? 0 }}
                  @endif
                    </td>  -->
                    <td>
                      @if(!empty($order_product['product_options']['配菜']['紐澳良雞翅']))
                      {{ $order_product['product_options']['配菜']['紐澳良雞翅'] ?? 0 }}
                      @endif
                    </td>
                    <!-- <td>
                    @if(!empty($order_product['product_options']['配菜']['紐澳良雞翅']))
                      {{ $order_product['product_options']['配菜']['紐澳良雞翅'] ?? 0 }}
                      @endif
                    </td> -->
                    <!-- <td>
                    @if(!empty($order_product['product_options']['配菜']['芋頭糕']))
                  {{ $order_product['product_options']['配菜']['芋頭糕'] ?? 0 }}
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['product_options']['配菜']['梅汁番茄']))
                  {{ $order_product['product_options']['配菜']['梅汁番茄'] ?? 0 }}
                  @endif
                    </td>   -->
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td style="border-right:3px solid black"></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    {{-- 微豆 --}}
                    <!-- @if(!empty($order_product['main_meal']['option_values'][1046]['drink'])||(!empty($order_product['main_meal']['option_values'][1047]['drink']))
                    ||(!empty($order_product['main_meal']['option_values'][1017]['drink']))||(!empty($order_product['main_meal']['option_values'][1018]['drink']))
                    ||(!empty($order_product['main_meal']['option_values'][1019]['drink']))||(!empty($order_product['main_meal']['option_values'][1020]['drink']))
                    ||(!empty($order_product['main_meal']['option_values'][1021]['drink']))||(!empty($order_product['main_meal']['option_values'][1022]['drink']))
                    ||(!empty($order_product['main_meal']['option_values'][1072]['drink']))

                    || (!empty($order_product['main_meal']['option_values'][1096]['drink'])) || (!empty($order_product['main_meal']['option_values'][1097]['drink']))
                    || (!empty($order_product['main_meal']['option_values'][1098]['drink'])) || (!empty($order_product['main_meal']['option_values'][1099]['drink']))
                    || (!empty($order_product['main_meal']['option_values'][1100]['drink'])) || (!empty($order_product['main_meal']['option_values'][1101]['drink']))
                    || (!empty($order_product['main_meal']['option_values'][1104]['drink'])) || (!empty($order_product['main_meal']['option_values'][1105]['drink'])))
                    <td>

                    @if(!empty($order_product['main_meal']['option_values'][1046]['drink'][1023]))
                  {{$order_product['main_meal']['option_values'][1046]['drink'][1023]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1047]['drink'][1023]))
                  {{$order_product['main_meal']['option_values'][1047]['drink'][1023]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1017]['drink'][1023]))
                  {{$order_product['main_meal']['option_values'][1017]['drink'][1023]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1018]['drink'][1023]))
                  {{$order_product['main_meal']['option_values'][1018]['drink'][1023]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1019]['drink'][1023]))
                  {{$order_product['main_meal']['option_values'][1019]['drink'][1023]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1020]['drink'][1023]))
                  {{$order_product['main_meal']['option_values'][1020]['drink'][1023]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1021]['drink'][1023]))
                  {{$order_product['main_meal']['option_values'][1021]['drink'][1023]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1022]['drink'][1023]))
                  {{$order_product['main_meal']['option_values'][1022]['drink'][1023]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1072]['drink'][1023]))
                  {{$order_product['main_meal']['option_values'][1072]['drink'][1023]['quantity']}}

                  @elseif(!empty($order_product['main_meal']['option_values'][1096]['drink'][1023]))
                  {{$order_product['main_meal']['option_values'][1096]['drink'][1023]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1097]['drink'][1023]))
                  {{$order_product['main_meal']['option_values'][1097]['drink'][1023]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1098]['drink'][1023]))
                  {{$order_product['main_meal']['option_values'][1098]['drink'][1023]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1099]['drink'][1023]))
                  {{$order_product['main_meal']['option_values'][1099]['drink'][1023]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1100]['drink'][1023]))
                  {{$order_product['main_meal']['option_values'][1100]['drink'][1023]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1101]['drink'][1023]))
                  {{$order_product['main_meal']['option_values'][1101]['drink'][1023]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1104]['drink'][1023]))
                  {{$order_product['main_meal']['option_values'][1104]['drink'][1023]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1105]['drink'][1023]))
                  {{$order_product['main_meal']['option_values'][1105]['drink'][1023]['quantity']}}
                  @endif
                    </td>
                    @endif -->
                    {{-- 無豆 --}}
                    <!-- @if(!empty($order_product['main_meal']['option_values'][1046]['drink'])||(!empty($order_product['main_meal']['option_values'][1047]['drink']))
                    ||(!empty($order_product['main_meal']['option_values'][1017]['drink']))||(!empty($order_product['main_meal']['option_values'][1018]['drink']))
                    ||(!empty($order_product['main_meal']['option_values'][1019]['drink']))||(!empty($order_product['main_meal']['option_values'][1020]['drink']))
                    ||(!empty($order_product['main_meal']['option_values'][1021]['drink']))||(!empty($order_product['main_meal']['option_values'][1022]['drink']))
                    ||(!empty($order_product['main_meal']['option_values'][1072]['drink']))
                    || (!empty($order_product['main_meal']['option_values'][1096]['drink'])) || (!empty($order_product['main_meal']['option_values'][1097]['drink']))
                    || (!empty($order_product['main_meal']['option_values'][1098]['drink'])) || (!empty($order_product['main_meal']['option_values'][1099]['drink']))
                    || (!empty($order_product['main_meal']['option_values'][1100]['drink'])) || (!empty($order_product['main_meal']['option_values'][1101]['drink']))
                    || (!empty($order_product['main_meal']['option_values'][1104]['drink'])) || (!empty($order_product['main_meal']['option_values'][1105]['drink'])))
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1046]['drink'][1024]))
                  {{$order_product['main_meal']['option_values'][1046]['drink'][1024]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1047]['drink'][1024]))
                  {{$order_product['main_meal']['option_values'][1047]['drink'][1024]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1017]['drink'][1024]))
                  {{$order_product['main_meal']['option_values'][1017]['drink'][1024]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1018]['drink'][1024]))
                  {{$order_product['main_meal']['option_values'][1018]['drink'][1024]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1019]['drink'][1024]))
                  {{$order_product['main_meal']['option_values'][1019]['drink'][1024]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1020]['drink'][1024]))
                  {{$order_product['main_meal']['option_values'][1020]['drink'][1024]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1021]['drink'][1024]))
                  {{$order_product['main_meal']['option_values'][1021]['drink'][1024]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1022]['drink'][1024]))
                  {{$order_product['main_meal']['option_values'][1022]['drink'][1024]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1072]['drink'][1024]))
                  {{$order_product['main_meal']['option_values'][1072]['drink'][1024]['quantity']}}

                  @elseif(!empty($order_product['main_meal']['option_values'][1096]['drink'][1024]))
                  {{$order_product['main_meal']['option_values'][1096]['drink'][1024]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1097]['drink'][1024]))
                  {{$order_product['main_meal']['option_values'][1097]['drink'][1024]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1098]['drink'][1024]))
                  {{$order_product['main_meal']['option_values'][1098]['drink'][1024]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1099]['drink'][1024]))
                  {{$order_product['main_meal']['option_values'][1099]['drink'][1024]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1100]['drink'][1024]))
                  {{$order_product['main_meal']['option_values'][1100]['drink'][1024]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1101]['drink'][1024]))
                  {{$order_product['main_meal']['option_values'][1101]['drink'][1024]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1104]['drink'][1024]))
                  {{$order_product['main_meal']['option_values'][1104]['drink'][1024]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1105]['drink'][1024]))
                  {{$order_product['main_meal']['option_values'][1105]['drink'][1024]['quantity']}}
                  @endif
                    </td>
                    @endif -->
                    {{-- 紅茶 --}}
                    <!-- @if(!empty($order_product['main_meal']['option_values'][1046]['drink'])||(!empty($order_product['main_meal']['option_values'][1047]['drink']))
                    ||(!empty($order_product['main_meal']['option_values'][1017]['drink']))||(!empty($order_product['main_meal']['option_values'][1018]['drink']))
                    ||(!empty($order_product['main_meal']['option_values'][1019]['drink']))||(!empty($order_product['main_meal']['option_values'][1020]['drink']))
                    ||(!empty($order_product['main_meal']['option_values'][1021]['drink']))||(!empty($order_product['main_meal']['option_values'][1022]['drink']))
                    ||(!empty($order_product['main_meal']['option_values'][1072]['drink']))
                    || (!empty($order_product['main_meal']['option_values'][1096]['drink'])) || (!empty($order_product['main_meal']['option_values'][1097]['drink']))
                    || (!empty($order_product['main_meal']['option_values'][1098]['drink'])) || (!empty($order_product['main_meal']['option_values'][1099]['drink']))
                    || (!empty($order_product['main_meal']['option_values'][1100]['drink'])) || (!empty($order_product['main_meal']['option_values'][1101]['drink']))
                    || (!empty($order_product['main_meal']['option_values'][1104]['drink'])) || (!empty($order_product['main_meal']['option_values'][1105]['drink'])))
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1046]['drink'][1025]))
                  {{$order_product['main_meal']['option_values'][1046]['drink'][1025]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1047]['drink'][1025]))
                  {{$order_product['main_meal']['option_values'][1047]['drink'][1025]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1017]['drink'][1025]))
                  {{$order_product['main_meal']['option_values'][1017]['drink'][1025]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1018]['drink'][1025]))
                  {{$order_product['main_meal']['option_values'][1018]['drink'][1025]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1019]['drink'][1025]))
                  {{$order_product['main_meal']['option_values'][1019]['drink'][1025]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1020]['drink'][1025]))
                  {{$order_product['main_meal']['option_values'][1020]['drink'][1025]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1021]['drink'][1025]))
                  {{$order_product['main_meal']['option_values'][1021]['drink'][1025]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1022]['drink'][1025]))
                  {{$order_product['main_meal']['option_values'][1022]['drink'][1025]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1072]['drink'][1025]))
                  {{$order_product['main_meal']['option_values'][1072]['drink'][1025]['quantity']}}

                  @elseif(!empty($order_product['main_meal']['option_values'][1096]['drink'][1025]))
                  {{$order_product['main_meal']['option_values'][1096]['drink'][1025]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1097]['drink'][1025]))
                  {{$order_product['main_meal']['option_values'][1097]['drink'][1025]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1098]['drink'][1025]))
                  {{$order_product['main_meal']['option_values'][1098]['drink'][1025]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1099]['drink'][1025]))
                  {{$order_product['main_meal']['option_values'][1099]['drink'][1025]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1100]['drink'][1025]))
                  {{$order_product['main_meal']['option_values'][1100]['drink'][1025]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1101]['drink'][1025]))
                  {{$order_product['main_meal']['option_values'][1101]['drink'][1025]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1104]['drink'][1025]))
                  {{$order_product['main_meal']['option_values'][1104]['drink'][1025]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1105]['drink'][1025]))
                  {{$order_product['main_meal']['option_values'][1105]['drink'][1025]['quantity']}}
                  @endif
                    </td>
                    @endif -->
                    {{-- 奶茶 --}}
                    <!-- @if(!empty($order_product['main_meal']['option_values'][1046]['drink'])||(!empty($order_product['main_meal']['option_values'][1047]['drink']))
                    ||(!empty($order_product['main_meal']['option_values'][1017]['drink']))||(!empty($order_product['main_meal']['option_values'][1018]['drink']))
                    ||(!empty($order_product['main_meal']['option_values'][1019]['drink']))||(!empty($order_product['main_meal']['option_values'][1020]['drink']))
                    ||(!empty($order_product['main_meal']['option_values'][1021]['drink']))||(!empty($order_product['main_meal']['option_values'][1022]['drink']))
                    ||(!empty($order_product['main_meal']['option_values'][1072]['drink']))
                    || (!empty($order_product['main_meal']['option_values'][1096]['drink'])) || (!empty($order_product['main_meal']['option_values'][1097]['drink']))
                    || (!empty($order_product['main_meal']['option_values'][1098]['drink'])) || (!empty($order_product['main_meal']['option_values'][1099]['drink']))
                    || (!empty($order_product['main_meal']['option_values'][1100]['drink'])) || (!empty($order_product['main_meal']['option_values'][1101]['drink']))
                    || (!empty($order_product['main_meal']['option_values'][1104]['drink'])) || (!empty($order_product['main_meal']['option_values'][1105]['drink'])))
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1046]['drink'][1026]))
                  {{$order_product['main_meal']['option_values'][1046]['drink'][1026]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1047]['drink'][1026]))
                  {{$order_product['main_meal']['option_values'][1047]['drink'][1026]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1017]['drink'][1026]))
                  {{$order_product['main_meal']['option_values'][1017]['drink'][1026]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1018]['drink'][1026]))
                  {{$order_product['main_meal']['option_values'][1018]['drink'][1026]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1019]['drink'][1026]))
                  {{$order_product['main_meal']['option_values'][1019]['drink'][1026]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1020]['drink'][1026]))
                  {{$order_product['main_meal']['option_values'][1020]['drink'][1026]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1021]['drink'][1026]))
                  {{$order_product['main_meal']['option_values'][1021]['drink'][1026]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1022]['drink'][1026]))
                  {{$order_product['main_meal']['option_values'][1022]['drink'][1026]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1072]['drink'][1026]))
                  {{$order_product['main_meal']['option_values'][1072]['drink'][1026]['quantity']}}

                  @elseif(!empty($order_product['main_meal']['option_values'][1096]['drink'][1026]))
                  {{$order_product['main_meal']['option_values'][1096]['drink'][1026]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1097]['drink'][1026]))
                  {{$order_product['main_meal']['option_values'][1097]['drink'][1026]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1098]['drink'][1026]))
                  {{$order_product['main_meal']['option_values'][1098]['drink'][1026]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1099]['drink'][1026]))
                  {{$order_product['main_meal']['option_values'][1099]['drink'][1026]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1100]['drink'][1026]))
                  {{$order_product['main_meal']['option_values'][1100]['drink'][1026]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1101]['drink'][1026]))
                  {{$order_product['main_meal']['option_values'][1101]['drink'][1026]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1104]['drink'][1026]))
                  {{$order_product['main_meal']['option_values'][1104]['drink'][1026]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1105]['drink'][1026]))
                  {{$order_product['main_meal']['option_values'][1105]['drink'][1026]['quantity']}}
                  @endif
                    </td>
                    @endif -->
                    {{-- 濃湯 --}}
                    <!-- @if(!empty($order_product['main_meal']['option_values'][1046]['drink'])||(!empty($order_product['main_meal']['option_values'][1047]['drink']))
                    ||(!empty($order_product['main_meal']['option_values'][1017]['drink']))||(!empty($order_product['main_meal']['option_values'][1018]['drink']))
                    ||(!empty($order_product['main_meal']['option_values'][1019]['drink']))||(!empty($order_product['main_meal']['option_values'][1020]['drink']))
                    ||(!empty($order_product['main_meal']['option_values'][1021]['drink']))||(!empty($order_product['main_meal']['option_values'][1022]['drink']))
                    ||(!empty($order_product['main_meal']['option_values'][1072]['drink']))
                    || (!empty($order_product['main_meal']['option_values'][1096]['drink'])) || (!empty($order_product['main_meal']['option_values'][1097]['drink']))
                    || (!empty($order_product['main_meal']['option_values'][1098]['drink'])) || (!empty($order_product['main_meal']['option_values'][1099]['drink']))
                    || (!empty($order_product['main_meal']['option_values'][1100]['drink'])) || (!empty($order_product['main_meal']['option_values'][1101]['drink']))
                    || (!empty($order_product['main_meal']['option_values'][1104]['drink'])) || (!empty($order_product['main_meal']['option_values'][1105]['drink'])))
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1046]['drink'][1027]))
                  {{$order_product['main_meal']['option_values'][1046]['drink'][1027]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1047]['drink'][1027]))
                  {{$order_product['main_meal']['option_values'][1047]['drink'][1027]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1017]['drink'][1027]))
                  {{$order_product['main_meal']['option_values'][1017]['drink'][1027]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1018]['drink'][1027]))
                  {{$order_product['main_meal']['option_values'][1018]['drink'][1027]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1019]['drink'][1027]))
                  {{$order_product['main_meal']['option_values'][1019]['drink'][1027]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1020]['drink'][1027]))
                  {{$order_product['main_meal']['option_values'][1020]['drink'][1027]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1021]['drink'][1027]))
                  {{$order_product['main_meal']['option_values'][1021]['drink'][1027]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1022]['drink'][1027]))
                  {{$order_product['main_meal']['option_values'][1022]['drink'][1027]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1072]['drink'][1027]))
                  {{$order_product['main_meal']['option_values'][1072]['drink'][1027]['quantity']}}

                  @elseif(!empty($order_product['main_meal']['option_values'][1096]['drink'][1027]))
                  {{$order_product['main_meal']['option_values'][1096]['drink'][1027]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1097]['drink'][1027]))
                  {{$order_product['main_meal']['option_values'][1097]['drink'][1027]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1098]['drink'][1027]))
                  {{$order_product['main_meal']['option_values'][1098]['drink'][1027]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1099]['drink'][1027]))
                  {{$order_product['main_meal']['option_values'][1099]['drink'][1027]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1100]['drink'][1027]))
                  {{$order_product['main_meal']['option_values'][1100]['drink'][1027]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1101]['drink'][1027]))
                  {{$order_product['main_meal']['option_values'][1101]['drink'][1027]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1104]['drink'][1027]))
                  {{$order_product['main_meal']['option_values'][1104]['drink'][1027]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1105]['drink'][1027]))
                  {{$order_product['main_meal']['option_values'][1105]['drink'][1027]['quantity']}}
                  @endif
                    </td>
                    @endif -->
                    {{-- 甜湯 --}}
                    <!-- @if(!empty($order_product['main_meal']['option_values'][1046]['drink'])||(!empty($order_product['main_meal']['option_values'][1047]['drink']))
                    ||(!empty($order_product['main_meal']['option_values'][1017]['drink']))||(!empty($order_product['main_meal']['option_values'][1018]['drink']))
                    ||(!empty($order_product['main_meal']['option_values'][1019]['drink']))||(!empty($order_product['main_meal']['option_values'][1020]['drink']))
                    ||(!empty($order_product['main_meal']['option_values'][1021]['drink']))||(!empty($order_product['main_meal']['option_values'][1022]['drink']))
                    ||(!empty($order_product['main_meal']['option_values'][1072]['drink']))
                    || (!empty($order_product['main_meal']['option_values'][1096]['drink'])) || (!empty($order_product['main_meal']['option_values'][1097]['drink']))
                    || (!empty($order_product['main_meal']['option_values'][1098]['drink'])) || (!empty($order_product['main_meal']['option_values'][1099]['drink']))
                    || (!empty($order_product['main_meal']['option_values'][1100]['drink'])) || (!empty($order_product['main_meal']['option_values'][1101]['drink']))
                    || (!empty($order_product['main_meal']['option_values'][1104]['drink'])) || (!empty($order_product['main_meal']['option_values'][1105]['drink'])))
                    <td>
                    @if(!empty($order_product['main_meal']['option_values'][1046]['drink'][1028]))
                  {{$order_product['main_meal']['option_values'][1046]['drink'][1028]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1047]['drink'][1028]))
                  {{$order_product['main_meal']['option_values'][1047]['drink'][1028]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1017]['drink'][1028]))
                  {{$order_product['main_meal']['option_values'][1017]['drink'][1028]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1018]['drink'][1028]))
                  {{$order_product['main_meal']['option_values'][1018]['drink'][1028]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1019]['drink'][1028]))
                  {{$order_product['main_meal']['option_values'][1019]['drink'][1028]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1020]['drink'][1028]))
                  {{$order_product['main_meal']['option_values'][1020]['drink'][1028]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1021]['drink'][1028]))
                  {{$order_product['main_meal']['option_values'][1021]['drink'][1028]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1022]['drink'][1028]))
                  {{$order_product['main_meal']['option_values'][1022]['drink'][1028]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1072]['drink'][1028]))
                  {{$order_product['main_meal']['option_values'][1072]['drink'][1028]['quantity']}}

                  @elseif(!empty($order_product['main_meal']['option_values'][1096]['drink'][1028]))
                  {{$order_product['main_meal']['option_values'][1096]['drink'][1028]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1097]['drink'][1028]))
                  {{$order_product['main_meal']['option_values'][1097]['drink'][1028]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1098]['drink'][1028]))
                  {{$order_product['main_meal']['option_values'][1098]['drink'][1028]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1099]['drink'][1028]))
                  {{$order_product['main_meal']['option_values'][1099]['drink'][1028]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1100]['drink'][1028]))
                  {{$order_product['main_meal']['option_values'][1100]['drink'][1028]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1101]['drink'][1028]))
                  {{$order_product['main_meal']['option_values'][1101]['drink'][1028]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1104]['drink'][1028]))
                  {{$order_product['main_meal']['option_values'][1104]['drink'][1028]['quantity']}}
                  @elseif(!empty($order_product['main_meal']['option_values'][1105]['drink'][1028]))
                  {{$order_product['main_meal']['option_values'][1105]['drink'][1028]['quantity']}}
                  @endif
                    </td>
                    @endif -->


                    <!-- <td>
                    @if(!empty($order_product['product_options']['飲料']['微豆']))
                  {{ $order_product['product_options']['飲料']['微豆']}}
                  @endif
                    </td> -->
                    <!-- <td>
                    @if(!empty($order_product['product_options']['飲料']['無豆']))
                  {{ $order_product['product_options']['飲料']['無豆']}}
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['product_options']['飲料']['紅茶']))
                  {{ $order_product['product_options']['飲料']['紅茶']}}
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['product_options']['飲料']['奶茶']))
                  {{ $order_product['product_options']['飲料']['奶茶']}}
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['product_options']['飲料']['濃湯']))
                  {{ $order_product['product_options']['飲料']['濃湯']}}
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['product_options']['飲料']['甜湯']))
                  {{ $order_product['product_options']['飲料']['甜湯']}}
                  @endif
                    </td> -->
                </tr>
                <tr>
                  <!-- <td></td> -->
                  <td>{{"盒餐飲料"}}</td>
                  <td></td>
                  <td>
                  @if(!empty($order_product['main_meal']['option_values'][1083]['drink']))
                    @foreach($order_product['main_meal']['option_values'][1083]['drink'] as $drink_option_value_id => $drink)
                    @if(!empty(mb_substr($drink['name'], 0, 1, 'UTF-8')==='季'))
                    <span style="font-size: 15px;">{{ mb_substr($drink['name'], 2, 1, 'UTF-8')}}</span>
                    @else
                    <span style="font-size: 15px;">{{ mb_substr($drink['name'], 0, 1, 'UTF-8')}}</span>
                    @endif
                    <span style="font-size: 13px;">{{ $drink['quantity'] }}</span><BR>
                    @endforeach
                    @endif
                  </td>
                  <td>
                  @if(!empty($order_product['main_meal']['option_values'][1046]['drink']))
                    @foreach($order_product['main_meal']['option_values'][1046]['drink'] as $drink_option_value_id => $drink)
                    @if(!empty(mb_substr($drink['name'], 0, 1, 'UTF-8')==='季'))
                    <span style="font-size: 15px;">{{ mb_substr($drink['name'], 2, 1, 'UTF-8')}}</span>
                    @else
                    <span style="font-size: 15px;">{{ mb_substr($drink['name'], 0, 1, 'UTF-8')}}</span>
                    @endif
                    <span style="font-size: 13px;">{{ $drink['quantity'] }}</span><BR>
                    @endforeach
                    @endif
                  </td>
                  <td>
                  @if(!empty($order_product['main_meal']['option_values'][1047]['drink']))
                    @foreach($order_product['main_meal']['option_values'][1047]['drink'] as $drink_option_value_id => $drink)
                    @if(!empty(mb_substr($drink['name'], 0, 1, 'UTF-8')==='季'))
                    <span style="font-size: 15px;">{{ mb_substr($drink['name'], 2, 1, 'UTF-8')}}</span>
                    @else
                    <span style="font-size: 15px;">{{ mb_substr($drink['name'], 0, 1, 'UTF-8')}}</span>
                    @endif
                    <span style="font-size: 13px;">{{ $drink['quantity'] }}</span><BR>
                    @endforeach
                    @endif
                  </td>
                  <td>
                  @if(!empty($order_product['main_meal']['option_values'][1017]['drink']))
                    @foreach($order_product['main_meal']['option_values'][1017]['drink'] as $drink_option_value_id => $drink)
                    @if(!empty(mb_substr($drink['name'], 0, 1, 'UTF-8')==='季'))
                    <span style="font-size: 15px;">{{ mb_substr($drink['name'], 2, 1, 'UTF-8')}}</span>
                    @else
                    <span style="font-size: 15px;">{{ mb_substr($drink['name'], 0, 1, 'UTF-8')}}</span>
                    @endif
                    <span style="font-size: 13px;">{{ $drink['quantity'] }}</span><BR>
                    @endforeach
                    @endif
                  </td>
                  <td>
                    @if(!empty($order_product['main_meal']['option_values'][1018]['drink']))
                    @foreach($order_product['main_meal']['option_values'][1018]['drink'] as $drink_option_value_id => $drink)
                    @if(!empty(mb_substr($drink['name'], 0, 1, 'UTF-8')==='季'))
                    <span style="font-size: 15px;">{{ mb_substr($drink['name'], 2, 1, 'UTF-8')}}</span>
                    @else
                    <span style="font-size: 15px;">{{ mb_substr($drink['name'], 0, 1, 'UTF-8')}}</span>
                    @endif
                    <span style="font-size: 13px;">{{ $drink['quantity'] }}</span><BR>
                    @endforeach
                    @endif
                  </td>
                  <td>
                  @if(!empty($order_product['main_meal']['option_values'][1019]['drink']))
                    @foreach($order_product['main_meal']['option_values'][1019]['drink'] as $drink_option_value_id => $drink)
                    @if(!empty(mb_substr($drink['name'], 0, 1, 'UTF-8')==='季'))
                    <span style="font-size: 15px;">{{ mb_substr($drink['name'], 2, 1, 'UTF-8')}}</span>
                    @else
                    <span style="font-size: 15px;">{{ mb_substr($drink['name'], 0, 1, 'UTF-8')}}</span>
                    @endif
                    <span style="font-size: 13px;">{{ $drink['quantity'] }}</span><BR>
                    @endforeach
                    @endif
                  </td>
                  <td>
                  @if(!empty($order_product['main_meal']['option_values'][1020]['drink']))
                    @foreach($order_product['main_meal']['option_values'][1020]['drink'] as $drink_option_value_id => $drink)
                    @if(!empty(mb_substr($drink['name'], 0, 1, 'UTF-8')==='季'))
                    <span style="font-size: 15px;">{{ mb_substr($drink['name'], 2, 1, 'UTF-8')}}</span>
                    @else
                    <span style="font-size: 15px;">{{ mb_substr($drink['name'], 0, 1, 'UTF-8')}}</span>
                    @endif
                    <span style="font-size: 13px;">{{ $drink['quantity'] }}</span><BR>
                    @endforeach
                    @endif
                  </td>
                  <td>
                  @if(!empty($order_product['main_meal']['option_values'][1021]['drink']))
                    @foreach($order_product['main_meal']['option_values'][1021]['drink'] as $drink_option_value_id => $drink)
                    @if(!empty(mb_substr($drink['name'], 0, 1, 'UTF-8')==='季'))
                    <span style="font-size: 15px;">{{ mb_substr($drink['name'], 2, 1, 'UTF-8')}}</span>
                    @else
                    <span style="font-size: 15px;">{{ mb_substr($drink['name'], 0, 1, 'UTF-8')}}</span>
                    @endif
                    <span style="font-size: 13px;">{{ $drink['quantity'] }}</span><BR>
                    @endforeach
                    @endif
                  </td>
                  <td>
                  @if(!empty($order_product['main_meal']['option_values'][1022]['drink']))
                    @foreach($order_product['main_meal']['option_values'][1022]['drink'] as $drink_option_value_id => $drink)
                    @if(!empty(mb_substr($drink['name'], 0, 1, 'UTF-8')==='季'))
                    <span style="font-size: 15px;">{{ mb_substr($drink['name'], 2, 1, 'UTF-8')}}</span>
                    @else
                    <span style="font-size: 15px;">{{ mb_substr($drink['name'], 0, 1, 'UTF-8')}}</span>
                    @endif
                    <span style="font-size: 13px;">{{ $drink['quantity'] }}</span><BR>
                    @endforeach
                    @endif
                  </td>
                  <td style="border-right:3px solid black">
                  @if(!empty($order_product['main_meal']['option_values'][1093]['drink']))
                    @foreach($order_product['main_meal']['option_values'][1093]['drink'] as $drink_option_value_id => $drink)
                    @if(!empty(mb_substr($drink['name'], 0, 1, 'UTF-8')==='季'))
                    <span style="font-size: 15px;">{{ mb_substr($drink['name'], 2, 1, 'UTF-8')}}</span>
                    @else
                    <span style="font-size: 15px;">{{ mb_substr($drink['name'], 0, 1, 'UTF-8')}}</span>
                    @endif
                    <span style="font-size: 13px;">{{ $drink['quantity'] }}</span><BR>
                    @endforeach
                    @endif
                  </td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td style="border-right:3px solid black"></td>
                  <td ></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <!-- <td>
                  @if(!empty($order_product['main_meal']['option_values'][1072]['drink']))
                    @foreach($order_product['main_meal']['option_values'][1072]['drink'] as $drink_option_value_id => $drink)
                                       <span style="font-size: 15px;">{{ mb_substr($drink['name'], 0, 1, 'UTF-8')}}</span>
                    <span style="font-size: 13px;">{{ $drink['quantity'] }}</span><BR>
                    @endforeach
                    @endif
                  </td> -->
                </tr>
                @if(!empty($order_product['comment']))
                <tr style="height:3px">
                  <td colspan="11"><span style="font-size: 10px;">商品備註：</span>{{ $order_product['comment'] }}</td>
                </tr>
                @endif
                @if($order['final_products'][$key]['product_id']!==1044)
                @php unset($order['final_products'][$key]) @endphp
                @endif
                @endif
                @endforeach
              </tbody>
            </table>

        <table data-toggle="table" class="table table-bordered border border-dark tr-border-special" style="margin-top:3px;margin-bottom:0px;">
            @foreach($order['final_products'] as $key => $order_product)
            @if($order_product['product_id']===1044)
            <tr>
              <td>備註: 客製盒餐 * {{ $order_product['quantity'] }}份。
              @if(!empty($order_product['price']))
                {{ $order_product['price'] }}
              @else
                0
                @endif
                元  /@
              @foreach($order_product['product_options'] as $option_name => $options)
              @if($option_name!=='飲料' && $option_name!=='主餐')
              【{{ $option_name }}{{":"}}】

                @foreach($options as $option_value_name => $quantity)
                  {{ $option_value_name }}*{{ $quantity }}
                  @endforeach
                  @endif
              @endforeach
                <!-- @if(!empty($order_product['drink']))
                【{{"飲料:"}}】
                      @foreach($order_product['drink']['option_values'] as $product_option_value_id => $value)
                        {{ $value['name'] }}*{{ $value['quantity'] }},
                      @endforeach

                @endif -->

              </td>
            </tr>
            @endif
            @endforeach
          </table>
          {{-- 單點品項 --}}
        <table data-toggle="table" class=" table-bordered border border-dark tr-border-top" style="margin-top:3px;margin-bottom:0px;">
          <tbody>
          @foreach($order['final_products'] as $key =>$order_product)
              @if($order_product['name'] == '其它商品組')
                  <tr>
                    <td  style="width:113px;">單點品項</td>
                    <td style="width:24px;"  class=" fw-bold">小計</td>
                    <!-- <td class=" fw-bold">極品</td> -->
                    <td style="width:24px;" class=" fw-bold">全素</td>
                    <td style="width:24px;" class=" fw-bold">蛋素</td>
                    <td style="width:24px;" class=" fw-bold">薯泥</td>
                    <td style="width:24px;" class=" fw-bold">炸蝦</td>
                    <td style="width:24px;" class=" fw-bold">芥雞</td>
                    <td style="width:24px;" class=" fw-bold">酥魚</td>
                    <td style="width:24px;" class=" fw-bold">培根</td>
                    <td style="width:24px;border-right:3px solid black" class=" fw-bold">滷肉</td>
                    <!-- <td style="width:24px;border-right:3px solid black" class=" fw-bold">春捲</td> -->
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;border-right:3px solid black" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold">微豆</td>
                    <td style="width:24px;" class=" fw-bold">無豆</td>
                    <td style="width:24px;" class=" fw-bold">紅茶</td>
                    <td style="width:24px;" class=" fw-bold">奶茶</td>
                    <td style="width:24px;" class=" fw-bold">濃湯</td>
                    <td style="width:24px;" class=" fw-bold">甜湯</td>

                  </tr>
                  @php $bento_title_showed = 1; @endphp
                <tr>
                  <td>{{ $order_product['name'] }}</td>
                  <td>{{ $order_product['quantity'] }}</td>
                  <!-- <td>
                  @if(!empty($order_product['main_meal']['option_values'][1092]))
                  {{ $order_product['main_meal']['option_values'][1092]['quantity'] ?? 0 }}
                  @endif
                  </td> -->
                  <td>
                    @if(!empty($order_product['product_options']['6吋潤餅']['全素薯泥潤餅']))
                  {{ $order_product['product_options']['6吋潤餅']['全素薯泥潤餅'] ?? 0 }}
                  @endif
                    </td>
                  <td>
                    @if(!empty($order_product['product_options']['6吋潤餅']['蛋素薯泥潤餅']))
                  {{ $order_product['product_options']['6吋潤餅']['蛋素薯泥潤餅'] ?? 0 }}
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['product_options']['6吋潤餅']['薯泥潤餅']))
                  {{ $order_product['product_options']['6吋潤餅']['薯泥潤餅'] ?? 0 }}
                  @endif
                    </td>
                  <td>
                    @if(!empty($order_product['product_options']['6吋潤餅']['炸蝦潤餅']))
                  {{ $order_product['product_options']['6吋潤餅']['炸蝦潤餅'] ?? 0 }}
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['product_options']['6吋潤餅']['芥雞潤餅']))
                  {{ $order_product['product_options']['6吋潤餅']['芥雞潤餅'] ?? 0 }}
                  @endif
                    </td>
                  <td>
                    @if(!empty($order_product['product_options']['6吋潤餅']['酥魚潤餅']))
                  {{ $order_product['product_options']['6吋潤餅']['酥魚潤餅'] ?? 0 }}
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['product_options']['6吋潤餅']['培根潤餅']))
                  {{ $order_product['product_options']['6吋潤餅']['培根潤餅'] ?? 0 }}
                  @endif
                    </td>
                  <td style="border-right:3px solid black">
                    @if(!empty($order_product['product_options']['6吋潤餅']['滷肉潤餅']))
                  {{ $order_product['product_options']['6吋潤餅']['滷肉潤餅'] ?? 0 }}
                  @endif
                    </td>
                    <td>
                    <!-- @if(!empty($order_product['product_options']['6吋潤餅']['春捲']))
                  {{ $order_product['product_options']['6吋潤餅']['春捲'] ?? 0 }}
                  @endif -->
                    </td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td style="border-right:3px solid black"></td>
                    <td>
                    @if(!empty($order_product['product_options']['飲料']['微豆']))
                  {{ $order_product['product_options']['飲料']['微豆'] ?? 0 }}
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['product_options']['飲料']['無豆']))
                  {{ $order_product['product_options']['飲料']['無豆'] ?? 0 }}
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['product_options']['飲料']['紅茶']))
                  {{ $order_product['product_options']['飲料']['紅茶'] ?? 0 }}
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['product_options']['飲料']['奶茶']))
                  {{ $order_product['product_options']['飲料']['奶茶'] ?? 0 }}
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['product_options']['飲料']['濃湯']))
                  {{ $order_product['product_options']['飲料']['濃湯'] ?? 0 }}
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['product_options']['飲料']['季節甜品']))
                  {{ $order_product['product_options']['飲料']['季節甜品'] ?? 0 }}
                  @endif
                    </td>


                </tr>
                {{-- 如果有備註 --}}
                <!-- @if(!empty($order_product['comment']))
                <tr style="height:3px">
                  <td colspan="11"><span style="font-size: 10px;">商品備註：</span>{{ $order_product['comment'] }}</td>
                </tr>
                @endif
                 -->
              @endif
              @endforeach
              @endif
          </tbody>
        </table>
        <table data-toggle="table" class="table table-bordered border border-dark tr-border-special" style="margin-top:3px;margin-bottom:0px;">
            @foreach($order['final_products'] as $key => $order_product)
            @if($order_product['product_id']===1062 && $order_product['show']=== true)
            <tr>
              <td>單點：
              @foreach($order_product['product_options'] as $option_name => $options)
                @if($option_name!=='飲料')
                  【{{ $option_name }}{{":"}}】

                   @foreach($options as $option_value_name => $quantity)
                    {{ $option_value_name }}*{{ $quantity }}
                   @endforeach
                @endif
              @endforeach
                <!-- @if(!empty($order_product['drink']))
                【{{"飲料:"}}】
                      @foreach($order_product['drink']['option_values'] as $product_option_value_id => $value)
                        {{ $value['name'] }}*{{ $value['quantity'] }},
                      @endforeach

                @endif -->
              </td>
            </tr>
            @endif
            @endforeach
          </table>
        {{-- 豆花 --}}
        <table data-toggle="table" class=" table-bordered border border-dark tr-border-top" style="margin-top:3px;margin-bottom:0px;">
          <tbody>
            @php
              $douhua_title_showed = 1;
            @endphp
            @foreach($order['final_products'] as $key =>$order_product)
            @if($order_product['main_category_code'] == 'douhua')
            @if($douhua_title_showed == 1)
                  <tr>
                    <td  style="width:113px;">豆花系列</td>
                    <td style="width:24px;" class=" fw-bold">小計</td>
                    <!-- <td class=" fw-bold">極品</td> -->
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>
                    <td style="width:24px;" class=" fw-bold"></td>

                  </tr>
                  @php $douhua_title_showed = 0; @endphp
                @endif
                <tr>
                  <td>{{ $order_product['name'] }}</td>
                  <td>{{ $order_product['quantity'] }}</td>
                  <!-- <td>
                  @if(!empty($order_product['main_meal']['option_values'][1092]))
                  {{ $order_product['main_meal']['option_values'][1092]['quantity'] ?? 0 }}
                  @endif
                  </td> -->
                  <!-- <td>
                    @if(!empty($order_product['product_options']['副主餐']['控肉']))
                  {{ $order_product['product_options']['副主餐']['控肉'] ?? 0 }}
                  @endif
                    </td>
                  <td>
                    @if(!empty($order_product['product_options']['副主餐']['清蒸嫰雞胸']))
                  {{ $order_product['product_options']['副主餐']['清蒸嫰雞胸'] ?? 0 }}
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['product_options']['配菜']['香滷雞翅']))
                  {{ $order_product['product_options']['配菜']['香滷雞翅'] ?? 0 }}
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['product_options']['副主餐']['滷雞腿']))
                  {{ $order_product['product_options']['副主餐']['滷雞腿'] ?? 0 }}
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['product_options']['副主餐']['滷牛腱']))
                  {{ $order_product['product_options']['副主餐']['滷牛腱'] ?? 0 }}
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['product_options']['副主餐']['鮭魚']))
                  {{ $order_product['product_options']['副主餐']['鮭魚'] ?? 0 }}
                  @endif
                    </td>
                    <td>
                    @if(!empty($order_product['product_options']['副主餐']['紅酒牛肉丸']))
                  {{ $order_product['product_options']['副主餐']['紅酒牛肉丸'] ?? 0 }}
                  @endif
                    </td> -->
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>



                </tr>

                {{-- 如果有飲料 --}}
                <!-- @if(!empty($order_product['drink']))
                <tr>
                  <td colspan="11"><span style="font-size: 10px;">飲料：</span>

                      @foreach($order_product['drink']['option_values'] as $product_option_value_id => $value)
                        {{ $value['name'] }}*{{ $value['quantity'] }},
                      @endforeach

                  </td>
                </tr>
                @endif -->

                {{-- 如果有備註 --}}
                @if(!empty($order_product['comment']))
                <tr style="height:3px">
                  <td colspan="11"><span style="font-size: 10px;">商品備註：</span>{{ $order_product['comment'] }}</td>
                </tr>
                @endif
                @php unset($order['final_products'][$key]) @endphp
                @endif
                @endforeach
          </tbody>
        </table>


      {{-- 客戶簽收 --}}
      <table data-toggle="table" class="table table-bordered border border-dark tr-border-top">
        <tr>
        <td class="align-top" style="width: 50%;border-right:3px solid black" >
        <p style="white-space: pre-wrap;margin:0">訂單備註：{{ $order['order']->comment }}</p>
        </td>
          <td class="border-right:3px solid black">
          客戶簽收：<BR>
          @if( $order['order']->payment_method ==='cash')
          <input type="checkbox" id="checkedCheckbox" name="checkedCheckbox" checked>
          {{"現金"}}
           @else
          <input type="checkbox">
          {{"現金"}}
          @endif
          <BR>
          @if( $order['order']->payment_method ==='debt')
          <input type="checkbox" id="checkedCheckbox" name="checkedCheckbox" checked> {{"預計匯款日"}}
          @if(!empty($order['order']->scheduled_payment_date))
          {{ $order['order']->scheduled_payment_date}}
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
      @if(!empty($order['order']->order_taker))
      接單人員:  {{$order['order']->order_taker }}
      @else
      接單人員:__________
      @endif
      <br>
      <div class="fw-bold"  style="text-align: right;margin-right: 1.5em">{{'製單時間：'}}{{ $order['order']->now }}</div>
    </div>


    @endforeach

    <script type="text/javascript">
      //ElonLee

    </script>
</body>
</html>
