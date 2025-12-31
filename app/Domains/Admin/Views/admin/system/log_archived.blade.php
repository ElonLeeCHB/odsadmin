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
        <a href="{{ route('lang.admin.system.logs.index') }}" class="btn btn-light"><i class="fa-solid fa-database"></i> 即時日誌</a>
        <button type="button" data-bs-toggle="tooltip" title="篩選" onclick="$('#filter-log').toggleClass('d-none');" class="btn btn-light d-md-none d-lg-none"><i class="fa-solid fa-filter"></i></button>
      </div>
      <h1>歷史日誌</h1>
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
                <label class="form-label">月份（YYYY-MM）</label>
                <input type="month" id="input-month" name="filter_month" value="{{ $filter_month ?? '' }}" class="form-control" placeholder="例如：2025-12"/>
              </div>

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
                <label class="form-label">狀態</label>
                <select name="filter_status" id="input-status" class="form-select">
                  <option value="">-- 全部 --</option>
                  <option value="success" @if(($filter_status ?? '') == 'success') selected @endif>Success</option>
                  <option value="error" @if(($filter_status ?? '') == 'error') selected @endif>Error</option>
                  <option value="warning" @if(($filter_status ?? '') == 'warning') selected @endif>Warning</option>
                  <option value="empty" @if(($filter_status ?? '') == 'empty') selected @endif>空值</option>
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

        @if(count($archives) > 0)
        <div class="card mt-3">
          <div class="card-header"><i class="fa-solid fa-archive"></i> 壓縮檔清單</div>
          <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
              <thead>
                <tr>
                  <th>月份</th>
                  <th class="text-end">大小</th>
                </tr>
              </thead>
              <tbody>
                @foreach($archives as $archive)
                <tr class="archive-row" data-month="{{ $archive['month'] }}" style="cursor: pointer;">
                  <td>{{ $archive['month'] }}</td>
                  <td class="text-end">{{ $archive['size_formatted'] }}</td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
        @endif
      </div>
      <div class="col-lg-9 col-md-12">
        <div class="card">
          <div class="card-header"><i class="fa-solid fa-list"></i> 歷史日誌列表</div>
          <div id="log" class="card-body">
            @if($filter_month)
              {!! $list !!}
            @else
              <div class="text-center text-muted py-5">
                <i class="fa-solid fa-archive fa-3x mb-3"></i>
                <p>請選擇月份以載入歷史日誌</p>
              </div>
            @endif
          </div>
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
  var month = $('#input-month').val();

  if (!month) {
    alert('請先選擇月份');
    return;
  }

	url = '&filter_month=' + encodeURIComponent(month);

  var filter_date = $('#input-date').val();

  if (filter_date) {
    url += '&filter_date=' + encodeURIComponent(filter_date);
  }

	var filter_method = $('#input-method').val();

	if (filter_method) {
		url += '&filter_method=' + encodeURIComponent(filter_method);
	}

	var filter_status = $('#input-status').val();

	if (filter_status) {
		url += '&filter_status=' + encodeURIComponent(filter_status);
	}

  var filter_keyword = $('#input-keyword').val();

  if (filter_keyword) {
    url += '&filter_keyword=' + encodeURIComponent(filter_keyword);
  }

	url = "{{ $list_url }}?" + url;

	$('#log').load(url);
});

$('#button-clear').on('click', function() {
	$('#input-date').val('');
	$('#input-method').val('');
	$('#input-status').val('');
	$('#input-keyword').val('');
});

// 點擊壓縮檔清單載入該月份
$('.archive-row').on('click', function() {
  var month = $(this).data('month');
  $('#input-month').val(month);
  $('#button-filter').click();
});

// 點擊查看詳情
$('#log').on('click', '.view-detail', function(e) {
	e.preventDefault();

	var archiveId = $(this).data('archive-id');

	var url = "{{ route('lang.admin.system.logs.archived.form') }}?archive_id=" + encodeURIComponent(archiveId);

	window.open(url, '_blank');
});
//--></script>
@endsection
