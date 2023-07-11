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
        {{-- <button type="submit" form="form-term" data-bs-toggle="tooltip" title="Save" class="btn btn-primary"><i class="fas fa-save"></i></button> --}}
        <button type="submit" form="form-term" data-bs-toggle="tooltip" title="儲存" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="{{ $back_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_back }}" class="btn btn-light"><i class="fas fa-reply"></i></a>
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
            <li class="nav-item"><a href="#tab-trans" data-bs-toggle="tab" class="nav-link active">{{ $lang->tab_trans }}</a></li>
            <li class="nav-item"><a href="#tab-general" data-bs-toggle="tab" class="nav-link">{{ $lang->tab_general }}</a></li>
          </ul>
          <form id="form-term" action="{{ $save_url }}" method="post" data-oc-toggle="ajax">
            @csrf
            @method('POST')

            <div class="tab-content">
              <div id="tab-trans" class="tab-pane active" >
                <ul class="nav nav-tabs">
                  @foreach($languages as $language)
                  <li class="nav-item"><a href="#language-{{ $language->code }}" data-bs-toggle="tab" class="nav-link @if ($loop->first)active @endif">{{ $language->native_name }}</a></li>
                  @endforeach
                </ul>
                <div class="tab-content">
                  @foreach($languages as $language)
                  <div id="language-{{ $language->code }}" class="tab-pane @if ($loop->first)active @endif">
                    <input type="hidden" name="translations[{{ $language->code }}][id]" value="{{ $translations[$language->code]['id'] ?? '' }}" >

                    <div class="row mb-3">
                      <label for="input-name-{{ $language->code }}" class="col-sm-2 col-form-label">{{ $lang->column_name }}</label>
                      <div class="col-sm-10">
                        <div class="input-group">
                          <input type="text" id="input-name-{{ $language->code }}" name="translations[{{ $language->code }}][name]" value="{{ $translations[$language->code]['name'] ?? '' }}" class="form-control">
                        </div>
                        <div id="error-name-{{ $language->code }}" class="invalid-feedback"></div>
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label for="input-short_name-{{ $language->code }}" class="col-sm-2 col-form-label">{{ $lang->column_short_name }}</label>
                      <div class="col-sm-10">
                        <div class="input-group">
                          <input type="text" id="input-short_name-{{ $language->code }}" name="translations[{{ $language->code }}][short_name]" value="{{ $translations[$language->code]['short_name'] ?? '' }}" class="form-control">
                        </div>
                        <div id="error-short_name-{{ $language->code }}" class="invalid-feedback"></div>
                      </div>
                    </div>
                  </div>
                  @endforeach
                </div>
              </div>

              <div id="tab-general" class="tab-pane">
                {{-- main column_code--}}
                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label">{{ $lang->column_code }}</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <input type="text" id="input-code" name="code" value="{{ $term->code ?? ''}}" data-oc-target="autocomplete-code" class="form-control" />
                    </div>
                    <div class="form-text">(識別碼有可能用在程式裡面。請小心設定。)</div>
                    <div id="error-code" class="invalid-feedback"></div>
                  </div>
                </div>

                {{-- parent_name --}}
                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label">{{ $lang->column_parent_name }}</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <input type="text" id="input-parent_name" name="parent_name" value="{{ $term->parent->name ?? ''}}" data-oc-target="autocomplete-parent_name" class="form-control" />
                    </div>
                    <div id="error-parent_name" class="invalid-feedback"></div>
                    <input type="hidden" id="input-parent_id" name="parent_id" value="{{ $term->parent_id }}" />
                    <ul id="autocomplete-parent_name" class="dropdown-menu"></ul>
                    <div class="form-text"></div><?php /* help text */ ?>
                  </div>
                </div>
                
                {{-- comment --}}
                <div class="row mb-3">
                  <label for="input-comment" class="col-sm-2 col-form-label">備註</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <input type="text" id="input-comment" name="comment" value="{{ $term->comment }}" class="form-control">
                    </div>
                    <div id="error-comment" class="invalid-feedback"></div>
                  </div>
                </div>

                {{-- is_active --}}
                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label">{{ $lang->column_enable }}</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <div id="input-is_active" class="form-check form-switch form-switch-lg">
                        <input type="hidden" name="is_active" value="0"/>
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" @if($term->is_active) checked @endif/>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
              <input type="hidden" id="input-term_id" name="term_id" value="{{ $term_id }}"/>
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

$('#input-parent_name').autocomplete({
  'source': function (request, response) {
    var self_id = $('#input-term_id').val();

    $.ajax({
      url: "{{ $autocomplete_url }}?filter_name=" + encodeURIComponent(request) + '&equal_taxonomy_code=product_tag' + '&exclude_id=' + self_id,
      dataType: 'json',
      success: function (json) {
        json.unshift({
            term_id: '',
            name: '-- 無 --'
        });

        response($.map(json, function (item) {
          return {
            value: item['term_id'],
            label: item['name']
          }
        }));
      }
    });
  },
  'select': function (item) {
    $('#input-parent_name').val(item.label);
    $('#input-parent_id').val(item.value);
  }
});
</script>
@endsection
