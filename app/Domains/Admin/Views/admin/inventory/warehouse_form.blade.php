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
        <form id="form-warehouse" action="{{ $save_url }}" method="post" data-oc-toggle="ajax">
            @csrf
            @method('POST')
          <ul class="nav nav-tabs">
            <li class="nav-item"><a href="#tab-general" data-bs-toggle="tab" class="nav-link active"> {{ $lang->tab_general }}</a></li>
          </ul>
          <div class="tab-content">
            <div id="tab-general" class="tab-pane active">
              <div class="row mb-3 required">
                <label for="input-name" class="col-sm-2 col-form-label">{{ $lang->column_name }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-name" name="name" value="{{ $warehouse->name }}" class="form-control">
                  <div id="error-name" class="invalid-feedback"></div>
                </div>
              </div>
                          </div>

          </div>
          <input type="hidden" id="input-warehouse_id" name="warehouse_id" value="{{ $warehouse_id }}"></form>
      </div>
    </div>
  </div>
</div>

@endsection
