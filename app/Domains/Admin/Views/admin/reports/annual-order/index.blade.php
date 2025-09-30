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
        <button type="button" id="button-export" class="btn btn-success">
          <i class="fa-solid fa-download"></i> 匯出 XLSX
        </button>
      </div>
      <h1>年度訂單總金額分析</h1>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('lang.admin.dashboard') }}">首頁</a></li>
        <li class="breadcrumb-item active">年度訂單分析</li>
      </ol>
    </div>
  </div>
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-3 col-md-12 mb-3">
        <div class="card">
          <div class="card-header"><i class="fa-solid fa-filter"></i> 篩選年份</div>
          <div class="card-body">
            <form method="GET" action="{{ route('lang.admin.reports.annual-order.index') }}">
              @foreach($availableYears as $y)
                <div class="form-check mb-2">
                  <input class="form-check-input" type="checkbox" name="years[]" value="{{ $y }}" id="year-{{ $y }}"
                    @if(in_array($y, $selectedYears)) checked @endif>
                  <label class="form-check-label" for="year-{{ $y }}">
                    {{ $y }} 年
                  </label>
                </div>
              @endforeach
              <div class="text-end mt-3">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search"></i> 查詢</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <div class="col-lg-9 col-md-12">
        <div class="card">
          <div class="card-header"><i class="fa-solid fa-table"></i> 年度訂單總金額矩陣</div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered table-hover">
                <thead>
                  <tr>
                    <th class="text-center">年份</th>
                    @for($m = 1; $m <= 12; $m++)
                      <th class="text-end">{{ $m }}月</th>
                    @endfor
                    <th class="text-end bg-light">全年總計</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($matrix as $year => $months)
                    <tr>
                      <td class="text-center"><strong>{{ $year }}</strong></td>
                      @php $yearTotal = 0; @endphp
                      @for($m = 1; $m <= 12; $m++)
                        <td class="text-end">
                          @if(isset($months[$m]) && $months[$m])
                            {{ number_format($months[$m], 0) }}
                            @php $yearTotal += $months[$m]; @endphp
                          @else
                            <span class="text-muted">-</span>
                          @endif
                        </td>
                      @endfor
                      <td class="text-end bg-light"><strong>{{ number_format($yearTotal, 0) }}</strong></td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="14" class="text-center text-muted">請選擇年份查詢</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('buttom')
<script type="text/javascript">
$('#button-export').on('click', function() {
  var selectedYears = [];
  $('input[name="years[]"]:checked').each(function() {
    selectedYears.push($(this).val());
  });

  if (selectedYears.length === 0) {
    alert('請至少選擇一個年份');
    return;
  }

  var url = '{{ route("lang.admin.reports.annual-order.export") }}?years=' + selectedYears.join(',');
  window.location.href = url;
});
</script>
@endsection
