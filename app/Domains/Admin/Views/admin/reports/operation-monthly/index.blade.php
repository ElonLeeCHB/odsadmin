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
        <button type="button" data-bs-toggle="tooltip" title="篩選" onclick="$('#filter-operation-monthly').toggleClass('d-none');" class="btn btn-light d-md-none d-lg-none"><i class="fa-solid fa-filter"></i></button>
        <a href="{{ route('lang.admin.reports.operation-monthly.exportYear', ['year' => $year]) }}" class="btn btn-success" data-bs-toggle="tooltip" title="匯出 XLSX">
          <i class="fa-solid fa-download"></i>
        </a>
      </div>
      <h1>營運月報表</h1>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('lang.admin.dashboard') }}">首頁</a></li>
        <li class="breadcrumb-item active">營運月報表</li>
      </ol>
    </div>
  </div>
  <div class="container-fluid">
    <div class="row">
      <div id="filter-operation-monthly" class="col-lg-3 col-md-12 order-lg-last d-none d-lg-block mb-3">
        <form method="GET" action="{{ route('lang.admin.reports.operation-monthly.index') }}">
          <div class="card">
            <div class="card-header"><i class="fa-solid fa-filter"></i> 篩選</div>
            <div class="card-body">
              <div class="mb-3">
                <label class="form-label">年份</label>
                <select name="year" id="input-year" class="form-select">
                  @foreach($availableYears as $y)
                    <option value="{{ $y }}" @if($y == $year) selected @endif>{{ $y }}</option>
                  @endforeach
                </select>
              </div>
              <div class="text-end">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search"></i> 查詢</button>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="col-lg-9 col-md-12">
        <div class="card">
          <div class="card-header"><i class="fa-solid fa-calendar"></i> {{ $year }} 年營運月報</div>
          <div id="operation-monthly-list" class="card-body">
            {!! $list !!}
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('buttom')
@endsection
