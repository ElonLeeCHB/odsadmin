@extends('admin.app')

@section('pageJsCss')

<!-- DataTable -->
<link  href="{{ asset('assets/vendor/select2/select2.min.css') }}" rel="stylesheet" type="text/css"/>
<script src="{{ asset('assets/vendor/select2/select2.min.js') }}"></script>
<link  href="{{ asset('assets/stylesheet/path/sale/order_form.css') }}" rel="stylesheet" type="text/css"/>
</style>
@endsection

@section('columnLeft')
  @include('admin.common.column_left')
@endsection

@section('content')
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="float-end">
        <a data-href="{{ $printReceiveForm }}" id="href-printReceiveForm"  target="_blank" data-bs-toggle="tooltip" title="列印訂單簽收單" class="btn btn-info"><i class="fa-solid fa-print"></i></a>
        <button type="submit" id="btn-save-order_form" form="form-order" data-bs-toggle="tooltip" title="Save" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i></button>
        <a href="{{ $back }}" id="href-save" data-bs-toggle="tooltip" title="{{ $lang->button_back }}" class="btn btn-light"><i class="fa-solid fa-reply"></i></a>
      </div>
      @include('admin.common.breadcumb')
    </div>
  </div>
  <div class="container-fluid">
    <div class="card">
      <div class="card-body">
        <form id="form-order" action="{{ $save }}" method="post" data-oc-toggle="ajax">
          @csrf
          @method('POST')
          <input type="hidden" id="input-location_id" name="location_id" value="{{ $order->location_id }}" >
          <input type="hidden" id="input-order_id" name="order_id" value="{{ $order->id }}" >
          <ul class="nav nav-tabs">
            <li class="nav-item"><a href="#tab-general" data-bs-toggle="tab" class="nav-link active">訂單資料</a></li>
            <li class="nav-item"><a href="#tab-products" data-bs-toggle="tab" class="nav-link">商品與備註</a></li>
          </ul>
          <div class="tab-content">
            <div id="tab-general" class="tab-pane active">
              <fieldset>
                <div>
                  <table class="table table-bordered table-hover" id="table-order-header">
                    <tbody>
                      <tr>
                        <td colspan="8">

                        <label for="input-order_date" class="colname-font">訂購日期 </label>
                        <input type="date" id="input-order_date" name="order_date" value="{{ $order->order_date }}">

                        &nbsp;&nbsp;&nbsp;<label for="input-status_id" class="colname-font">訂單狀態 </label>
                          <select id="input-status_id" name="status_id">
                            <option value="">--</option>
                              @foreach($order_statuses as $status)
                              <option value="{{ $status->id }}" @if($status->id == $status_id) selected @endif>{{ $status->name }}</option>
                              @endforeach
                          </select>&nbsp;&nbsp;

                          <label for="input-shipping_method" class="colname-font">取貨方式 </label>
                          <select id="input-shipping_method" name="shipping_method">
                            <option value="">--</option>
                            <option value="shipping_pickup" @if($shipping_method == 'shipping_pickup' ) selected="selected" @endif>自取</option>
                            <option value="shipping_delivery" @if($shipping_method == 'shipping_delivery' )selected="selected" @endif>派送</option>
                          </select>

                        </td>
                      </tr>
                      <tr>
                        <td class="text-end colname-font" style="width:85px;">{{ $lang->column_delivery_date }}</td>
                        <td class="text-start width6char" ><input type="date" id="input-delivery_date_ymd" name="delivery_date_ymd" value="{{ $order->delivery_date_ymd }}" class="form-control"></td>
                        <td class="text-end colname-font" style="width: 70px;">{{ $lang->column_delivery_day_of_week }}</td>
                        <td class="text-start width7char"><input type="text" id="input-delivery_day_of_week" name="delivery_day_of_week" value="{{ $order->delivery_weekday }}" class="form-control" readonly></td>

                        <td class="text-start" colspan="4">
                          <label target="_blank" for="input-delivery_time_range" data-bs-toggle="tooltip" title="例如：1130-1230" class="colname-font"><i class="fa fa-question-circle" aria-hidden="true"></i> 送達時間範圍</label>
                          <input type="text" id="input-delivery_time_range" name="delivery_time_range" value="{{ $order->delivery_time_range }}" placeholder="例如 1130 - 1230" style="width:120px;" >

                          &nbsp;&nbsp;
                          <label target="_blank" for="input-delivery_date_hi" data-bs-toggle="tooltip" title="格式必須是幾點:幾分，例如 12:30。" class="colname-font"><i class="fa fa-question-circle" aria-hidden="true"></i> 送達時間</label>
                          <input type="text" id="input-delivery_date_hi" name="delivery_date_hi"value="{{ $order->delivery_date_hi }}" class="width4char" placeholder="例如 12:00" >

                          &nbsp;&nbsp;
                          <label target="_blank" for="input-shipping_road_abbr" data-bs-toggle="tooltip" title="任意文字。請盡量簡短。例如&quot;中山南&quot;" class="colname-font"><i class="fa fa-question-circle" aria-hidden="true"></i> 送達路段</label>
                          <input type="text" id="input-shipping_road_abbr" name="shipping_road_abbr" value="{{ $order->shipping_road_abbr }}" style="width:110px;">

                          &nbsp;&nbsp;
                          <label target="_blank" for="input-delivery_time_comment" data-bs-toggle="tooltip" title="a 或 b 或 a,b" class="colname-font"><i class="fa fa-question-circle" aria-hidden="true"> 控單表備註</i></label>
                          <input type="text" id="input-delivery_time_comment" name="delivery_time_comment" value="{{ $order->delivery_time_comment }}" class="width4char"><BR>
                        </td>
                      </tr>

                      <tr>
                        <td class="text-end colname-font required" style="width: 105px;">{{ $lang->column_personal_name }}</td>
                        <td class="text-start width6char" >
                          <input  type="hidden" id="input-customer_id" name="customer_id" value="{{ $order->customer_id }}" >
                          <div class="input-group mb-3">
                            <input type="text" id="input-personal_name" name="personal_name" class="form-control" aria-label="personal_name" value="{{ $order->personal_name }}" data-oc-target="autocomplete-personal_name">
                            <div class="input-group-append">
                              <a class="input-group-text" target="_self" id="a-order_list" href="sale/orders?filter_customer_id={{ $order->customer_id }}"><i class="fa-solid fa-list"></i></a>
                            </div>
                            <ul id="autocomplete-personal_name" class="dropdown-menu" style="margin-top:30px;"></ul>
                            <div id="error-personal_name" class="invalid-feedback"></div>
                          </div>

                          <div style="display: flex;flex-direction: row;justify-content: space-between;">
                            <div class="text-start">
                              <input id="input-customer" name="customer" value="{{ $order->customer }}" class="form-control" disabled>
                            </div>
                            <div class="text-end">
                            <i class="fa fa-times-circle" data-bs-toggle="tooltip" title="清空訂購人" style="color:grey;" onclick="clearCustomer();"></i>
                            </div>
                          </div>
                        </td>

                        <td class="text-end colname-font" style="width: 70px;">{{ $lang->column_salutation }}</td>
                        <td class="text-start">
                          <select name="salutation_id" id="input-salutation_id" class="form-select">
                            <option value="">--</option>
                              @foreach($salutations as $salutation)
                                <option value="{{ $salutation->option_value_id }}" @if($member->salutation_id == $salutation->option_value_id) selected @endif>{{ $salutation->name  }}</option>
                              @endforeach
                          </select>
                        </td>

                        <td class="text-end" style="width:100px;">
                          <label target="_blank" data-bs-toggle="tooltip" title="儲存時系統會自動刪除橫線" class="colname-font"><i class="fa fa-question-circle" aria-hidden="true"></i> {{ $lang->column_mobile }}</label><BR>
                          <label target="_blank" data-bs-toggle="tooltip" title="儲存時系統會自動刪除橫線" class="colname-font"><i class="fa fa-question-circle" aria-hidden="true"></i> {{ $lang->column_telephone }}</label>
                        </td>

                        <td class="text-start" style="width:180px;">
                          <input type="text" id="input-mobile" name="mobile" aria-label="mobile" class="form-control" value="{{ $order->mobile }}" placeholder="查詢時請輸入至少3個數字" data-oc-target="autocomplete-mobile" style="width:158px;" />
                          <ul id="autocomplete-mobile" class="dropdown-menu"></ul>
                          <div id="error-mobile" class="invalid-feedback"></div>

                          <input type="text" id="input-telephone_prefix" name="telephone_prefix" value="{{ $order->telephone_prefix }}" placeholder="區碼" style="width:30px"/>
                          <input type="text" id="input-telephone" name="telephone" value="{{ $order->telephone }}" placeholder="查詢時請輸入至少3個數字" data-oc-target="autocomplete-telephone" style="width:125px;" />
                          <ul id="autocomplete-telephone" class="dropdown-menu"></ul>
                          <div id="error-telephone" class="invalid-feedback"></div>
                          <span id="span-hasOrder" style="color:red"></span>

                        </td>

                        <td class="text-end colname-font" style="width: 100px;">{{ $lang->column_email }}</td>
                        <td class="text-start">
                          <input type="text" id="input-email" name="email" value="{{ $order->email }}" placeholder="{{ $lang->column_email }}" data-oc-target="autocomplete-email"  class="form-control"/>
                          <ul id="autocomplete-email" class="dropdown-menu"></ul>
                        </td>
                      </tr>

                      <tr>
                        <td class="text-end colname-font" style="width: 105px;">{{ $lang->column_payment_company }}</td>
                        <td class="text-start" colspan="3" >


                        <div class="input-group mb-3">
                          <input type="text" id="input-payment_company" name="payment_company" class="form-control" aria-label="payment_company" value="{{ $order->payment_company }}" data-oc-target="autocomplete-payment_company">
                          <div class="input-group-append">
                            <a class="input-group-text" target="_self" id="a-payment_company"><i class="fa-solid fa-eraser"></i></a>
                          </div>
                        </div>


                          <div class="input-group">

                            <input type="text" id="input-payment_company_shortname" name="payment_company_shortname" value="" placeholder="公司簡稱" class="form-control w-50">
                            <input type="text" id="input-payment_department" name="payment_department" value="{{ $order->payment_department }}" placeholder="部門" class="form-control w-50">

                          </div>
                        </td>
                        <td class="text-end colname-font">是否統編</td>
                        <td class="text-start" style="font-size: 16px;">
                          <select id="input-is_payment_tin" name="is_payment_tin">
                            <option value="">請選擇</option>
                            <option value="0" @if($order->is_payment_tin == 0) selected @endif>不需要</option>
                            <option value="1" @if($order->is_payment_tin == 1) selected @endif>需要</option>
                          </select>
                          <div id="error-is_payment_tin" class="invalid-feedback"></div>


                          <input type="text" id="input-payment_tin" name="payment_tin" value="{{ $order->payment_tin }}" placeholder="統一編號" data-oc-target="autocomplete-payment_tin" class="form-control" autocomplete="off">
                          <ul id="autocomplete-payment_tin" class="dropdown-menu"></ul>

                        </td>
                        <td class="text-end colname-font">訂單標籤</td>
                        <td class="text-start">
                          <select id="input-order_tag" name="order_tag[]" class="select2-multiple form-control" multiple="multiple">

                          </select><BR>
                          <div class="selOrderTag">
                            <button type="button">會</button>
                            <button type="button">教</button>
                            <button type="button">幫</button>
                            <button type="button">清</button>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td class="text-end colname-font required" style="width: 105px;">{{ $lang->column_shipping_personal_name }}</td>
                        <td class="text-start" colspan="2">
                          <input type="text" id="input-shipping_personal_name" name="shipping_personal_name" value="{{ $order->shipping_personal_name }}" class="form-control">
                          <div id="error-shipping_personal_name" class="invalid-feedback"></div>

                          <div class="form-check" style="font-size: 0.8em;">
                            <input type="checkbox" name="same_order_customer" id="input-same_order_customer" class="form-check-input">
                            <label for="input-same_order_customer" class="form-check-label">同訂購人</label>
                          </div>

                        </td>
                        <td class="text-end colname-font" style="width: 60px;">地址</td>
                        <td class="text-start" colspan="4">
                          <div class="col-sm-12">
                            <div class="input-group shipping">
                              <select id="input-shipping_state_id" name="shipping_state_id">
                                <option value="">--</option>
                                @foreach($states as $state)
                                <option value="{{ $state->id }}" @if($state->id == $order->shipping_state_id) selected @endif>{{ $state->name }}</option>
                                @endforeach
                              </select>
                              <select id="input-shipping_city_id" name="shipping_city_id">
                                @foreach($shipping_cities as $city)
                                <option value="{{ $city->id }}" @if($city->id == $order->shipping_city_id) selected @endif>{{ $city->name }}</option>
                                @endforeach
                              </select>
                              <input type="text" id="input-shipping_road" name="shipping_road" value="{{ $order->shipping_road }}" data-oc-target="autocomplete-shipping_road" style="width:120px;">
                                <ul id="autocomplete-shipping_road" class="dropdown-menu" style="margin-left: 90px;margin-top: 30px;"></ul>
                                <div id="error-shipping_road" class="invalid-feedback"></div>
                              <input type="text" id="input-shipping_address1" name="shipping_address1" value="{{ $order->shipping_address1 }}" placeholder="路段後面的地址" class="form-control">
                            </div>
                          </div>

                          <div class="addAddrPartName">
                            <button type="button">巷</button> <button type="button">弄</button> <button type="button">衖</button> <button type="button">號</button>
                            <button type="button">棟</button> <button type="button">大樓</button> <button type="button">樓</button> <button type="button">房</button>
                            <button type="button">室</button>
                            <BR><input type="text" id="input-original_address" name="original_address" placeholder="統編登記地址" value="" readonly style="width:100%">
                          </div>

                          <!--<button type="button" data-bs-toggle="modal" data-bs-target="#modal-customer" class="btn btn-outline-primary"><i class="fa-solid fa-cog"></i></button>-->
                        </td>
                      </tr>
                      <tr>
                        <td class="text-end colname-font" style="width: 105px;">{{ $lang->column_shipping_company }}</td>
                        <td class="text-start" colspan="2" ><input type="text" id="input-shipping_company" name="shipping_company" value="{{ $order->shipping_company }}" class="form-control">
                          <div class="form-check" style="font-size: 0.8em;">
                            <input type="checkbox" name="same_as_order_company" id="input-same_as_order_company" class="form-check-input">
                            <label for="input-same_as_order_company" class="form-check-label">同訂餐公司</label>
                          </div>
                      </td>
                        <td class="text-end colname-font" style="width: 70px;" colspan="2">{{ $lang->column_shipping_phone }}</td>
                        <td class="text-start" colspan="3"><input type="text" id="input-shipping_phone" name="shipping_phone" value="{{ $order->shipping_phone }}" class="form-control"></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </fieldset>

              <fieldset>
                  <div>
                    <table class="table table-bordered table-hover" id="table-payment">
                      <tr>
                        <td colspan="2">
                          <span class="colname-font">付款狀況</span>
                      </td>
                      </tr>
                      <tr>
                        <td>付款方式：&nbsp;
                          <input type="radio" id="input-payment_method-cash" name="payment_method" value="cash" @if($order->payment_method=='cash') checked @endif>
                          <label for="input-payment_method-cash">現金</label>&nbsp;

                          <input type="radio" id="input-payment_method-wire" name="payment_method" value="wire" @if($order->payment_method=='wire') checked @endif>
                          <label for="input-payment_method-wire">轉帳</label>&nbsp;

                          <input type="radio" id="input-payment_method-credit" name="payment_method" value="credit" @if($order->payment_method=='credit') checked @endif>
                          <label for="input-payment_method-credit">信用卡</label>&nbsp;

                          <input type="radio" id="input-payment_method-debt" name="payment_method" value="debt" @if($order->payment_method=='debt') checked @endif>
                          <label for="input-payment_method-debt">賒帳</label>
                          <label data-bs-toggle="tooltip" title="若賒帳，請填寫預計付款日"><i class="fa fa-question-circle"> </i></label>
                          <input type="date" id="input-scheduled_payment_date" name="scheduled_payment_date" value="{{ $order->scheduled_payment_date }}">

                        </td>
                        <td>
                          總金額：<input type="text" id="input-payment_total" value="{{ $order->payment_total }}" style="width:80px" readonly>&nbsp;&nbsp; &nbsp;&nbsp;
                          已付金額： <input type="text" id="input-payment_paid" name="payment_paid" value="{{ $order->payment_paid }}" style="width:80px">&nbsp;&nbsp;
                          未付餘額： <input type="text" id="input-payment_unpaid" name="payment_unpaid" value="{{ $order->payment_unpaid }}" style="width:80px" readonly >&nbsp;&nbsp;

                        </td>
                      </tr>
                      <tr>
                        <td></td>
                        <td></td>
                      </tr>
                    </table>
                  </div>
              </fieldset>

              <label for="input-old_code" class="form-check-label">紙本訂單編號</label>
              <input type="text" id="input-old_code" name="old_code" value="{{ $order->old_code }}"><BR>
              建單時間：{{ $order->created_at }}<BR>
              修改時間：{{ $order->updated_at }}

            </div>
            <div id="tab-products" class="tab-pane">
              <fieldset>
                <div class="row mb-3">
                  <label for="input-comment" class="col-sm-1 col-form-label" style="height:20px;">{{ $lang->column_comment }}</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <input id="input-comment" name="comment" value="{{ $order->comment }}" class="form-control">
                      <div class="input-group-append">
                        <a class="input-group-text" target="_self" id="a-order_comment" href="javascript:void(0)" style="height:40px;"><i class="fa-solid fa-list"></i></a>
                      </div>
                    </div>
                    <div id="error-comment" class="invalid-feedback"></div>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="input-extra_comment" class="col-sm-1 col-form-label" style="height:20px;">{{ $lang->column_extra_comment }}</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <input id="input-extra_comment" name="extra_comment" value="{{ $order->extra_comment }}" class="form-control">
                      <div class="input-group-append">
                        <a class="input-group-text" target="_self" id="a-order_extra_comment" href="javascript:void(0)" style="height:40px;"><i class="fa-solid fa-list"></i></a>
                      </div>
                    </div>
                    <div id="error-extra_comment" class="invalid-feedback"></div>
                  </div>
                </div>

                <div class="table-responsive">
                  <span style="color:red">* 素食商品請注意配菜數量</span>

                  <table id="order_products" class="table table-bordered table-hover">
                    <tbody id="tbody_order_products">

                    @foreach($html_order_products as $html_order_product )
                      {!! $html_order_product !!}
                    @endforeach

                    </tbody>
                    <tfoot>
                      <tr>
                        <td class="text-end" colspan="12">
                          <button type="button" id="button-refresh" data-bs-toggle="tooltip" title="重新計算" class="btn btn-outline-primary" ><i class="fa-solid fa-rotate"></i></button>
                          <button type="button" onclick="addProduct();" data-bs-toggle="tooltip" title="{{ $lang->button_add_product }}" class="btn btn-primary"><i class="fa-solid fa-plus-circle"></i></button>
                        </td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </fieldset>
              <table class="table table-bordered">
                <tbody id="order-totals">
                  @foreach($order_totals as $code => $order_total)
                  <tr>
                    <td class="text-end col-sm-10"><strong>{{ $order_total->title }}</strong></td>
                    <td class="text-end">
                      <input type="hidden" name="order_totals[{{ $code }}][title]" value="{{ $order_total->title }}">
                      <input type="text" id="input-total-{{ $code }}" name="order_totals[{{ $code }}][value]" value="{{ $order_total->value }}" class="form-control" onchange="calcTotal()">
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
          </form>
      </div>
    </div>
  </div>
