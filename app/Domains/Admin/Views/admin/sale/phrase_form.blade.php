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
          <button type="submit" form="form-phrase" data-bs-toggle="tooltip" title="儲存" class="btn btn-primary"><i class="fa fa-save"></i></button>
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
              <li class="nav-item"><a href="#tab-general" data-bs-toggle="tab" class="nav-link active">{{ $lang->tab_general }}</a></li>
              <li class="nav-item"><a href="#tab-data" data-bs-toggle="tab" class="nav-link">{{ $lang->tab_data }}</a></li>
            </ul>
            <form id="form-phrase" action="{{ $save_url }}" method="post" data-oc-toggle="ajax">
              @csrf
              @method('POST')

              <div class="tab-content">
                <div id="tab-general" class="tab-pane active" >
                  <ul class="nav nav-tabs">
                    @foreach($languages as $language)
                    <li class="nav-item"><a href="#language-{{ $language->code }}" data-bs-toggle="tab" class="nav-link @if ($loop->first)active @endif">{{ $language->native_name }}</a></li>
                    @endforeach
                  </ul>
                  <div class="tab-content">
                    @foreach($languages as $language)
                    <div id="language-{{ $language->code }}" class="tab-pane @if ($loop->first)active @endif">
                      <input type="hidden" name="translations[{{ $language->code }}][id]" value="{{ $translations[$language->code]['id'] ?? '' }}" >

                      <div class="row mb-3 required">
                        <label for="input-name-{{ $language->code }}" class="col-sm-2 col-form-label">{{ $lang->column_name }}</label>
                        <div class="col-sm-10">
                          <div class="input-group">
                            <input type="text" name="translations[{{ $language->code }}][name]" value="{{ $translations[$language->code]['name'] ?? ''  }}" placeholder="{{ $lang->column_name }}" id="input-name-{{ $language->code }}" class="form-control">
                                                    </div>
                          <div id="error-name-{{ $language->code }}" class="invalid-feedback"></div>
                        </div>
                      </div>
                      <div class="row mb-3 ">
                        <label for="input-short_name-{{ $language->code }}" class="col-sm-2 col-form-label">{{ $lang->column_short_name }}</label>
                        <div class="col-sm-10">
                          <div class="input-group">
                            <input type="text" name="translations[{{ $language->code }}][short_name]" value="{{ $translations[$language->code]['short_name'] ?? ''  }}" placeholder="{{ $lang->column_short_name }}" id="input-short_name-{{ $language->code }}" class="form-control">
                                                    </div>
                          <div id="error-short_name-{{ $language->code }}" class="invalid-feedback"></div>
                        </div>
                      </div>
                    </div>
                    @endforeach
                  </div>
                </div>

                <div id="tab-data" class="tab-pane">
                  <div class="row mb-3 required">
                    <label for="input-taxonomy_code" class="col-sm-2 col-form-label">{{ $lang->column_taxonomy }}</label>
                    <div class="col-sm-10">
                      <div class="input-group">
                        <select name="taxonomy_code" id="input-taxonomy_code" class="form-select">
                          <option value="0">{{ $lang->text_none }}</option>
                            <option value="phrase_order_comment"@if($term->taxonomy_code == 'phrase_order_comment') selected @endif>訂單客戶備註 (phrase_order_comment)</option>
                            <option value="phrase_order_extra_comment"@if($term->taxonomy_code == 'phrase_order_extra_comment') selected @endif>訂單餐點備註 (phrase_order_extra_comment)</option>
                        </select>
                      </div>
                      <div id="error-taxonomy_code" class="invalid-feedback"></div>
                    </div>
                  </div>
                  <div class="row mb-3">
                    <label for="input-sort_order" class="col-sm-2 col-form-label">{{ $lang->column_sort_order }}</label>
                    <div class="col-sm-10">
                      <input type="text" name="sort_order" value="{{ $term->sort_order }}" placeholder="Sort Order" id="input-sort_order" class="form-control">
                    </div>
                  </div>


                  <div class="row mb-3">
                    <label class="col-sm-2 col-form-label">{{ $lang->column_is_active }}</label>
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

                <input type="hidden" name="term_id" value="{{ $term_id }}" id="input-term_id"/>
              </div>
            </form>
            </div>
            </div>
          </div>
      </div>
  </div>
@endsection