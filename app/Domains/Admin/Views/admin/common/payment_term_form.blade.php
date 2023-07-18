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
        <button type="submit" form="form-term" data-bs-toggle="tooltip" title="儲存" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="{{ $back }}" data-bs-toggle="tooltip" title="{{ $lang->button_back }}" class="btn btn-light"><i class="fas fa-reply"></i></a>
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
            <li class="nav-item"><a href="#tab-data" data-bs-toggle="tab" class="nav-link active">{{ $lang->tab_data }}</a></li>
          </ul>
          <form id="form-term" action="{{ $save }}" method="post" data-oc-toggle="ajax">
            @csrf
            @method('POST')

            <div class="tab-content">
              <div id="tab-data" class="tab-pane active">
                
                {{-- name --}}
                <div class="row mb-3 required">
                  <label class="col-sm-2 col-form-label">{{ $lang->column_name }}</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <input type="text" id="input-name" name="name" value="{{ $payment_term->name ?? ''}}" data-oc-target="autocomplete-name" class="form-control" />
                    </div>
                    <ul id="autocomplete-name" class="dropdown-menu"></ul>
                  </div>
                </div>

                {{-- type --}}
                <div class="row mb-3 required">
                  <label class="col-sm-2 col-form-label">{{ $lang->column_type }}</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <select name="type" id="input-type" class="form-select">
                        <option value="">{{ $lang->text_select }}</option>
                        <option value="1" @if($payment_term->type=='1') selected @endif>1:銷售</option>
                        <option value="2" @if($payment_term->type=='2') selected @endif>2:採購</option>
                      </select>
                      <div id="error-type" class="invalid-feedback"></div>
                    </div>
                  </div>
                </div>

                {{-- due_date_basis --}}
                <div class="row mb-3 required">
                  <label class="col-sm-2 col-form-label">{{ $lang->column_due_date_basis }}</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <select name="due_date_basis" id="input-due_date_basis" class="form-select">
                        <option value=""> -- </option>
                        <option value="1" @if($payment_term->due_date_basis=='1') selected @endif>1:來源單據日</option>
                        <option value="2" @if($payment_term->due_date_basis=='2') selected @endif>2:出貨日(到貨日)</option>
                        <option value="3" @if($payment_term->due_date_basis=='3') selected @endif>3:次月初</option>
                      </select>
                      <div id="error-due_date_basis" class="invalid-feedback"></div>
                    </div>
                  </div>
                </div>

                {{-- due_date_plus_months 應收款日為起算日起加幾月 --}}
                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label">{{ $lang->column_due_date_plus_months }}</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <input type="text" id="input-due_date_plus_months" name="due_date_plus_months" value="{{ $payment_term->due_date_plus_months }}" data-oc-target="autocomplete-due_date_plus_months" class="form-control" />
                    </div>
                    <ul id="autocomplete-due_date_plus_months" class="dropdown-menu"></ul>
                  </div>
                </div>

                {{-- due_date_plus_days 應收款日為起算日起加幾天 --}}
                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label">{{ $lang->column_due_date_plus_days }}</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <input type="text" id="input-due_date_plus_days" name="due_date_plus_days" value="{{ $payment_term->due_date_plus_days }}" data-oc-target="autocomplete-due_date_plus_days" class="form-control" />
                    </div>
                    <ul id="autocomplete-due_date_plus_days" class="dropdown-menu"></ul>
                  </div>
                </div>

                {{-- comment --}}
                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label">{{ $lang->column_comment }}</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <input type="text" id="input-comment" name="comment" value="{{ $payment_term->comment ?? ''}}" data-oc-target="autocomplete-comment" class="form-control" />
                    </div>
                    <ul id="autocomplete-comment" class="dropdown-menu"></ul>
                  </div>
                </div>

                {{-- sort_order --}}
                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label">{{ $lang->column_sort_order }}</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <input type="text" id="input-sort_order" name="sort_order" value="{{ $payment_term->sort_order ?? '100'}}" data-oc-target="autocomplete-sort_order" class="form-control" />
                    </div>
                    <ul id="autocomplete-sort_order" class="dropdown-menu"></ul>
                  </div>
                </div>
                
                {{-- is_active --}}
                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label">{{ $lang->column_enable }}</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <div id="input-is_active" class="form-check form-switch form-switch-lg">
                        <input type="hidden" name="is_active" value="0"/>
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" @if($payment_term->is_active) checked @endif/>
                      </div>
                    </div>
                  </div>
                </div>
                
              </div>
              <input type="hidden" name="payment_term_id" value="{{ $payment_term_id }}" id="input-payment_term_id"/>
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
