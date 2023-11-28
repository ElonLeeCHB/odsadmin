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
                  <input type="text" id="input-code" name="code" value="{{ $institution->code }}" class="form-control">
                  <div id="error-code" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3 required">
                <label for="input-name" class="col-sm-2 col-form-label">{{ $lang->column_name }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-name" name="name" value="{{ $institution->name }}" class="form-control">
                  <div id="error-name" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label for="input-short_name" class="col-sm-2 col-form-label">{{ $lang->column_short_name }}</label>
                <div class="col-sm-10">
                  <input type="text" id="input-short_name" name="short_name" value="{{ $institution->short_name }}" class="form-control">
                  <div id="error-short_name" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label class="col-sm-2 col-form-label">{{ $lang->column_parent_name }}</label>
                <div class="col-sm-10">
                  <div class="input-group">
                    <input type="text" id="input-parent_name" name="parent_name" value="{{ $institution->parent->name ?? ''}}" data-oc-target="autocomplete-parent_name" class="form-control" />
                  </div>
                  <div id="error-parent_name" class="invalid-feedback"></div>
                  <input type="hidden" id="input-parent_id" name="parent_id" value="{{ $institution->parent_id }}" />
                  <ul id="autocomplete-parent_name" class="dropdown-menu"></ul>
                  <div class="form-text"></div><?php /* help text */ ?>
                </div>
              </div>

              {{-- is_active --}}
              <div class="row mb-3">
                <label class="col-sm-2 col-form-label">{{ $lang->column_enable }}</label>
                <div class="col-sm-10">
                  <div class="input-group">
                    <div id="input-is_active" class="form-check form-switch form-switch-lg">
                      <input type="hidden" name="is_active" value="0"/>
                      <input type="checkbox" name="is_active" value="1" class="form-check-input" @if($institution->is_active) checked @endif/>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>
          <input type="hidden" id="input-institution_id" name="institution_id" value="{{ $institution_id }}"></form>
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
      url: "{{ $autocomplete_url }}?filter_name=" + encodeURIComponent(request),
      dataType: 'json',
      success: function (json) {
        json.unshift({
          manufacturer_id: 0,
          name: ' --- None --- '
        });

        response($.map(json, function (item) {
          return {
            label: item['name'],
            value: item['institution_id']
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