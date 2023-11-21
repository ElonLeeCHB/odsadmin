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
        
        @if(!empty($calc_url))
        <a data-href="{{ $printForm }}" id="href-printForm"  target="_blank" data-bs-toggle="tooltip" title="列印" class="btn btn-info"><i class="fa-solid fa-print"></i></a>
        @endif
        <a href="{{ $back_url }}" data-bs-toggle="tooltip" title="Back" class="btn btn-light"><i class="fas fa-reply"></i></a>

      </div>
      <h1>{{ $lang->heading_title }}</h1>
      @include('admin.common.breadcumb')
    </div>
  </div>
    <div class="container-fluid">
      <div class="card">
        <div class="card-body">
          <ul class="nav nav-tabs">
              <li class="nav-item"><a href="#tab-general" data-bs-toggle="tab" class="nav-link active">{{ $lang->tab_general }}</a></li>
              <!--<li class="nav-item"><a href="#tab-address" data-bs-toggle="tab" class="nav-link">{{ $lang->tab_address }}</a></li>-->
          </ul>
          <form id="form-mrequisition" action="{{ $save_url }}" method="post" data-oc-toggle="ajax">
            @csrf
            @method('POST')
            <div class="tab-content">
              <div id="tab-general" class="tab-pane active">

                  <fieldset>

                    <div class="row mb-3">
                      <label for="input-required_date" class="col-sm-2 col-form-label">{{ $lang->column_required_date }}</label>
                      <div class="col-sm-10">
                        <div class="input-group">
                          <input type="text" id="input-required_date" name="required_date" value="{{ $required_date }}" placeholder="{{ $lang->column_required_date }}" class="form-control date"/>
                          <div class="input-group-text"><i class="fa-regular fa-calendar"></i></div>
                          <button type="button" id="btn-redirectToRequiredDate" class="btn btn-primary btn-sm float-end" data-bs-toggle="tooltip" title="查詢" >查詢</button>
                          <button type="button" id="getDemandSource" class="btn btn-primary btn-sm float-end" data-bs-toggle="tooltip" title="重抓需求來源" onclick="calcOrders();">更新</button>
                        </div>
                        <div id="error-required_date" class="invalid-feedback"></div>
                      </div>
                    </div>
                    
<style>
#tableContainer {
  max-height: 700px; /* 设置表格容器的最大高度 */
  overflow-y: auto; /* 启用垂直滚动条 */
  position: relative; /* 确保相对定位 */
}

#tableContainer thead {
  background-color: #f2f2f2; /* 可选：设置表头的背景颜色 */
  position: sticky;
  top: 0; /* 表头初始位置在顶部 */
  z-index: 1; /* 使表头在上方 */
}
</style>

                    <div class="table-responsive text-end mx-auto" id="tableContainer">
                      <table class="table table-bordered table-hover mx-auto">

                        <thead>
                          <tr>
                          <td class="text-start"> </td>
                            <td class="text-start">時間</td>
                            <td class="text-start">訂單編號<BR>(末4碼)</td>
                            <td class="text-end">地址簡稱</td>
                            @foreach($sales_ingredients_table_items as $name)
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
                            <td colspan="4">全日統計</td>
                            @foreach($sales_ingredients_table_items as $saleable_product_material_id => $saleable_product_material_name)
                            <td>
                              @if(!empty($requisitions['all_day']))
                              @foreach($requisitions['all_day'] as $ingredient_product_id => $record)
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
                            @foreach($sales_ingredients_table_items as $saleable_product_material_id => $saleable_product_material_name)
                            <td>
                              @if(!empty($requisitions['am']))
                              @foreach($requisitions['am'] as $ingredient_product_id => $record)
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
                            @foreach($sales_ingredients_table_items as $saleable_product_material_id => $saleable_product_material_name)
                            <td>
                              @if(!empty($requisitions['pm']))
                              @foreach($requisitions['pm'] as $ingredient_product_id => $record)
                                @if($saleable_product_material_id == $ingredient_product_id)
                                  {{ $record['quantity'] }}
                                @endif
                              @endforeach
                              @endif
                            </td>
                            @endforeach
                          </tr>
                          @if(!empty($requisitions['details']))
                          @foreach($requisitions['details'] as $key => $detail_row)
                          <tr id="option-value-row-0">
                            <td class="text-end">{{ $key+1 }}</td>
                            <td class="text-end">{{ $detail_row['required_date_hi'] ?? '' }}</td>
                            <td class="text-end"><a href="{{ $detail_row['source_id_url'] }}" data-bs-toggle="tooltip" title="訂單連結" target="_blank">{{ $detail_row['order_code'] ?? '' }}</a></td>
                            <td class="text-end">{{ $detail_row['shipping_road_abbr'] }}</td>
                            @foreach($sales_ingredients_table_items as $saleable_product_material_id => $saleable_product_material_name)
                            <td>
                                {{ $detail_row['items'][$saleable_product_material_id]['quantity'] ?? ''}}
                            </td>
                            @endforeach
                          </tr>
                          @endforeach
                          @endif

                          @php
                              $columns = 4 + count($sales_ingredients_table_items);
                            @endphp
                          @for($i=0; $i<20; $i++)
                          <tr>
                            <td colspan="{{ $columns }}">&nbsp;&nbsp;</td>
                          </tr>
                          @endfor
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
    window.location.href = "{{ route('lang.admin.sale.requisitions.form') }}/" + required_date_2ymd;
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
    url: "{{ route('lang.admin.sale.requisitions.calcRequisitionsByDate') }}/"+required_date,
    success:function(response){

      if(response.error){
        alert(response.error)
      }else if(response.required_date_2ymd.length > 0){
        window.location.href = "{{ route('lang.admin.sale.requisitions.form') }}/" + required_date_2ymd;
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
    var url = "{{ route('lang.admin.sale.requisitions.printForm') }}/" + required_date;
    window.open(url);
  });
})

</script>
@endsection
