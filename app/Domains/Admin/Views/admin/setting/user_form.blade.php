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
        {{-- <button type="submit" form="form-user" data-bs-toggle="tooltip" title="Save" class="btn btn-primary"><i class="fas fa-save"></i></button> --}}
        <button type="submit" form="form-user" data-bs-toggle="tooltip" title="{{ $lang->save }}" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="{{ $back_url }}" data-bs-toggle="tooltip" title="Back" class="btn btn-light"><i class="fas fa-reply"></i></a>
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
          <form id="form-user" action="{{ $save_url }}" method="post" data-oc-toggle="ajax">
            @csrf
            @method('POST')
            <div class="tab-content">
              <div id="tab-general" class="tab-pane active">

                  <fieldset>
                    <legend>{{ $lang->trans('text_user_details') }}</legend>

                    <div class="row mb-3 required">
                      <label for="input-username" class="col-sm-2 col-form-label">{{ $lang->column_username }}</label>
                      <div class="col-sm-10">
                        <input type="text" id="input-username" name="username" value="{{ $user->username }}" class="form-control"/>
                        <div id="error-username" class="invalid-feedback"></div>
                      </div>
                    </div>

                    <div class="row mb-3 required">
                      <label for="input-name" class="col-sm-2 col-form-label">{{ $lang->column_name }}</label>
                      <div class="col-sm-10">
                        <input type="text" id="input-name" name="name" value="{{ $user->name }}" class="form-control"/>
                        <div id="error-name" class="invalid-feedback"></div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="input-telephone" class="col-sm-2 col-form-label">{{ $lang->column_telephone }}</label>
                      <div class="col-sm-10">
                        <div class="input-group">
                          <input type="text" id="input-telephone_prefix" name="telephone_prefix" value="{{ $user->telephone_prefix }}" placeholder="區碼" class="form-control" style="width:30px;"/>
                          <input type="text" id="input-telephone" name="telephone" value="{{ $user->telephone }}" placeholder="{{ $lang->column_telephone }}"class="form-control" style="width:100px;"/>
                          <div id="error-telephone" class="invalid-feedback"></div>
                        </div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="input-mobile" class="col-sm-2 col-form-label">{{ $lang->column_mobile }}</label>
                      <div class="col-sm-10">
                        <input type="text" name="mobile" value="{{ $user->mobile }}" id="input-mobile" class="form-control"/>
                        <div id="error-mobile" class="invalid-feedback"></div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="input-email" class="col-sm-2 col-form-label">{{ $lang->column_email }}</label>
                      <div class="col-sm-10">
                        <input type="text" id="input-email" name="email" value="{{ $user->email }}" class="form-control"/>
                        <div id="error-email" class="invalid-feedback"></div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="input-is_admin" class="col-sm-2 col-form-label">{{ $lang->column_is_admin }}</label>
                      <div class="col-sm-10">
                        <div class="form-check form-switch form-switch-lg">
                          <input type="hidden" name="is_admin" value="0"/>
                          <input type="checkbox" id="input-is_admin" name="is_admin" value="1" class="form-check-input" @if($user->is_admin) checked @endif/>
                        </div>
                      </div>
                    </div>
                    
                    <legend>密碼</legend>
                    <div class="row mb-3">
                      <label for="input-payment_company" class="col-sm-2 col-form-label">{{ $lang->column_password }}</label>
                      <div class="col-sm-10">
                        <input type="text" name="password" value="" id="input-password" class="form-control"/>
                        <div id="error-password" class="invalid-feedback"></div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="input-password_confirmation" class="col-sm-2 col-form-label">{{ $lang->column_password_confirm }}</label>
                      <div class="col-sm-10">
                        <input type="text" name="password_confirmation" value="{{ $user->password_confirmation }}" id="input-password_confirmation" class="form-control"/>
                        <div id="error-password_confirmation" class="invalid-feedback"></div>
                      </div>
                    </div>
                    
                    <legend>異動時間</legend>
                    <div class="row mb-3">
                      <label for="input-updated_date" class="col-sm-2 col-form-label">{{ $lang->column_updated_time}}</label>
                      <div class="col-sm-10">
                        <input type="text" value="{{ $user->updated_at }}" class="form-control" disabled/>
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label for="input-created_date" class="col-sm-2 col-form-label">{{ $lang->column_created_time}}</label>
                      <div class="col-sm-10">
                        <input type="text" value="{{ $user->created_at }}" class="form-control" disabled/>
                      </div>
                    </div>
                                  </fieldset>

                  <input type="hidden" name="user_id" value="{{ $user_id }}" id="input-user_id"/>
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