</div>{{-- End of content--}}

<div id="modal-phrases-comment" class="modal fade show" aria-modal="true" role="dialog" style="display: none; padding-left: 0px;">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa-solid fa-pencil"></i> 客戶備註 常用片語</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <table id="table-phrase-comment" class="table table-striped dataTable">
          <thead>
            <tr>
              <th class="sorting sorting_asc" tabindex="0" aria-controls="table-phrase-comment" aria-sort="ascending" >排序</th>
              <th class="sorting" tabindex="0" aria-controls="table-phrase-comment" aria-sort="ascending" >常用片語</th>
            </tr>
          </thead>
          <tbody>
            @foreach($order_comment_phrases as $phrase)
            <tr>
              <td class="phrase sorting_1">{{ $phrase->sort_order }}</td>
              <td class="phrase sorting_2" data-phrase-column="comment">{{ $phrase->translation->name }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" id="button-save" data-option-row="0" data-option-value-row="0" class="btn btn-primary">儲存</button> <button type="button" class="btn btn-light" data-bs-dismiss="modal">取消</button>
      </div>
    </div>
  </div>
</div>
<div id="modal-phrases-extra_comment" class="modal fade show" aria-modal="true" role="dialog" style="display: none; padding-left: 0px;">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa-solid fa-pencil"></i> 客戶備註 常用片語</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <table id="table-phrase-extra_comment" class="table table-striped dataTable">
          <thead>
            <tr>
              <th class="sorting sorting_asc" tabindex="0" aria-controls="table-phrase-comment" aria-sort="ascending" >排序</th>
              <th class="sorting" tabindex="0" aria-controls="table-phrase-extra_comment" aria-sort="ascending" >常用片語</th>
            </tr>
          </thead>
          <tbody>
            @foreach($order_extra_comment_phrases as $phrase)
            <tr>
              <td class="phrase sorting_1">{{ $phrase->sort_order }}</td>
              <td class="phrase sorting_2" data-phrase-column="extra_comment">{{ $phrase->name }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" id="button-save" data-option-row="0" data-option-value-row="0" class="btn btn-primary">儲存</button> <button type="button" class="btn btn-light" data-bs-dismiss="modal">取消</button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('buttom')
<script type="text/javascript">

//關閉全部的 autocomplete
$('input').attr('autocomplete', 'off');

//列印按鈕
$('#href-printReceiveForm').on('click',function(e){
  e.preventDefault();
  var order_id = $('#input-order_id').val();
  var href = $(this).data('href');
  var current_order_id = href.match(/[^\/]*$/);
  if(current_order_id == '%20' && $.isNumeric(order_id)){
    href = href.replace('%20',order_id);
  }
  window.open(href);
});

//暫時不用
/*
$( "input,textarea" ).focusin(function() {
  window.addEventListener('beforeunload', myBeforeUnload);
});

$('#btn-save-order_form').on('click', function(){
  window.removeEventListener('beforeunload', myBeforeUnload);
});

window.addEventListener('beforeunload', myBeforeUnload);

//防止誤按關閉瀏覽器
function myBeforeUnload(event){
  // // Cancel the event as stated by the standard.
  // event.preventDefault();
  // // Chrome requires returnValue to be set.
  // event.returnValue = '';
}
//
*/


// 單頭
var shipping_city_id = {{ $order->shipping_city_id ?? 0 }}
      ,shipping_road = '';

var payment_total = parseInt({{ $order->payment_total ?? 0 }})
    , payment_paid = parseInt({{ $order->payment_paid ?? 0 }})
    , payment_unpaid = parseInt({{ $order->payment_unpaid ?? 0 }})
    
$(document).on("change",'#input-nav_location_id', function(){
  var location_id = $(this).val();
  $('#input-location_id').val(location_id);
});

$("#input-nav_location_id" ).trigger( "change" );

//選常用片語
// $('#table-phrase-comment').DataTable();
// $('#table-phrase-extra_comment').DataTable();
//alert(33)
var phraseType = '';

//客戶備註選常用片語
$('#input-comment').on('input', function () {
  var order_comment_value = $(this).val();
  if (order_comment_value.indexOf(',,') !== -1) {
    $('#modal-phrases-comment').modal('show');
  }
});

//餐點備註選常用片語
$('#input-extra_comment').on('input', function () {
  var order_comment_value = $(this).val();
  if (order_comment_value.indexOf(',,') !== -1) {
    $('#modal-phrases-extra_comment').modal('show');
  }
});

$(document).on("click",'.phrase', function(){
  phraseType = $(this).data("phrase-column");

  if(phraseType == 'comment'){
    jObj = $('#input-comment');
  }else if(phraseType == 'extra_comment'){
    jObj = $('#input-extra_comment');
  }
  oldString = jObj.val();
  order_comment_phrase = $(this).text();

  splitResult = oldString.split(',,'); // 使用 :: 分割字符串
  order_comment_phrase_before = splitResult[0]; // 分割后的前面字符串
  order_comment_phrase_after = splitResult[1]; // 分割后的后面字符串

  if(typeof order_comment_phrase_after == 'undefined'){
    order_comment_phrase_after = '';
  }

  if (oldString.indexOf(order_comment_phrase) !== -1) {
    newString = oldString;
  }else{
    newString = order_comment_phrase_before + ', '+ order_comment_phrase + ', ' + order_comment_phrase_after;
  }

  newString = newString.replace(/\s+/g, ' ').replace(/,+$/, ',').replace(/,+$/, '').replace(/,\s,\s/g, ', ').replace(/,\s$/g, '').replace(/^,\s*/, '');

  jObj.val(newString);
  $('#modal-phrases-comment').modal('hide');
  $('#modal-phrases-extra_comment').modal('hide');

});

// 訂單標籤
var orderTagBtnTxt = '   ';
var qStr = '';
$('.selOrderTag button').on('click', function(){
  var addStr = $('#input-order_tag').val();
  var buttonText = $(this).text();
  if(buttonText=='清'){
    orderTagBtnTxt = '  ';
    return;
  }
  orderTagBtnTxt = buttonText
});

//已存的訂單標籤
@foreach($order_tag ?? [] as $tag)
  $('.select2-multiple').append(new Option('{{ $tag }}','{{ $tag }}',true,true));
@endforeach

$('.select2-multiple').select2({
  multiple: true,
  ajax: {
    url: "{{ route('lang.admin.sale.orders.autocompleteAllOrderTags') }}",
      dataType: 'json',
      delay: 250,
      data: function(params) {
        orderTagBtnTxt = orderTagBtnTxt.replace(/\s+/g, "");
        if(orderTagBtnTxt.length>0){
          qStr = orderTagBtnTxt;
        }else{
          qStr = params.term;
        }

        return {
            q: qStr, // 使用者輸入的搜尋關鍵字
            page: params.page // 目前頁數
        };
      },
      processResults: function(data, params) {
          // 將 API 回傳的資料轉換成 Select2 可用的格式
          return {
            results: data.items, // 替換為實際的資料欄位名稱
            pagination: {
                more: data.more // 替換為實際的分頁資訊
            }
          };
      },
      cache: true
  },
  // 設定顯示在下拉選單中的資料格式
  templateResult: function(item) {
    if (item.loading) {
        //return '載入中...';
    }
    return item.text;
  },
  // 設定選取項目後要顯示在選取框中的格式
  templateSelection: function(item) {
    //if (item.id === '') {
    if (item.text === '') {
        //return '請選擇';
        return '';
    }
    return item.text;
  }
});

//設定星期幾
$("#input-delivery_date").on('change',function(){
  const d = new Date(this.value);
  let i = d.getDay();
  daystr = ["日","一","二","三","四","五","六"][i];
  $("#input-delivery_day_of_week").val(daystr);
});

//查姓名
$('#input-personal_name').autocomplete({
  'minLength': 1,
  'source': function (request, response) {
      $.ajax({
          url: "{{ route('lang.admin.member.members.autocomplete') }}?filter_personal_name=" + encodeURIComponent(request),
          dataType: 'json',
          success: function (json) {
              response(json);
          }
      });
  },
  'select': function (item) {
    setCustomerInfo(item)
  }
});

//查手機
$('#input-mobile').autocomplete({
  minLength: 3, //not working
  source: function(request, response) {
    request = request.replace(/-/g, "");
    if(request.length > 2){
      $.ajax({
        url: "{{ route('lang.admin.member.members.autocomplete') }}?filter_mobile=" + encodeURIComponent(request) + '&with=orders',
        dataType: 'json',
        success: function(json) {
          response(json);
        }
      });
    }else{
      return false
    }
  },
  select: function(event,ui) {
    setCustomerInfo(event)
  }
});

//查市話
$('#input-telephone').autocomplete({
  'minLength': 3,
  'source': function(request, response) {
    request = request.replace(/-/g, "");
    $.ajax({
      url: "{{ route('lang.admin.member.members.autocomplete') }}?filter_telephone=" + encodeURIComponent(request),
      dataType: 'json',
      success: function(json) {
        response(json);
      }
    });
  },
  'select': function(event,ui) {
    setCustomerInfo(event)
  }
});

//查email
$('#input-email').autocomplete({
  'minLength': 2,
  'source': function(request, response) {
    $.ajax({
      url: "{{ route('lang.admin.member.members.autocomplete') }}?filter_email=" + encodeURIComponent(request) + '&show_column1=name&show_column2=email',
      dataType: 'json',
      success: function(json) {
        response(json);
      }
    });
  },
  'select': function(event,ui) {
    setCustomerInfo(event)
  }
});

//查客戶之後重設單頭
function setCustomerInfo(item){
  $('#input-personal_name').val(item.personal_name);
  $('#input-customer_id').val(item.customer_id);
  $('#input-customer').val(item.customer_id+'_'+item.personal_name);
  $('#input-salutation_id').val(item.salutation_id);
  $('#input-telephone').val(item.telephone);
  $('#input-mobile').val(item.mobile);
  $('#input-email').val(item.email);
  $('#input-payment_company').val(item.payment_company);

  $('#input-payment_tin').val(item.payment_tin);
  $('#input-payment_department').val(item.payment_department);
  $('#input-shipping_company').val(item.shipping_company);
  $('#input-shipping_personal_name').val(item.shipping_personal_name);
  $('#input-shipping_phone').val(item.shipping_phone);

  shipping_city_id = item.shipping_city_id;
  shipping_road = item.shipping_road;

  $("#input-shipping_state_id").val(item.shipping_state_id);
  $("#input-shipping_road").val(item.shipping_road);
  $("#input-shipping_address1").val(item.shipping_address1);

  if(item.has_order){
    $("#a-order_list").attr('href', 'sale/orders?filter_customer_id='+item.customer_id);
    $("#a-order_list").show();
  }else{
    $("#a-order_list").attr('href', '');
    $("#a-order_list").hide();
    $("#span-hasOrder").text('無訂單記錄');
  }

  setShippingState(item.shipping_state_id)
  //swichDCustomerReadonly(true);
}

//查統編
$('#input-payment_tin').autocomplete({
  minLength: 3, //not working
  source: function(request, response) {
    if(request.length > 7){
      $.ajax({
        url: "{{ route('lang.admin.member.guin.autocompleteSingle') }}?filter_payment_tin=" + encodeURIComponent(request),
        dataType: 'json',
        success: function(json) {
          response(json);
        }
      });
    }else{
      return false
    }
  },
  select: function(event,ui) {
    $('#input-payment_company').val(event.label);
    $('#input-payment_company').prop('readonly', true);
    $('#input-shipping_company').val(event.label);

    if(event.address_parts.after_road_section.length == 0){
      if(confirm('資料來源沒有地址，所以地址不進行覆蓋。')){
        return;
      }
    }else{
      if(confirm('是否覆蓋地址？')){
        $('#input-shipping_road').val(event.address_parts.full_road_section);
        $('#input-shipping_address1').val(event.address_parts.after_road_section);
        $('#input-original_address').val(event.original_address);
        $("#input-shipping_state_id").val(event.address_parts.divsionL1_id);

        shipping_city_id = event.address_parts.divsionL2_id;
        shipping_road = event.address_parts.full_road_section;

        setShippingState(event.address_parts.divsionL1_id)
      }
    }
  }
});

$('#a-payment_company').on('click', function(){
    $('#input-payment_company').prop('readonly', false);
    $('#input-payment_company').val('');
});

//重設鄉鎮區選單
function setShippingState(state_id){
  $.ajax({
      type:'get',
      url: "{{ route('lang.admin.localization.divisions.getJsonCities') }}?filter_parent_id=" + state_id,
      success:function(json){
        html = '<option value=""> -- </option>';

        $.each(json, function(i, item) {
          html += '<option value="'+item.city_id+'">'+item.name+'</option>';
        });

        $('#input-shipping_city_id').html(html);

        if(shipping_city_id){
          $('#input-shipping_city_id').val(shipping_city_id);
        }
      }
    });
}

//選縣市查區
$('#input-shipping_state_id').on('change', function(){
  var state_id = $(this).val();
  setShippingState(state_id)
  clearShippingAddress1()
});

//選鄉鎮市區
$('#input-shipping_city_id').on('change', function(){
  clearShippingAddress1()
});

//查路名
$('#input-shipping_road').autocomplete({
  'minLength': 1,
  'source': function(request, response) {
    filter_state_id = $('#input-shipping_state_id').val();
    filter_city_id = $('#input-shipping_city_id').val();
    filter_name = $(this).val();

    if(!filter_state_id && !filter_city_id){
      return false;
    }

    url = '';

    if (filter_state_id) {
      url += '&filter_state_id=' + encodeURIComponent(filter_state_id);
    }

    if (filter_city_id) {
      url += '&filter_city_id=' + encodeURIComponent(filter_city_id);
    }

    if (filter_name) {
      url += '&filter_name=' + encodeURIComponent(filter_name);
    }

    url = "{{ route('lang.admin.localization.roads.autocomplete') }}?" + url;

    $.ajax({
      url: url,
      dataType: 'json',
      success: function(json) {
        response(json);
      }
    });
  },
  'select': function(item,ui) {

    $("#input-shipping_city_id").val(item.city_id);
    $("#input-shipping_road").val(item.name);
  }
});

//addAddrPartName
$('.addAddrPartName button').on('click', function(){
  var addAddrPartName = $('#input-shipping_address1').val();
  var buttonText = $(this).text();
  var newText = addAddrPartName+buttonText;
  $('#input-shipping_address1').val(newText);
});

function clearShippingAddress1(){
  $("#input-shipping_road").val('');
  $("#input-shipping_lane").val('');
  $("#input-shipping_alley").val('');
  $("#input-shipping_no").val('');
  $("#input-shipping_floor").val('');
  $("#input-shipping_room").val('');
}

function clearCustomer(){
  $('#input-personal_name').val('');
  $('#input-customer_id').val('');
  $('#input-customer').val('');
  $('#input-telephone').val('');
  $('#input-mobile').val('');
  $('#input-email').val('');
  $('#input-payment_company').val('');
  $('#input-payment_company').prop('readonly', false);
  $('#input-payment_tin').val('');
  $('#input-shipping_state_id').val('');
  $('#input-shipping_city_id').val('');
  $('#input-payment_department').val('');
  $('#input-shipping_company').val('');
  $('#input-shipping_personal_name').val('');
  $('#input-shipping_phone').val('');
  $('#input-salutation_id').val('');

  clearShippingAddress1();
}

//同訂購人
$("#input-same_order_customer").on('click',function(){
    if($(this).is(':checked')){
        personal_name = $("#input-personal_name").val();
        mobile = $("#input-mobile").val();
        $("#input-shipping_personal_name").val(personal_name);
        $("#input-shipping_phone").val(mobile);
    }else{
        $("#input-shipping_personal_name").val('');
        $("#input-shipping_phone").val('');
    }
});

//同訂購公司
$("#input-same_as_order_company").on('click',function(){
    if($(this).is(':checked')){
        company = $("#input-payment_company").val();
        $("#input-shipping_company").val(company);
    }else{
        $("#input-shipping_company").val('');
    }
});

//預計付款日
$("#input-scheduled_payment_date").on('change',function(){
  var scheduled_date = $(this).val();
  if(scheduled_date.length != 0){
    $('#input-payment_method-debt').prop('checked', true);
  }else{
    $('#input-payment_method-debt').prop('checked', false);
  }
});


$("#input-payment_paid").on('input ',function(){
  calcPayment()
});

function calcPayment(){
  payment_total = $('#input-payment_total').val().toNum();
  payment_paid = $('#input-payment_paid').val().toNum();
  payment_unpaid = parseFloat(payment_total) - parseFloat(payment_paid)
  $('#input-payment_unpaid').val(payment_unpaid);
}

// 商品
var product_row = {{ $product_row }};

// 新增商品
function addProduct(){
  
  $.ajax({
    type:'get',
    dataType: 'html',
    url: "{{ route('lang.admin.sale.orders.getProductHtml') }}?product_row="+product_row,
    success:function(response){
      $('#tbody_order_products').append(response);
    }
  });
  product_row++;
}

// 刪除商品
function removeProduct(product_row){
  var trProductRow = $('#product-row-'+product_row);
  var strId = trProductRow.attr('id');
  trProductRow.find('input').remove();
  trProductRow.find('textarea').remove();
  trProductRow.remove();

  calcTotal();
}

// 獲取商品詳細內容
function getProductDetails(selectedProduct){
  var jqObj = $(selectedProduct);
  var this_product_row = jqObj.closest('tr').data("product-row");
  var product_id = jqObj.val();

  $.ajax({
    type:'get',
    dataType: 'html',
    url: "{{ route('lang.admin.sale.orders.getProductDetailsHtml') }}?filter_product_id=" + encodeURIComponent(product_id) + '&product_row='+this_product_row,
    success:function(response){
      var is_required = '';

      //options
      $('#product-row-'+this_product_row+'-content-table').remove();
      $('#product-row-'+this_product_row+'-options').append(response);

      //product
      var main_category_code = $('#product-row-'+this_product_row+'-hidden_main_category_code').val();
      var model = $('#product-row-'+this_product_row+'-hidden_model').val();
      var price = $('#product-row-'+this_product_row+'-hidden_price').val().toNum();
      var sort_order = $('#product-row-'+this_product_row+'-hidden_sort_order').val().toNum();

      $('#input-product-'+this_product_row+'-main_category_code').val(main_category_code);
      $('#input-product-'+this_product_row+'-model').val(model);
      $('#input-product-'+this_product_row+'-price').val(price);
      $('#input-product-'+this_product_row+'-quantity').val(1);
    }
  });
}

// 設定預設選項數量
function setProductOptionDefault(this_product_row){
  var main_meal_quantity = $('#input-product-'+this_product_row+'-main_meal_quantity').val();
  var main_meal_quantity_no_veg = $('#input-product-'+this_product_row+'-main_meal_quantity_no_veg').val();
  //console.log('funtion setProductOptionDefault: main_meal_quantity='+main_meal_quantity+', main_meal_quantity_no_veg='+main_meal_quantity_no_veg)

  $('#product-row-'+this_product_row).find('input[data-element="options_with_qty"]').each(function() {
    var is_default = $(this).data('is_default');

    //設定預設數量
    if(is_default){
      $(this).val(main_meal_quantity_no_veg);
    }
  });
}

// 觸發-計算商品選項金額
$(document).on("focusout",'input[data-element="options_with_qty"]', function(){
  var this_product_row = $(this).closest("[data-product-row]").data('product-row');
  var options_total = calcProductOptionTotal(this_product_row)
  var total = $('#input-product-'+this_product_row+'-total').val().toNum();
  var final_total = parseFloat(total) + parseFloat(options_total);
  $('#input-product-'+this_product_row+'-final_total').val(final_total);
  });

// 計算商品選項金額
function calcProductOptionTotal(this_product_row){
  var options_total = 0;
  $('#product-row-'+this_product_row).find('input[data-element="options_with_qty"]').each(function() {
    var option_qty = $(this).val().toNum();
    var option_price = $(this).data('option-price');
    var option_value = $(this).data('option-value');

    options_total += parseFloat(option_qty) * parseFloat(option_price);
  });

  $('#input-product-'+this_product_row+'-options_total').val(options_total);

  return options_total;
}

//計算商品金額
function calcProductTotal(this_product_row){
  var quantity = $('#input-product-'+this_product_row+'-quantity').val().toNum();
  var price = $('#input-product-'+this_product_row+'-price').val().toNum();
  var total = parseFloat(quantity) * parseFloat(price);

  //console.log('function calcProductTotal: this_product_row='+this_product_row+', quantity='+quantity+', price='+price+', total='+total)
  return total;
}

//計算商品主餐數量
// -- 頁面初始化時先計算當前主餐數量
for (let this_product_row = 0; this_product_row < product_row; this_product_row++) {
  calcProductMainMeal(this_product_row)
}

function calcProductMainMeal(this_product_row){
  var quantity = $('#input-product-'+this_product_row+'-quantity').val();
  var main_meal_quantity = 0;
  var main_meal_quantity_veg = 0;
  var main_meal_quantity_no_veg = 0;
  var unassigned_qty = 0;

  $('#product-row-'+this_product_row).find('.input_main_meal').each(function() {
    var ovid = $(this).data('ovid');
    var qty = $(this).val();
    if($.isNumeric(qty) && qty > 0){
      main_meal_quantity += parseInt(qty);
    }

    if(ovid == '1046' || ovid == '1047'){
      main_meal_quantity_veg += parseInt(qty);
    }
  });

  main_meal_quantity_no_veg = parseInt(main_meal_quantity) - parseInt(main_meal_quantity_veg);
  //console.log('function calcProductMainMeal: main_meal_quantity_no_veg='+main_meal_quantity_no_veg+', main_meal_quantity='+main_meal_quantity+', main_meal_quantity_veg='+main_meal_quantity_veg)

  unassigned_qty = quantity - main_meal_quantity;

  //$('#input-product-'+this_product_row+'-main_meal_quantity').val(main_meal_quantity);
  $('#input-product-'+this_product_row+'-main_meal_quantity').val(main_meal_quantity);
  $('#input-product-'+this_product_row+'-main_meal_quantity_no_veg').val(main_meal_quantity_no_veg);
  $('#input-product-'+this_product_row+'-unassigned_qty').val(unassigned_qty);
  
  
  $('#span-product-'+this_product_row+'-burrito_total').text(main_meal_quantity);
  return main_meal_quantity;
}

//觸發-計算某商品的飲料量數
$(document).on("focusout",'input.drink', function(){
  var this_product_row = $(this).closest("[data-product-row]").data('product-row');
  calcProductDrink(this_product_row);
});

//計算當前飲料數量
for (let i = 1; i < product_row; i++) {
  calcProductDrink(i);
}

//計算某商品的飲料量數
function calcProductDrink(this_product_row){
  
  // 找到 <tr> 元素
  const trElement = document.querySelector('#product-row-'+this_product_row);

  // 使用 querySelectorAll 找到所有帶有 class="drink" 的 <input> 元素
  const drinkInputs = trElement.querySelectorAll('input.drink');

  let total = 0;

  // 迭代每個 <input> 元素，獲取其值並相加
  drinkInputs.forEach((input) => {
    total += parseFloat(input.value);
  });

  $('#span-product-'+this_product_row+'-drink_total').text(total);
}


//變更主餐數量
$(document).on("focusout",'.input_main_meal', function(){
  var this_product_row = $(this).closest("[data-product-row]").data('product-row');
  main_meal_quantity = calcProductMainMeal(this_product_row)
  setProductOptionDefault(this_product_row);
});

//變更商品數量或單價
$(document).on("focusout",'input[data-element="quantity"], input[data-element="price"]', function(){
  var this_product_row = $(this).closest("[data-product-row]").data('product-row');

  quantity = $('#input-product-'+this_product_row+'-quantity').val();
  price = $('#input-product-'+this_product_row+'-price').val();
  total = parseFloat(quantity) * parseFloat(price);
  options_total = $('#input-product-'+this_product_row+'-options_total').val();
  final_total = parseFloat(total) + parseFloat(options_total);

  $('#input-product-'+this_product_row+'-total').val(total);
  $('#input-product-'+this_product_row+'-final_total').val(final_total);
  //console.log('Change product quantity or price: this_product_row='+this_product_row+', total='+total+', options_total='+options_total+', final_total='+final_total)
});

$('#button-refresh').on('click', function () {
  calcTotal()
});

//最終計算
function calcTotal(){
  var product_quantity = 0; //某個商品的數量
  var product_price = 0;
  var product_option_total = 0;
  var product_total = 0;
  var final_product_total = 0;

  // payment_total 是本檔的全域變數
  var order_sub_total = 0 //sum of all products' price*quantity
      , order_discount = $('#input-total-discount').val().toNum()
      , order_shipping_fee = $('#input-total-shipping_fee').val().toNum()

  for(i=1; i<=product_row; i++){

    product_name = $('#product-row-'+i+'-hidden_name').val();
    if(!product_name){
      continue;
    }

    //選項金額加總
    //product_options_total = $('#input-product-'+i+'-options_total').val();
    //product_options_total = calcProductOptionTotal(i);
    product_options_total = $('#input-product-'+i+'-options_total').val();

    //主餐數量
    product_main_meal_sum = $('#input-product-'+i+'-main_meal_quantity').val().toNum();

    //商品數量
    product_quantity_original = $('#input-product-'+i+'-quantity').val().toNum();
    product_quantity = $('#input-product-'+i+'-quantity').val().toNum();

    // if(product_main_meal_sum != 0 && product_main_meal_sum != product_quantity){
    //   alert(product_name + ': 主餐加總='+product_main_meal_sum+', 商品數量='+product_quantity+', 兩者應相等')
    //   return false;
    // }

    //商品數量
    product_quantity = product_main_meal_sum;
    if(product_quantity == 0){
      product_quantity = 1;
    }

    $('#input-product-'+i+'-quantity').val(product_quantity_original);


    //商品單價
    product_price = $('#input-product-'+i+'-price').val().toNum();

    //商品小計
    product_total = parseFloat(product_quantity_original) * parseFloat(product_price)
    $('#input-product-'+i+'-total').val(product_total);

    //最終商品小計 = 商品小計 + 選項金額
    final_total = parseFloat(product_total) + parseFloat(product_options_total)
    $('#input-product-'+i+'-final_total').val(final_total);

    //商品合計
    order_sub_total += final_total;

  }

  //console.log('button-refresh: product_option_total='+product_option_total+', product_quantity='+product_quantity+', product_price='+product_price+', product_total='+product_total+', final_product_total='+final_product_total)

  //右下方訂單各項金額
  // $('input[data-element="order_product_final_total"]').each( function(){
  //   var total = $(this).val().toNum();
  //   order_sub_total += parseInt(total)
  // });

  if(!$.isNumeric(order_discount)){
    order_discount = 0;
  }

  if(!$.isNumeric(order_shipping_fee)){
    order_shipping_fee = 0;
  }

  payment_total = parseFloat(order_sub_total) - parseFloat(order_discount) + parseFloat(order_shipping_fee);

  $('#input-total-sub_total').val(order_sub_total);
  $('#input-total-total').val(payment_total);
  $('#input-payment_total').val(payment_total);
  calcPayment()
}

</script>
@endsection
