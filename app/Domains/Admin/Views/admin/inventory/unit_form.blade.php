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
        <button type="submit" form="form-unit" data-bs-toggle="tooltip" title="{{ $lang->button_save }}" class="btn btn-primary"><i class="fa fa-save"></i></button>
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
        <form id="form-unit" action="{{ $save_url }}" method="post" data-oc-toggle="ajax">
            @csrf
            @method('POST')
          <ul class="nav nav-tabs">
            <li class="nav-item"><a href="#tab-trans" data-bs-toggle="tab" class="nav-link active">{{ $lang->tab_trans }}</a></li>
            <li class="nav-item"><a href="#tab-general" data-bs-toggle="tab" class="nav-link">{{ $lang->tab_general }}</a></li>
          </ul>
          <div class="tab-content">
            <div id="tab-trans" class="tab-pane active">

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

              {{-- column_code--}}
              <div class="row mb-3">
                <label class="col-sm-2 col-form-label">{{ $lang->column_code }}</label>
                <div class="col-sm-10">
                  <div class="input-group">
                    <input type="text" id="input-code" name="code" value="{{ $unit->code ?? ''}}" data-oc-target="autocomplete-code" class="form-control" @if(!empty($unit->code)) disabled @endif />
                  </div>
                  <div class="form-text">(識別碼有可能用在程式裡面。請小心設定。)</div>
                  <div id="error-code" class="invalid-feedback"></div>
                </div>
              </div>
                
              {{-- comment --}}
              <div class="row mb-3">
                <label for="input-comment" class="col-sm-2 col-form-label">備註</label>
                <div class="col-sm-10">
                  <div class="input-group">
                    <input type="text" id="input-comment" name="comment" value="{{ $unit->comment }}" class="form-control">
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
                      <input type="checkbox" name="is_active" value="1" class="form-check-input" @if($unit->is_active) checked @endif/>
                    </div>
                  </div>
                </div>
              </div>

            </div>

          </div>
          <input type="hidden" id="input-unit_id" name="unit_id" value="{{ $unit_id }}"></form>
      </div>
    </div>
  </div>
</div>

@endsection
