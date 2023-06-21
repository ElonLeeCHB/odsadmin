@extends('admin.app')

@section('pageJsCss')
<script src="{{ asset('oc-asset/javascript/jquery/datetimepicker/moment.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('oc-asset/javascript/jquery/datetimepicker/moment-with-locales.min.js') }}" type="text/javascript" ></script>
<script src="{{ asset('oc-asset/javascript/jquery/datetimepicker/daterangepicker.js') }}" type="text/javascript" ></script>
<link	href="{{ asset('oc-asset/javascript/jquery/datetimepicker/daterangepicker.css') }}" rel="stylesheet" type="text/css"/>
@endsection

@section('columnLeft')
	@include('admin.common.column_left')
@endsection

@section('content')
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="float-end">
        <button type="submit" form="form-trans_from_opencart" data-bs-toggle="tooltip" title="Save" class="btn btn-primary"><i class="fas fa-save"></i></button>
        <a href="153c12deb2c87f8b7adf" data-bs-toggle="tooltip" title="Back" class="btn btn-light"><i class="fas fa-reply"></i></a></div>
      <h1>Translations</h1>
      <ol class="breadcrumb">
              </ol>
    </div>
  </div>
  <div class="container-fluid">
    <div class="card">
      <div class="card-header"><i class="fas fa-pencil-alt"></i> Translate Opencart Language text</div>
      <div class="card-body">
        <form id="form-trans_from_opencart" action="{{ route('lang.admin.system.maintenance.tools.trans_from_opencart') }}" method="post">
            @csrf
          <div class="row mb-3">
            <label for="input-transText" class="col-sm-2 col-form-label">Text to transform</label>
            <div class="col-sm-10">
              <textarea id="input-transText" name="transText" class="form-control" placeholder="Opencart format" rows="20">{{ $transText ?? null }}</textarea>
              @if(!empty($toArray))
              <table style="border: solid thin black;">
                @foreach($toArray as $row)
                <tr>
                  <td>{{ $row['key'] }}</td>
                  <td>{{ $row['value'] }}</td>
                </tr>
                @endforeach
              </table>
              @endif
              <div id="error-transText" class="invalid-feedback"></div>
            </div>
          </div>
          <input type="hidden" name="user_id" value="1" id="input-user-id"/>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection