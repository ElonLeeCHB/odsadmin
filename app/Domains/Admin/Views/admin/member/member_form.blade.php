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
          {{-- <a href="javascript:void(0)" data-bs-toggle="tooltip" title="Orders" class="btn btn-warning"><i class="fas fa-receipt"></i></a> --}}
        {{-- <button type="submit" form="form-member" data-bs-toggle="tooltip" title="Save" class="btn btn-primary"><i class="fas fa-save"></i></button> --}}
        <button type="submit" form="form-member" data-bs-toggle="tooltip" title="{{ $lang->save }}" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="{{ $back }}" data-bs-toggle="tooltip" title="Back" class="btn btn-light"><i class="fas fa-reply"></i></a>
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
          <form id="form-member" action="{{ $save }}" method="post" data-oc-toggle="ajax">
            @csrf
            @method('POST')
            <div class="tab-content">
              <div id="tab-general" class="tab-pane active">

                  <fieldset>
                    <legend>{{ $lang->trans('text_member_details') }}</legend>

                    <div class="row mb-3 required">
                      <label for="input-name" class="col-sm-2 col-form-label">{{ $lang->entry_name }}</label>
                      <div class="col-sm-10">
                        <input type="text" name="name" value="{{ $member->name }}" placeholder="{{ $lang->entry_name }}" id="input-name" class="form-control"/>
                        <div id="error-name" class="invalid-feedback"></div>
                      </div>
                    </div>
                    <div class="row mb-3">
                        <label for="input-salutation_id" class="col-sm-2 col-form-label">{{ $lang->entry_salutation }}</label>
                        <div class="col-sm-10">
                          <select name="salutation_id" id="input-salutation_id" class="form-select">
                            <option value="">--</option>
                            @foreach($salutation['option_values'] as $option_value)
                              <option value="{{ $option_value['option_value_id'] }}" @if($member->salutation_id == $option_value['option_value_id']) selected @endif>{{ $option_value['name']  }}</option>
                            @endforeach
                          </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                      <label for="input-telephone" class="col-sm-2 col-form-label">{{ $lang->entry_telephone }}</label>
                      <div class="col-sm-10">
                        <div class="input-group">
                          <input type="text" name="telephone_prefix" value="{{ $member->telephone_prefix }}" placeholder="區碼" id="input-telephone_prefix"  class="form-control" style="width:30px;"/>
                          <input type="text" name="telephone" value="{{ $member->telephone }}" placeholder="{{ $lang->entry_telephone }}" id="input-telephone" class="form-control" style="width:100px;"/>
                          <div id="error-telephone" class="invalid-feedback"></div>
                        </div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="input-mobile" class="col-sm-2 col-form-label">{{ $lang->entry_mobile }}</label>
                      <div class="col-sm-10">
                        <input type="text" name="mobile" value="{{ $member->mobile }}" placeholder="{{ $lang->entry_mobile }}" id="input-mobile" class="form-control"/>
                        <div id="error-mobile" class="invalid-feedback"></div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="input-email" class="col-sm-2 col-form-label">{{ $lang->entry_email }}</label>
                      <div class="col-sm-10">
                        <input type="text" name="email" value="{{ $member->email }}" placeholder="E-Mail" id="input-email" class="form-control"/>
                        <div id="error-email" class="invalid-feedback"></div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="input-comment" class="col-sm-2 col-form-label">內部備註</label>
                      <div class="col-sm-10">
                        <input type="text" name="comment" value="{{ $member->comment }}" id="input-comment" class="form-control"/>
                        <div id="error-comment" class="invalid-feedback"></div>
                      </div>
                    </div>
                    
                    <legend>訂購資料</legend>
                    <div class="row mb-3">
                      <label for="input-payment_company" class="col-sm-2 col-form-label">{{ $lang->entry_payment_company }}</label>
                      <div class="col-sm-10">
                        <input type="text" name="payment_company" value="{{ $member->payment_company }}" placeholder="訂購公司" id="input-payment_company" class="form-control"/>
                        <div id="error-payment_company" class="invalid-feedback"></div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="input-payment_department" class="col-sm-2 col-form-label">{{ $lang->entry_payment_department }}</label>
                      <div class="col-sm-10">
                        <input type="text" name="payment_department" value="{{ $member->payment_department }}" placeholder="訂購部門" id="input-payment_department" class="form-control"/>
                        <div id="error-payment_department" class="invalid-feedback"></div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="input-payment_tin" class="col-sm-2 col-form-label">統一編號</label>
                      <div class="col-sm-10">
                        <input type="text" name="payment_tin" value="{{ $member->payment_tin }}" placeholder="統一編號" id="input-payment_tin" class="form-control"/>
                        <div id="error-payment_tin" class="invalid-feedback"></div>
                      </div>
                    </div>

                    <legend>收件資料</legend>
                    <div class="row mb-3">
                      <label for="input-shipping_personal_name" class="col-sm-2 col-form-label">{{ $lang->entry_shipping_personal_name }}</label>
                      <div class="col-sm-10">
                        <input type="text" name="shipping_personal_name" value="{{ $member->shipping_personal_name }}" placeholder="{{ $lang->entry_shipping_personal_name }}" id="input-shipping_personal_name" class="form-control"/>
                        <div id="error-shipping_personal_name" class="invalid-feedback"></div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="input-shipping_company" class="col-sm-2 col-form-label">{{ $lang->entry_shipping_company }}</label>
                      <div class="col-sm-10">
                        <input type="text" name="shipping_company" value="{{ $member->shipping_company }}" placeholder="{{ $lang->entry_shipping_company }}" id="input-shipping_company" class="form-control"/>
                        <div id="error-shipping_company" class="invalid-feedback"></div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="input-shipping_phone" class="col-sm-2 col-form-label">{{ $lang->entry_shipping_phone }}</label>
                      <div class="col-sm-10">
                        <input type="text" name="shipping_phone" value="{{ $member->shipping_phone }}" placeholder="{{ $lang->entry_shipping_phone }}" id="input-shipping_phone" class="form-control"/>
                        <div id="error-shipping_phone" class="invalid-feedback"></div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="input-length" class="col-sm-2 col-form-label">{{ $lang->entry_shipping_address }}</label>
                      <div class="col-sm-10">
                        <div class="input-group">
                          <div class="col-sm-2">
                            <select id="input-shipping_state_id" name="shipping_state_id" class="form-select">
                              <option value="">--</option>
                              @foreach($states as $state)
                              <option value="{{ $state->id }}" @if($state->id == $member->shipping_state_id) selected @endif>{{ $state->name }}</option>
                              @endforeach
                            </select>
                          </div>&nbsp;
                          <div class="col-sm-2">
                            <select id="input-shipping_city_id" name="shipping_city_id" class="form-select">
                              <option value="">--</option>
                              @foreach($shipping_cities as $city)
                              <option value="{{ $city->id }}" @if($city->id == $member->shipping_city_id) selected @endif>{{ $city->name }}</option>
                              @endforeach
                            </select>
                          </div>&nbsp;
                          <div class="col-sm-2">
                            <input type="text" name="shipping_road" value="{{ $member->shipping_road  }}" id="input-shipping_road" data-oc-target="autocomplete-shipping_road" class="form-control"/>
                            <ul id="autocomplete-shipping_road" class="dropdown-menu"></ul>
                            <div id="error-shipping_road" class="invalid-feedback"></div>
                          </div>&nbsp;
                          <div class="col-sm-4">
                            <input type="text" id="input-shipping_address1" name="shipping_address1" value="{{ $member->shipping_address1 }}" class="form-control">
                          </div>
                        </div>
                        <div class="input-group addAddrPartName">
                          <button type="button">巷</button> <button type="button">弄</button> <button type="button">衖</button> <button type="button">號</button>
                          <button type="button">棟</button> <button type="button">大樓</button> <button type="button">樓</button> <button type="button">房</button>
                          <button type="button">室</button>
                        </div>
                      </div>
                    </div>
                    
                    <!--{{--
                    <div class="row mb-3">
                      <label for="input-job_title" class="col-sm-2 col-form-label">{{ $lang->entry_job_title }}</label>
                      <div class="col-sm-10">
                        <input type="text" name="job_title" value="{{ $member->job_title }}" placeholder="{{ $lang->entry_job_title }}" id="input-job_title" class="form-control"/>
                        <div id="error-job_title" class="invalid-feedback"></div>
                      </div>
                    </div>
                    --}}-->


                    <legend>異動時間</legend>
                    <div class="row mb-3">
                      <label for="input-updated_date" class="col-sm-2 col-form-label">{{ $lang->entry_updated_date}}</label>
                      <div class="col-sm-10">
                        <input type="text" name="updated_date" value="{{ $member->updated_at }}" id="input-updated_date" class="form-control" disabled/>
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label for="input-created_date" class="col-sm-2 col-form-label">{{ $lang->entry_created_date}}</label>
                      <div class="col-sm-10">
                        <input type="text" name="created_date" value="{{ $member->created_at }}" id="input-created_date" class="form-control" disabled/>
                      </div>
                    </div>
                                  </fieldset>

                  <input type="hidden" name="member_id" value="{{ $member_id }}" id="input-member_id"/>
              </div>
            </form>
            </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('buttom')
<script type="text/javascript"><!--
//選縣市查區
$('#input-shipping_state_id').on('change', function(){
  var state_id = $(this).val();
  if(state_id){
    $.ajax({
      type:'get',
      url: "{{ route('lang.admin.localization.divisions.getJsonCities') }}?filter_parent_id=" + state_id,
      success:function(data){
        //console.log(JSON.stringify(data))
        html = '<option value=""> -- </option>';
        
        $.each(data, function(i, item) {
          html += '<option value="'+item.city_id+'">'+item.name+'</option>';
        });

        $('#input-shipping_city_id').html(html);
        
        $('#input-shipping_road').val('');

      }
    }); 
  }else{
    $('#input-shipping_city_id').html('<option value="">--</option>');
  }  
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

$(function(){
  //addAddrPartName
  $('.addAddrPartName button').on('click', function(){
    var addAddrPartName = $('#input-shipping_address1').val();
    var buttonText = $(this).text();
    var newText = addAddrPartName+buttonText;
    $('#input-shipping_address1').val(newText);
  });
});



--></script>
@endsection
