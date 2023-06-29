@extends('admin.app')

@section('pageJsCss')
@endsection

@section('columnLeft')
  @include('admin.common.column_left')
@endsection

@section('content')
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="float-end">
        @if(!empty($calc))
        <a data-href="{{ $printForm }}" id="href-printForm"  target="_blank" data-bs-toggle="tooltip" title="列印" class="btn btn-info"><i class="fa-solid fa-print"></i></a>
        @endif
        <a href="{{ $back }}" data-bs-toggle="tooltip" title="Back" class="btn btn-light"><i class="fas fa-reply"></i></a>
      </div>
      <h1>{{ $lang->heading_title }}</h1>
      @include('admin.common.breadcumb')
    </div>
  </div>
    <div class="container-fluid">
      <div class="card">
        <div class="card-header"><i class="fas fa-pencil-alt"></i> {{ $lang->text_form }}</div>
        <div class="card-body">
          <ul class="nav nav-tabs">
              <li class="nav-item"><a href="#tab-general" data-bs-toggle="tab" class="nav-link active">{{ $lang->tab_general }}</a></li>
              <!--<li class="nav-item"><a href="#tab-address" data-bs-toggle="tab" class="nav-link">{{ $lang->tab_address }}</a></li>-->
          </ul>
          <form id="form-mrequisition" action="{{ $save }}" method="post" data-oc-toggle="ajax">
            @csrf
            @method('POST')
            <div class="tab-content">
              <div id="tab-general" class="tab-pane active">

                  <fieldset>

				            <legend class="float-none">日期 
                      <button type="button" id="getDemandSource" class="btn btn-primary btn-sm float-end" data-bs-toggle="tooltip" title="重抓需求來源" onclick="calcOrders();"><i class="fa-solid fa-list"></i></button>
                    </legend>

                    <div class="row mb-3">
                      <label for="input-required_date" class="col-sm-2 col-form-label">{{ $lang->column_required_date }}</label>
                      <div class="col-sm-10">
                        <div class="input-group">
                        <input type="date" id="input-required_date" name="required_date" value="{{ $required_date }}" placeholder="{{ $lang->column_required_date }}" class="form-control"/>
                          <button type="button" id="btn-redirectToRequiredDate" class="btn btn-primary btn-sm float-end" data-bs-toggle="tooltip" title="查詢" >查詢</button>
                          <div id="error-required_date" class="invalid-feedback"></div>
                        </div>
                      </div>
                    </div>
                    
                    <div class="table-responsive text-end mx-auto">
                      <table class="table table-bordered table-hover mx-auto">

                        <tbody id="tbody_body_records">
                          <tr>
                            <td colspan="4"></td>
                            @foreach($sales_saleable_product_ingredients as $product_id => $product_name)
                            <td>{{ $product_name }}</td>
                            @endforeach
                          </tr>
                          <tr id="option-value-row-0">
                            <td colspan="4">全日統計</td>
                            @foreach($sales_saleable_product_ingredients as $saleable_product_material_id => $saleable_product_material_name)
                            <td>
                              @if(!empty($mrequisitions['all_day']))
                              @foreach($mrequisitions['all_day'] as $ingredient_product_id => $record)
                                @if($saleable_product_material_id == $ingredient_product_id)
                                  {{ $record['quantity'] }}
                                @endif
                              @endforeach
                              @endif
                            </td>
                            @endforeach
                          </tr>
                          <tr id="option-value-row-0">
                            <td colspan="4">上午統計</td>
                            @foreach($sales_saleable_product_ingredients as $saleable_product_material_id => $saleable_product_material_name)
                            <td>
                              @if(!empty($mrequisitions['am']))
                              @foreach($mrequisitions['am'] as $ingredient_product_id => $record)
                                @if($saleable_product_material_id == $ingredient_product_id)
                                  {{ $record['quantity'] }}
                                @endif
                              @endforeach
                              @endif
                            </td>
                            @endforeach
                          </tr>
                          <tr id="option-value-row-0">
                            <td colspan="4">下午統計</td>
                            @foreach($sales_saleable_product_ingredients as $saleable_product_material_id => $saleable_product_material_name)
                            <td>
                              @if(!empty($mrequisitions['pm']))
                              @foreach($mrequisitions['pm'] as $ingredient_product_id => $record)
                                @if($saleable_product_material_id == $ingredient_product_id)
                                  {{ $record['quantity'] }}
                                @endif
                              @endforeach
                              @endif
                            </td>
                            @endforeach
                          </tr>
                          <tr>
                            <td class="text-start">時間</td>
                            <td class="text-start">訂單編號</td>
                            <td class="text-end">地址簡稱</td>
                            <td class="text-end">商品簡稱</td>
                            @foreach($sales_saleable_product_ingredients as $saleable_product_material_id => $saleable_product_material_name)
                            <td style="width:30px">{{ $saleable_product_material_name }}</td>
                            @endforeach
                          </tr>
                          @if(!empty($mrequisitions['details']))
                          @foreach($mrequisitions['details'] as $details_key => $detail_record)
                          <tr id="option-value-row-0">
                            <td class="text-end">{{ $detail_record['require_date_hi'] }}</td>
                            <td class="text-end">{{ $detail_record['source_idsn'] }}</td>
                            <td class="text-end">{{ $detail_record['shipping_road_abbr'] }}</td>
                            <td>{{ $detail_record['product_name'] }}</td>
                            @foreach($sales_saleable_product_ingredients as $saleable_product_material_id => $saleable_product_material_name)
                            <td>
                                {{ $detail_record['items'][$saleable_product_material_id]['quantity'] ?? ''}}
                            </td>
                            @endforeach
                          </tr>
                          @endforeach
                          @endif
                        </tbody>
                      </table>
                    </div>


                  </fieldset>
              </div>
              <input type="hidden" id="input-location_id" name="location_id" value="0" >
            </form>
            </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('buttom')
<script type="text/javascript">

$("#btn-redirectToRequiredDate").on('click', function(){ 
  var required_date = $('#input-required_date').val();
  var parts = required_date.split('-');
  parts[0] = parts[0].substring(2); // 將年份的前兩位去掉
  var required_date_2ymd = parts.join('');

  if(required_date_2ymd.length > 0){
    window.location.href = "{{ route('lang.admin.sale.mrequisition.form') }}/" + required_date_2ymd;
  }
});

function calcOrders(){
  var required_date = $('#input-required_date').val();
  if(required_date.length==''){
    alert('請選擇需求日期');
    return false;
  }

  var parts = required_date.split('-');
  parts[0] = parts[0].substring(2); // 將年份的前兩位去掉
  var required_date_2ymd = parts.join('');
  
  $.ajax({
    type:'get',
    //dataType: 'json',
    url: "{{ route('lang.admin.sale.mrequisition.calcMrequisitionsByDate') }}/"+required_date,
    success:function(response){
      if(response.required_date_2ymd.length > 0){
        window.location.href = "{{ route('lang.admin.sale.mrequisition.form') }}/" + required_date_2ymd;
      }
    }
  });
}

$(function(){
  //列印按鈕
  $('#href-printForm').on('click',function(e){
    e.preventDefault();
    var currentUrl = window.location.href;
    var required_date = currentUrl.match(/[^\/]*$/); //get last number in url
    var url = "{{ route('lang.admin.sale.mrequisition.printForm') }}/" + required_date;
    window.open(url);
  });
})

</script>
@endsection
