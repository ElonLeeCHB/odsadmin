<div class="table-responsive">
  <table class="table table-bordered table-hover">
    <thead>
      <tr>
        <th class="text-center">月份</th>
        <th class="text-end">訂單總金額</th>
        <th class="text-end">訂單數量</th>
        <th class="text-end">訂單客戶數</th>
        <th class="text-end">新客戶數</th>
        <th class="text-end">進貨總金額</th>
        <th class="text-end">廠商數量</th>
        <th class="text-center">操作</th>
      </tr>
    </thead>
    <tbody>
      @for($m = 1; $m <= 12; $m++)
        @php
          $report = $reports->firstWhere('month', $m);
        @endphp
        <tr>
          <td class="text-center">{{ $m }} 月</td>
          @if($report)
            <td class="text-end">{{ number_format($report->order_total_amount, 0) }}</td>
            <td class="text-end">{{ number_format($report->order_count) }}</td>
            <td class="text-end">{{ number_format($report->order_customer_count) }}</td>
            <td class="text-end">{{ number_format($report->new_customer_count) }}</td>
            <td class="text-end">{{ number_format($report->purchase_total_amount, 0) }}</td>
            <td class="text-end">{{ number_format($report->supplier_count) }}</td>
            <td class="text-center">
              <a href="{{ route('lang.admin.reports.operation-monthly.show', ['year' => $year, 'month' => $m]) }}" class="btn btn-sm btn-primary" title="查看詳情">
                <i class="fa-solid fa-eye"></i>
              </a>
            </td>
          @else
            <td colspan="6" class="text-center text-muted">尚無數據</td>
            <td class="text-center">-</td>
          @endif
        </tr>
      @endfor
    </tbody>
  </table>
</div>
