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
        <button type="submit" form="form-location" data-bs-toggle="tooltip" title="{{ $lang->button_save }}" class="btn btn-primary"><i class="fa fa-save"></i></button>
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
        <form id="form-location" action="{{ $save_url }}" method="post" data-oc-toggle="ajax">
            @csrf
            @method('POST')
          <ul class="nav nav-tabs">
            <li class="nav-item"><a href="#tab-location" data-bs-toggle="tab" class="nav-link active"> 基本資料</a></li>
          </ul>
          <div class="tab-content">
            <div id="tab-location" class="tab-pane active">
              <div class="row mb-3 required">
                <label for="input-name" class="col-sm-2 col-form-label">{{ $lang->column_name }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-name" name="name" value="{{ $location->name }}" class="form-control">
                  <div id="error-name" class="invalid-feedback"></div>
                </div>
              </div>
              <div class="row mb-3 required">
                <label for="input-short_name" class="col-sm-2 col-form-label">{{ $lang->column_short_name }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-short_name" name="short_name" value="{{ $location->short_name }}" class="form-control">
                  <div id="error-short_name" class="invalid-feedback"></div>
                </div>
              </div>
              <div class="row mb-3">
                <label for="input-tin" class="col-sm-2 col-form-label">{{ $lang->column_tin }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-tin" name="tin" value="{{ $location->tin }}" class="form-control">
                  <div id="error-tin" class="invalid-feedback"></div>
                </div>
              </div>
              <div class="row mb-3">
                <label for="input-owner" class="col-sm-2 col-form-label">{{ $lang->column_owner }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-owner" name="owner" value="{{ $location->owner }}" class="form-control">
                  <div id="error-owner" class="invalid-feedback"></div>
                </div>
              </div>
              <div class="row mb-3">
                <label for="input-geocode" class="col-sm-2 col-form-label">{{ $lang->column_geocode }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-geocode" name="geocode" value="{{ $location->geocode }}" class="form-control">
                  <div class="form-text">{{ $lang->text_geocode }}</div>
                </div>
              </div>
              <div class="row mb-3">
                <label for="input-telephone" class="col-sm-2 col-form-label">{{ $lang->column_telephone }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-telephone" name="telephone" value="{{ $location->telephone }}"  class="form-control">
                  <div id="error-telephone" class="invalid-feedback"></div>
                </div>
              </div>
            </div>
          </div>
          <input type="hidden" id="input-location_id" name="location_id" value="{{ $location_id }}"></form>
      </div>
    </div>
  </div>
</div>
@endsection
