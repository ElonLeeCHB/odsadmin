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

                    <div class="row mb-3 required">
                      <label for="input-group" class="col-sm-2 col-form-label">{{ $lang->column_group }}</label>
                      <div class="col-sm-10">
                        <input type="text" id="input-group" name="group" value="{{ $setting->group }}" class="form-control"/>
                        <div id="error-group" class="invalid-feedback"></div>
                      </div>
                    </div>

                    <div class="row mb-3 required">
                      <label for="input-setting_key" class="col-sm-2 col-form-label">{{ $lang->column_setting_key }}</label>
                      <div class="col-sm-10">
                        <input type="text" id="input-setting_key" name="setting_key" value="{{ $setting->setting_key }}" class="form-control"/>
                        <div id="error-setting_key" class="invalid-feedback"></div>
                      </div>
                    </div>

                    <div class="row mb-3 required">
                      <label for="input-setting_value" class="col-sm-2 col-form-label">{{ $lang->column_setting_value }}</label>
                      <div class="col-sm-10">
                        <textarea id="input-setting_value" name="setting_value" class="form-control" >{{ $setting_value }}</textarea>
                        <div id="error-setting_value" class="invalid-feedback"></div>
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label for="input-is_autoload" class="col-sm-2 col-form-label">{{ $lang->column_is_autoload }}</label>
                      <div class="col-sm-10">
                        <div class="form-check form-switch form-switch-lg">
                          <input type="hidden" name="is_autoload" value="0"/>
                          <input type="checkbox" name="is_autoload" value="1" id="input-is_autoload" class="form-check-input" @if($setting->is_autoload) checked @endif/>
                        </div>
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label for="input-is_json" class="col-sm-2 col-form-label">{{ $lang->column_is_json }}</label>
                      <div class="col-sm-10">
                        <div class="form-check form-switch form-switch-lg">
                          <input type="hidden" name="is_json" value="0"/>
                          <input type="checkbox" name="is_json" value="1" id="input-is_json" class="form-check-input" @if($setting->is_json) checked @endif/>
                        </div>
                      </div>
                    </div>


                  </fieldset>

                  <input type="hidden" name="setting_id" value="{{ $setting->id }}" id="input-setting_id"/>
              </div>
            </form>
            </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('buttom')
<script type="text/javascript">

</script>
@endsection
