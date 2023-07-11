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
            <li class="nav-item"><a href="#tab-supplier" data-bs-toggle="tab" class="nav-link active"> {{ $lang->tab_general }}</a></li>
          </ul>
          <div class="tab-content">
            <div id="tab-supplier" class="tab-pane active">

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
                <label for="input-meta_data_supplier_contact_name" class="col-sm-2 col-form-label">{{ $lang->column_supplier_contact_name }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-meta_data_supplier_contact_name" name="meta_data_supplier_contact_name" value="{{ $meta_data->supplier_contact_name }}" class="form-control">
                  <div id="error-meta_data_supplier_contact_name" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-meta_data_supplier_contact_jobtitle" class="col-sm-2 col-form-label">{{ $lang->column_supplier_contact_jobtitle }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-meta_data_supplier_contact_jobtitle" name="meta_data_supplier_contact_jobtitle" value="{{ $meta_data->supplier_contact_jobtitle }}" class="form-control">
                  <div id="error-meta_data_supplier_contact_jobtitle" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-meta_data_supplier_contact_email" class="col-sm-2 col-form-label">{{ $lang->column_supplier_contact_email }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-meta_data_supplier_contact_email" name="meta_data_supplier_contact_email" value="{{ $meta_data->supplier_contact_email}}" class="form-control">
                  <div id="error-meta_data_supplier_contact_email" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-meta_data_supplier_contact_telephone" class="col-sm-2 col-form-label">{{ $lang->column_supplier_contact_telephone }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-meta_data_supplier_contact_telephone" name="meta_data_supplier_contact_telephone" value="{{ $meta_data->supplier_contact_telephone }}" class="form-control">
                  <div id="error-meta_data_supplier_contact_telephone" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-meta_data_supplier_contact_mobile" class="col-sm-2 col-form-label">{{ $lang->column_supplier_contact_mobile }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-meta_data_supplier_contact_mobile" name="meta_data_supplier_contact_mobile" value="{{ $meta_data->supplier_contact_mobile }}" class="form-control">
                  <div id="error-meta_data_supplier_contact_mobile" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-meta_data_supplier_bank_name" class="col-sm-2 col-form-label">{{ $lang->column_supplier_bank_name }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-meta_data_supplier_bank_name" name="meta_data_supplier_bank_name" value="{{ $meta_data->supplier_bank_name }}" class="form-control">
                  <div id="error-meta_data_supplier_bank_name" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-meta_data_supplier_bank_code" class="col-sm-2 col-form-label">{{ $lang->column_supplier_bank_code }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-meta_data_supplier_bank_code" name="meta_data_supplier_bank_code" value="{{ $meta_data->supplier_bank_code }}" class="form-control">
                  <div id="error-meta_data_supplier_bank_code" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-meta_data_supplier_bank_account" class="col-sm-2 col-form-label">{{ $lang->column_supplier_bank_account }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-meta_data_supplier_bank_account" name="meta_data_supplier_bank_account" value="{{ $meta_data->supplier_bank_account }}" class="form-control">
                  <div id="error-meta_data_supplier_bank_account" class="invalid-feedback"></div>
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

</script>
@endsection