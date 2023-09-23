@extends('admin.app')

@section('pageJsCss')
<script src="{{ asset('js/jquery.twzipcode.js') }}" type="text/javascript" ></script>
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
        {{-- <button type="submit" form="form-organization" data-bs-toggle="tooltip" title="Save" class="btn btn-primary"><i class="fas fa-save"></i></button> --}}
        <button type="submit" form="form-organization" data-bs-toggle="tooltip" title="儲存" class="btn btn-primary"><i class="fa fa-save"></i></button>
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
          </ul>
          <form id="form-organization" action="{{ $save }}" method="post" data-oc-toggle="ajax">
            @csrf
            @method('POST')
            <div class="tab-content">
              <div id="tab-general" class="tab-pane active">

                  <fieldset>
                    <legend>{{ $lang->trans('text_organization_details') }}</legend>
                    <div class="row mb-3 required">
                      <label for="input-name" class="col-sm-2 col-form-label">{{ $lang->entry_name }}</label>
                      <div class="col-sm-10">
                        <input type="text" name="name" value="{{ $organization->name }}" placeholder="{{ $lang->entry_name }}" id="input-name" class="form-control"/>
                        <div id="error-name" class="invalid-feedback"></div>
                      </div>
                    </div>
                    <div class="row mb-3 required">
                      <label for="input-short_name" class="col-sm-2 col-form-label">{{ $lang->entry_short_name }}</label>
                      <div class="col-sm-10">
                        <input type="text" name="short_name" value="{{ $organization->short_name }}" placeholder="{{ $lang->entry_short_name }}" id="input-short_name" class="form-control"/>
                        <div id="error-short_name" class="invalid-feedback"></div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="input-telephone" class="col-sm-2 col-form-label">{{ $lang->entry_telephone }}</label>
                      <div class="col-sm-10">
                        <div class="input-group">
                          <input type="text" name="telephone_prefix" value="{{ $organization->telephone_prefix }}" placeholder="區碼" id="input-telephone_prefix"  class="form-control" style="width:30px;"/>
                          <input type="text" name="telephone" value="{{ $organization->telephone }}" placeholder="{{ $lang->entry_telephone }}" id="input-telephone" class="form-control" style="width:100px;"/>
                          <div id="error-telephone" class="invalid-feedback"></div>
                        </div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="input-tax_id_num" class="col-sm-2 col-form-label">{{ $lang->entry_tax_id_num }}</label>
                      <div class="col-sm-10">
                        <input type="text" name="tax_id_num" value="{{ $organization->tax_id_num }}" placeholder="{{ $lang->entry_tax_id_num }}" id="input-tax_id_num" class="form-control"/>
                        <div id="error-tax_id_num" class="invalid-feedback"></div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="input-type1" class="col-sm-2 col-form-label">{{ $lang->entry_type1 }}</label>
                      <div class="col-sm-10">
                        <div class="input-group">
                          <select name="type1" id="input-type1" class="form-select">
                            <option value="1" @if($organization->type1 == 1) selected @endif>營利事業</option>
                            <option value="2" @if($organization->type1 == 2) selected @endif>政府機關</option>
                            <option value="3" @if($organization->type1 == 3) selected @endif>各級學校</option>
                            <option value="4" @if($organization->type1 == 4) selected @endif>非營利事業</option>
                          </select>
                        </div>
                        <div class="form-text">{{-- 這裡放註解 --}}</div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="input-company_name" class="col-sm-2 col-form-label">{{ $lang->trans('entry_company_name') }}</label>
                      <div class="col-sm-10">
                        <input type="text" name="company_name" value="{{ $organization->company_name }}" placeholder="{{ $lang->entry_company_name }}" id="input-company_name" class="form-control"/>
                        <input type="hidden" name="company_id" value=""id="input-company_id" />
                        <div id="error-company_name" class="invalid-feedback"></div>
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label class="col-sm-2 col-form-label">{{ $lang->trans('entry_is_corporation') }}</label>
                      <div class="col-sm-10">
                        <div class="form-check form-switch form-switch-lg">
                          <input type="hidden" name="is_corporation" value="0"/>
                          <input type="checkbox" name="is_corporation" value="1" id="input-is_corporation" class="form-check-input" @checked(old('is_corporation', $organization->is_corporation)) />
                        </div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="input-corporation_name" class="col-sm-2 col-form-label">{{ $lang->entry_corporation_name }}</label>
                      <div class="col-sm-10">
                        <input type="text" name="corporation_name" value="{{ $organization->corporation_name }}" placeholder="{{ $lang->entry_corporation_name }}" id="input-corporation_name" class="form-control"/>
                        <input type="hidden" name="company_id" value="" id="input-company_id" />
                        <div id="error-corporation_name" class="invalid-feedback"></div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="input-created_date" class="col-sm-2 col-form-label">{{ $lang->entry_created_date}}</label>
                      <div class="col-sm-10">
                        <input type="text" name="created_date" value="{{ $organization->created_date }}" placeholder="{{ $lang->entry_created_date}}" id="input-created_date" class="form-control" disabled/>
                        <div id="error-created_date" class="invalid-feedback"></div>
                      </div>
                    </div>
                                  </fieldset>

                  <input type="hidden" name="organization_id" value="{{ $organization_id }}" id="input-organization_id"/>
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
// Company
$('#input-is_company').on('click', function (e) {
  if(!$('#input-is_company').is(':checked')){
    $('#input-company_name').val('');
  }
});
$( "#input-company_name" ).keyup(function() {
  len = $( "#input-company_name" ).length;
  if(len>0){
    $("#input-is_company").prop("checked", true);
  }
});
$( "#input-company_name" ).blur(function() {
  len = $('#input-company_name').val().length;
  if(len==0){
    $("#input-is_company").prop('checked',false)
  }
});

// Corporation
$('#input-is_corporation').on('click', function (e) {
  if(!$('#input-is_corporation').is(':checked')){
    $('#input-corporation_name').val('');
  }
});
$( "#input-corporation_name" ).keyup(function() {
  len = $( "#input-corporation_name" ).length;
  if(len>0){
    $("#input-is_corporation").prop("checked", true);
  }
});
$( "#input-corporation_name" ).blur(function() {
  len = $('#input-corporation_name').val().length;
  if(len==0){
    $("#input-is_corporation").prop('checked',false)
  }
});
--></script>
@endsection
