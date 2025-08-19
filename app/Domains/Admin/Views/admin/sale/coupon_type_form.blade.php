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
        <button type="submit" form="form-warehouse" data-bs-toggle="tooltip" title="{{ $lang->button_save }}" class="btn btn-primary"><i class="fa fa-save"></i></button>
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
        <form id="form-warehouse" action="http://ods.dtstw.test/laravel/zh-Hant/backend/inventory/warehouses/save" method="post" data-oc-toggle="ajax">
          @csrf
          @method('POST')
          <input type="hidden" id="input-coupon_type_id" name="coupon_type_id" value="{{ $coupon_type_id }}">

          <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item"><a href="#tab-details" data-bs-toggle="tab" class="nav-link active">{{ $lang->tab_details }}</a></li>
          </ul>
          <div class="tab-content">
            <div id="tab-general" class="tab-pane active show" role="tabpanel">
              
              <div class="row mb-3">
                <label for="input-name" class="col-sm-2 col-form-label">名稱</label>
                <div class="col-sm-10">
                  <div class="input-group">
                    <input type="text" id="input-name" name="name" value="{{ $couponType->name }}" class="form-control">
                  </div>
                  <div id="error-name" class="invalid-feedback"></div>
                </div>
              </div>
              
              <div class="row mb-3">
                <label for="input-valid-from" class="col-sm-2 col-form-label">開始日期</label>
                <div class="col-sm-10">
                  <div class="input-group">
                      <input type="date" 
                              id="input-valid-from" 
                              name="valid_from" 
                              value="{{ old('valid_from', optional($couponType)->valid_from?->format('Y-m-d')) }}" 
                              class="form-control @error('valid_from') is-invalid @enderror">
                      <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                  </div>
                    @error('valid_from')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-comment" class="col-sm-2 col-form-label">備註</label>
                <div class="col-sm-10">
                  <div class="input-group">
                    <input type="text" id="input-comment" name="comment" value="{{ $couponType->comment }}" class="form-control">
                  </div>
                  <div id="error-comment" class="invalid-feedback"></div>
                </div>
              </div>
              
              <div class="row mb-3">
                <label class="col-sm-2 col-form-label">啟用</label>
                <div class="col-sm-10">
                  <div class="input-group">
                    <div id="input-is_active" class="form-check form-switch form-switch-lg">
                      <input type="hidden" name="is_active" value="0">
                      <input type="checkbox" name="is_active" value="1" class="form-check-input" checked="">
                    </div>
                  </div>
                </div>
              </div>

            </div>

          </div>
          <input type="hidden" id="input-warehouse_id" name="warehouse_id" value="4"></form>
      </div>
    </div>
  </div>
</div>

@endsection
