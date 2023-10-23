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
        <button type="submit" form="form-supplier" data-bs-toggle="tooltip" title="{{ $lang->button_save }}" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="{{ $back_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_back }}" class="btn btn-light"><i class="fa-solid fa-reply"></i></a>
      </div>
      <h1>{{ $lang->heading_title }}</h1>
      @include('admin.common.breadcumb')
    </div>
  </div>
  <div class="container-fluid">
    <div class="card">
      <?php /*<div class="card-header"><i class="fa-solid fa-pencil"></i> {{ $lang->text_form }}</div>*/ ?>
      <div class="card-body">
        <form id="form-supplier" action="{{ $save_url }}" method="post" data-oc-toggle="ajax">
            @csrf
            @method('POST')
          <ul class="nav nav-tabs">
            <li class="nav-item"><a href="#tab-data" data-bs-toggle="tab" class="nav-link active"> {{ $lang->tab_data }}</a></li>
          </ul>
          <div class="tab-content">
            <div id="tab-data" class="tab-pane active">

              <div class="row mb-3">
                <label for="input-code" class="col-sm-2 col-form-label">{{ $lang->column_code }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-code" name="code" value="{{ $supplier->code }}" class="form-control">
                  <div id="error-code" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3 required">
                <label for="input-name" class="col-sm-2 col-form-label">{{ $lang->column_name }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-name" name="name" value="{{ $supplier->name }}" class="form-control">
                  <div id="error-name" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3 required">
                <label for="input-short_name" class="col-sm-2 col-form-label">{{ $lang->column_short_name }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-short_name" name="short_name" value="{{ $supplier->short_name }}" class="form-control">
                  <div id="error-short_name" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-tax_id_num" class="col-sm-2 col-form-label">{{ $lang->column_tax_id_num }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-tax_id_num" name="tax_id_num" value="{{ $supplier->tax_id_num }}" class="form-control">
                  <div id="error-tax_id_num" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-telephone" class="col-sm-2 col-form-label">{{ $lang->column_telephone }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-telephone" name="telephone" value="{{ $supplier->telephone }}" class="form-control">
                  <div id="error-telephone" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-fax" class="col-sm-2 col-form-label">{{ $lang->column_fax }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-fax" name="fax" value="{{ $supplier->fax }}" class="form-control">
                  <div id="error-fax" class="invalid-feedback"></div>
                </div>
              </div>
                
              <div class="row mb-3">
                <label for="input-length" class="col-sm-2 col-form-label">{{ $lang->column_address }}</label>
                <div class="col-sm-10">
                  <div class="input-group">
                    <div class="col-sm-2">
                      <select id="input-shipping_state_id" name="shipping_state_id" class="form-select">
                        <option value="">--</option>
                        @foreach($states as $state)
                        <option value="{{ $state->id }}" @if($state->id == $supplier->shipping_state_id) selected @endif>{{ $state->name }}</option>
                        @endforeach
                      </select>
                    </div>&nbsp;
                    <div class="col-sm-2">
                      <select id="input-shipping_city_id" name="shipping_city_id" class="form-select">
                        <option value="">--</option>
                        @foreach($shipping_cities as $city)
                        <option value="{{ $city->id }}" @if($city->id == $supplier->shipping_city_id) selected @endif>{{ $city->name }}</option>
                        @endforeach
                      </select>
                    </div>&nbsp;
                    <div class="col-sm-4">
                      <input type="text" id="input-shipping_address1" name="shipping_address1" value="{{ $supplier->shipping_address1 }}" class="form-control">
                    </div>
                  </div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-www" class="col-sm-2 col-form-label">{{ $lang->column_www }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-www" name="www" value="{{ $supplier->www }}" class="form-control">
                  <div id="error-www" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-line_at" class="col-sm-2 col-form-label">{{ $lang->column_line_at }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-line_at" name="line_at" value="{{ $supplier->line_at }}" class="form-control">
                  <div id="error-line_at" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-supplier_contact_name" class="col-sm-2 col-form-label">{{ $lang->column_supplier_contact_name }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-supplier_contact_name" name="supplier_contact_name" value="{{ $supplier->supplier_contact_name }}" class="form-control">
                  <div id="error-supplier_contact_name" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-supplier_contact_jobtitle" class="col-sm-2 col-form-label">{{ $lang->column_supplier_contact_jobtitle }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-supplier_contact_jobtitle" name="meta_data_supplier_contact_jobtitle" value="{{ $supplier->supplier_contact_jobtitle }}" class="form-control">
                  <div id="error-supplier_contact_jobtitle" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-supplier_contact_email" class="col-sm-2 col-form-label">{{ $lang->column_supplier_contact_email }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-supplier_contact_email" name="supplier_contact_email" value="{{ $supplier->supplier_contact_email}}" class="form-control">
                  <div id="error-supplier_contact_email" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-supplier_contact_telephone" class="col-sm-2 col-form-label">{{ $lang->column_supplier_contact_telephone }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-supplier_contact_telephone" name="supplier_contact_telephone" value="{{ $supplier->supplier_contact_telephone }}" class="form-control">
                  <div id="error-supplier_contact_telephone" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-supplier_contact_mobile" class="col-sm-2 col-form-label">{{ $lang->column_supplier_contact_mobile }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-supplier_contact_mobile" name="supplier_contact_mobile" value="{{ $supplier->supplier_contact_mobile }}" class="form-control">
                  <div id="error-supplier_contact_mobile" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label class="col-sm-2 col-form-label">{{ $lang->column_bank }}</label>
                <div class="col-sm-10">
                  <div class="input-group">
                    <div class="col-sm-1"><input type="text" id="input-supplier_bank_code" name="supplier_bank_code" value="{{ $supplier->supplier_bank_code }}" placeholder="銀行代碼" class="form-control" readonly=""/><div class="form-text">銀行代碼</div></div>
                    <div class="col-sm-3"><input type="text" id="input-supplier_bank_name" name="supplier_bank_name" value="{{ $supplier->supplier_bank_name }}" placeholder="{{ $lang->column_supplier_bank_name }}" class="form-control" data-oc-target="autocomplete-supplier_bank_name"/><div class="form-text">銀行名稱(可查詢，至少輸入一個字)</div></div>
                    <div class="col-sm-2"><input type="text" id="input-supplier_bank_account" name="supplier_bank_account" value="{{ $supplier->supplier_bank_account }}" placeholder="{{ $lang->column_supplier_bank_account }}" class="form-control" ><div class="form-text">銀行帳號</div></div>
                    <div id="error-supplier_bank_code" class="invalid-feedback"></div>
                    <ul id="autocomplete-supplier_bank_name" class="dropdown-menu"></ul>
                  </div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-payment_term" class="col-sm-2 col-form-label">{{ $lang->column_payment_term }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-payment_term" name="payment_term" value="{{ $supplier->payment_term_name }}" class="form-control" data-oc-target="autocomplete-payment_term" >
                  <input type="hidden" id="input-payment_term_id" name="payment_term_id" value="{{ $supplier->payment_term_id }}" />
                  <ul id="autocomplete-payment_term" class="dropdown-menu"></ul>
                  <div id="error-payment_term" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3 required">
                <label for="input-tax_type_code" class="col-sm-2 col-form-label">{{ $lang->column_tax_type }}</label>
                <div class="col-sm-10">
                  <select id="input-tax_type_code" name="tax_type_code" class="form-control" >
                    <option value="">{{ $lang->text_select }}</option>
                    @foreach($tax_types as $tax_type)
                    <option value="{{ $tax_type->code }}" @if($tax_type->code == $supplier->tax_type_code) selected @endif>{{ $tax_type->name }}</option>
                    @endforeach
                  </select>
                  <div id="error-tax_type" class="invalid-feedback"></div>
                </div>
              </div>
              
              <div class="row mb-3">
                <label class="col-sm-2 col-form-label">{{ $lang->column_is_often_used_supplier }}</label>
                <div class="col-sm-10">
                  <div class="input-group">
                    <div id="input-is_often_used_supplier" class="form-check form-switch form-switch-lg">
                      <input type="hidden" name="is_often_used_supplier" value="0"/>
                      <input type="checkbox" name="is_often_used_supplier" value="1" class="form-check-input" @if($supplier->is_often_used_supplier) checked @endif/>
                    </div>
                  </div>
                </div>
              </div>  

              <div class="row mb-3">
                <label class="col-sm-2 col-form-label">{{ $lang->column_enable }}</label>
                <div class="col-sm-10">
                  <div class="input-group">
                    <div id="input-is_active" class="form-check form-switch form-switch-lg">
                      <input type="hidden" name="is_active" value="0"/>
                      <input type="checkbox" name="is_active" value="1" class="form-check-input" @if($supplier->is_active) checked @endif/>
                    </div>
                  </div>
                </div>
              </div>              

              <div class="row mb-3">
                <label for="input-comment" class="col-sm-2 col-form-label">{{ $lang->column_comment }}</label>
                <div class="col-sm-10">
                  <textarea id="input-comment" name="comment" class="form-control" rows="5">{{ $supplier->comment }}</textarea>
                  <div id="error-comment" class="invalid-feedback"></div>
                </div>
              </div>

            </div>
          </div>
          <input type="hidden" id="input-supplier_id" name="supplier_id" value="{{ $supplier_id }}"></form>
      </div>
    </div>
  </div>
</div>

@endsection

@section('buttom')
<script type="text/javascript">

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

$('#input-supplier_bank_name').autocomplete({
  'source': function (request, response) {
    $.ajax({
      url: "{{ route('lang.admin.counterparty.suppliers.autocomplete') }}?filter_name=" + encodeURIComponent(request),
      dataType: 'json',
      success: function (json) {
        json.unshift({
          manufacturer_id: 0,
          name: ' --- None --- '
        });

        response($.map(json, function (item) {
          return {
            label: item['name'],
            value: item['supplier_id']
          }
        }));
      }
    });
  },
  'select': function (item) {
    $('#input-supplier_bank_name').val(item['label']);
    $('#input-supplier_bank_code').val(item['value']);
  }
});

// Parent
$('#input-parent_name').autocomplete({
  'source': function (request, response) {
    $.ajax({
      url: "{{ route('lang.admin.counterparty.suppliers.autocomplete') }}?filter_name=" + encodeURIComponent(request),
      dataType: 'json',
      success: function (json) {
        json.unshift({
          manufacturer_id: 0,
          name: ' --- None --- '
        });

        response($.map(json, function (item) {
          return {
            label: item['name'],
            value: item['supplier_id']
          }
        }));
      }
    });
  },
  'select': function (item) {
    $('#input-parent_name').val(item['label']);
    $('#input-parent_id').val(item['value']);
  }
});

// Payment Term
$('#input-payment_term').autocomplete({
  'source': function (request, response) {
      $.ajax({
        url: "{{ $payment_term_autocomplete_url }}?filter_name=" + encodeURIComponent(request),
        dataType: 'json',
        success: function (json) {
          response(json);
        }
      });
  },
  'select': function (item) {
    $('#input-payment_term_id').val(item['value']);
    $('#input-payment_term').val(item['name']);
  }
});

</script>
@endsection