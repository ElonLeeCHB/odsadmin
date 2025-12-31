<div class="table-responsive">
  <table class="table table-bordered table-hover">
    <thead>
      <tr>
        <td class="text-start" style="width: 160px;">
          @php
            $currentSort = $sort ?? 'time';
            $currentOrder = $order ?? 'desc';
            $newOrder = ($currentSort === 'time' && $currentOrder === 'desc') ? 'asc' : 'desc';

            $sortUrl = route('lang.admin.system.logs.archived.list', [
              'filter_month' => request('filter_month', ''),
              'filter_date' => request('filter_date', ''),
              'filter_method' => request('filter_method', ''),
              'filter_keyword' => request('filter_keyword', ''),
              'sort' => 'time',
              'order' => $newOrder,
              'page' => 1
            ]);
          @endphp
          <a href="{{ $sortUrl }}" class="text-decoration-none text-dark">
            時間
            @if($currentSort === 'time')
              @if($currentOrder === 'desc')
                <i class="fa-solid fa-sort-down"></i>
              @else
                <i class="fa-solid fa-sort-up"></i>
              @endif
            @else
              <i class="fa-solid fa-sort text-muted"></i>
            @endif
          </a>
        </td>
        <td class="text-start" style="width: 80px;">Method</td>
        <td class="text-start" style="width: 80px;">狀態</td>
        <td class="text-start">URL</td>
        <td class="text-start" style="width: 120px;">Client IP</td>
        <td class="text-start">備註</td>
        <td class="text-center" style="width: 80px;">操作</td>
      </tr>
    </thead>
    <tbody>
      @if(count($logs) > 0)
        @foreach($logs as $log)
        <tr>
          <td class="text-start">{{ $log['formatted_time'] ?? '' }}</td>
          <td class="text-start">
            @php
              $methodClass = match($log['method'] ?? '') {
                'GET' => 'badge bg-info',
                'POST' => 'badge bg-success',
                'PUT', 'PATCH' => 'badge bg-warning',
                'DELETE' => 'badge bg-danger',
                default => 'badge bg-secondary'
              };
            @endphp
            <span class="{{ $methodClass }}">{{ $log['method'] ?? '' }}</span>
          </td>
          <td class="text-start">
            @php
              $status = $log['status'] ?? '';
              $statusClass = match($status) {
                'success' => 'badge bg-success',
                'error' => 'badge bg-danger',
                'warning' => 'badge bg-warning',
                '' => '',
                default => 'badge bg-secondary'
              };
              $statusText = $status ?: '-';
            @endphp
            @if($statusClass)
              <span class="{{ $statusClass }}">{{ $statusText }}</span>
            @else
              <span class="text-muted">{{ $statusText }}</span>
            @endif
          </td>
          <td class="text-start" title="{{ $log['url'] ?? '' }}">
            <small>{{ $log['short_url'] ?? '' }}</small>
          </td>
          <td class="text-start">{{ $log['client_ip'] ?? '' }}</td>
          <td class="text-start" title="{{ $log['note'] ?? '' }}">
            <small>{{ $log['short_note'] ?? '' }}</small>
          </td>
          <td class="text-center">
            <button
              type="button"
              class="btn btn-sm btn-primary view-detail"
              data-archive-id="{{ $log['archive_id'] ?? '' }}"
              data-bs-toggle="tooltip"
              title="查看詳情">
              <i class="fa-solid fa-eye"></i>
            </button>
          </td>
        </tr>
        @endforeach
      @else
        <tr>
          <td colspan="7" class="text-center">沒有找到日誌記錄</td>
        </tr>
      @endif
    </tbody>
  </table>
</div>

<div class="row">
  <div class="col-sm-6 text-start">
    顯示 {{ count($logs) }} 筆，共 {{ $total }} 筆
  </div>
  <div class="col-sm-6 text-end">
    @if($total_pages > 1)
      <ul class="pagination justify-content-end">
        @if($page > 1)
          <li class="page-item">
            <a class="page-link" href="{{ $pagination_url }}&page={{ $page - 1 }}">上一頁</a>
          </li>
        @endif

        @for($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++)
          <li class="page-item {{ $i == $page ? 'active' : '' }}">
            <a class="page-link" href="{{ $pagination_url }}&page={{ $i }}">{{ $i }}</a>
          </li>
        @endfor

        @if($page < $total_pages)
          <li class="page-item">
            <a class="page-link" href="{{ $pagination_url }}&page={{ $page + 1 }}">下一頁</a>
          </li>
        @endif
      </ul>
    @endif
  </div>
</div>
