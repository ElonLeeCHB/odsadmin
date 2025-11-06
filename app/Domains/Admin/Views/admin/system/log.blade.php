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
        <button type="button" data-bs-toggle="tooltip" title="篩選" onclick="$('#filter-log').toggleClass('d-none');" class="btn btn-light d-md-none d-lg-none"><i class="fa-solid fa-filter"></i></button>
      </div>
      <h1>日誌查看</h1>
      @include('admin.common.breadcumb')
    </div>
  </div>
  <div class="container-fluid">
    <div class="row">
      <div id="filter-log" class="col-lg-3 col-md-12 order-lg-last d-none d-lg-block mb-3">
        <form>
          <div class="card">
            <div class="card-header"><i class="fa-solid fa-filter"></i> 篩選條件</div>
            <div class="card-body">

              <div class="mb-3">
                <label class="form-label">日期</label>
                <input type="date" id="input-date" name="filter_date" value="{{ $filter_date ?? '' }}" class="form-control" autocomplete="off"/>
              </div>

              <div class="mb-3">
                <label class="form-label">HTTP Method</label>
                <select name="filter_method" id="input-method" class="form-select">
                  <option value="">-- 全部 --</option>
                  <option value="GET" @if(($filter_method ?? '') == 'GET') selected @endif>GET</option>
                  <option value="POST" @if(($filter_method ?? '') == 'POST') selected @endif>POST</option>
                  <option value="PUT" @if(($filter_method ?? '') == 'PUT') selected @endif>PUT</option>
                  <option value="PATCH" @if(($filter_method ?? '') == 'PATCH') selected @endif>PATCH</option>
                  <option value="DELETE" @if(($filter_method ?? '') == 'DELETE') selected @endif>DELETE</option>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label">關鍵字搜尋</label>
                <input type="text" id="input-keyword" name="filter_keyword" value="{{ $filter_keyword ?? '' }}" placeholder="搜尋 URL、IP、備註..." class="form-control" autocomplete="off"/>
              </div>

              <div class="text-end">
                <button type="reset" id="button-clear" class="btn btn-light"><i class="fa fa-refresh" aria-hidden="true"></i> 清除</button>
                <button type="button" id="button-filter" class="btn btn-primary"><i class="fa-solid fa-filter"></i> 篩選</button>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="col-lg-9 col-md-12">
        <div class="card">
          <div class="card-header"><i class="fa-solid fa-list"></i> 日誌列表</div>
          <div id="log" class="card-body">{!! $list !!}</div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('buttom')
<script type="text/javascript"><!--
$('#log').on('click', 'thead a, .pagination a', function(e) {
	e.preventDefault();

	$('#log').load(this.href);
});

$('#button-filter').on('click', function() {
	url = '';

  var filter_date = $('#input-date').val();

  if (filter_date) {
    url += '&filter_date=' + encodeURIComponent(filter_date);
  }

	var filter_method = $('#input-method').val();

	if (filter_method) {
		url += '&filter_method=' + encodeURIComponent(filter_method);
	}

  var filter_keyword = $('#input-keyword').val();

  if (filter_keyword) {
    url += '&filter_keyword=' + encodeURIComponent(filter_keyword);
  }

	url = "{{ $list_url }}?" + url;

	$('#log').load(url);
});

$('#button-clear').on('click', function() {
	$('#input-date').val('{{ \Carbon\Carbon::today()->format('Y-m-d') }}');
	$('#input-method').val('');
	$('#input-keyword').val('');
	$('#button-filter').click();
});

// 點擊查看詳情
$('#log').on('click', '.view-detail', function(e) {
	e.preventDefault();

	var date = $(this).data('date');
	var uniqueid = $(this).data('uniqueid');

	// 使用 modal 或新視窗顯示詳情
	var url = "{{ route('lang.admin.system.logs.form') }}?date=" + date + "&uniqueid=" + uniqueid;

	// 在新分頁開啟
	window.open(url, '_blank');
});
//--></script>
@endsection
