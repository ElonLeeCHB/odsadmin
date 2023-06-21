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
        <button type="submit" form="form-parse_uniform_invoice_number" data-bs-toggle="tooltip" title="Save" class="btn btn-primary"><i class="fas fa-save"></i></button>
      </div>
      <h1>拆解統一編號CSV檔</h1>
    </div>
  </div>

  <div class="container-fluid">
    <div class="card">
      <div class="card-header"><i class="fas fa-pencil-alt"></i> 拆解統一編號CSV檔</div>
      <div class="card-body">
        <form id="form-parse_uniform_invoice_number" action="{{ route('lang.admin.system.maintenance.tools.parse_uniform_invoice_number') }}" method="post">
          @csrf

          <div class="row mb-3 required">
            <label for="input-path" class="col-sm-2 col-form-label">路徑</label>
            <div class="col-sm-10">
              <input type="text" id="input-path" name="path" value="" placeholder="請輸入作業系統完整路徑 D:\path\to\BGMOPEN1.csv" class="form-control"/>
              <div id="error-path" class="invalid-feedback"></div>
            </div>
          </div>
          
        </form>
      </div>
    </div>
  </div>
</div>
@endsection