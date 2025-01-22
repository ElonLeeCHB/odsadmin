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
        <span class="fw-bold">送達公司：</span><span>{{ $order['header']->shipping_comment }}
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
        <p style="white-space: pre-wrap;margin:0">餐點備註：{{ $order['header']->extra_comment }}</p>
          <div style="text-align: right;">
            <input type="checkbox"> {{"小卡"}}
            外送五步驟：清檢放統備 &nbsp;&nbsp;簽名：_______________
        </div>
      </td>
      </tr>
    </tbody>
  </table>
{{-- end 客戶備註 --}}