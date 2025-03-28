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

        @if(!empty($calc_url))
        <a data-href="{{ $print_form_url }}" id="href-printForm"  target="_blank" data-bs-toggle="tooltip" title="列印" class="btn btn-info"><i class="fa-solid fa-print"></i></a>
        @endif
        <a href="{{ $back_url }}" data-bs-toggle="tooltip" title="Back" class="btn btn-light"><i class="fas fa-reply"></i></a>

      </div>
      <h1>{{ $lang->heading_title }}</h1>
      @include('admin.common.breadcumb')
    </div>
  </div>
  <div class="container-fluid">
    <div class="card">
      <div class="card-body">
        <ul class="nav nav-tabs">
            <li class="nav-item"><a href="#tab-general" data-bs-toggle="tab" class="nav-link active">{{ $lang->tab_general }}</a></li>
        </ul>
        <div class="tab-content">
          <div id="tab-general" class="tab-pane active">

            <form id="form-mrequisition" method="post" data-oc-toggle="ajax" data-oc-load="{{ route('lang.admin.sale.requisitions.getJsonByRequiredDate') }}" data-oc-target="#product">
              @csrf
              @method('POST')
              <div class="tab-content">
                <div id="tab-general" class="tab-pane active">
                  <div class="row mb-3">
                    <label for="input-required_date" class="col-sm-2 col-form-label">{{ $lang->column_required_date }}</label>
                    <div class="col-sm-10">
                      <div class="input-group">
                        <input type="text" id="input-required_date" name="required_date" value="{{ $required_date ?? \Carbon\Carbon::today()->toDateString() }}" placeholder="{{ $lang->column_required_date }}" class="form-control date"/>
                        <div class="input-group-text"><i class="fa-regular fa-calendar"></i></div>
                        <button type="button" id="btn-redirectToRequiredDate" class="btn btn-primary btn-sm float-end" data-bs-toggle="tooltip" title="查詢" >查詢</button> &nbsp;
                        <button type="button" id="btn-redirectToRequiredDateUpdate" class="btn btn-primary btn-sm float-end" data-bs-toggle="tooltip" title="重抓需求來源">更新</button>
                      </div>
                      <div id="error-required_date" class="invalid-feedback"></div>
                    </div>
                  </div>

                  <div id="formDate">
                  </div>
                </div>
              </div>
            </form>

          </div>
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
