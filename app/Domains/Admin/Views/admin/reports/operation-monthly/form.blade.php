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
        <form method="POST" action="{{ route('lang.admin.reports.operation-monthly.rebuild', ['year' => $year, 'month' => $month]) }}" style="display: inline;">
          @csrf
          <button type="submit" class="btn btn-warning" onclick="return confirm('確定要重建此月報表數據嗎？')">
            <i class="fa-solid fa-refresh"></i> 重建數據
          </button>
        </form>
        <a href="{{ route('lang.admin.reports.operation-monthly.export', ['year' => $year, 'month' => $month]) }}" class="btn btn-success">
          <i class="fa-solid fa-download"></i> 匯出 XLSX
        </a>
        <a href="{{ route('lang.admin.reports.operation-monthly.index', ['year' => $year]) }}" class="btn btn-secondary">
          <i class="fa-solid fa-arrow-left"></i> 返回列表
        </a>
      </div>
      <h1>{{ $year }} 年 {{ $month }} 月營運月報</h1>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('lang.admin.dashboard') }}">首頁</a></li>
        <li class="breadcrumb-item"><a href="{{ route('lang.admin.reports.operation-monthly.index') }}">營運月報表</a></li>
        <li class="breadcrumb-item active">{{ $year }}/{{ $month }}</li>
      </ol>
    </div>
  </div>
  <div class="container-fluid">
    <!-- 月度總覽 -->
    <div class="card mb-3">
      <div class="card-header"><i class="fa-solid fa-chart-line"></i> 月度總覽</div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-primary">
              <div class="card-body text-center">
                <h6 class="text-muted">訂單總金額</h6>
                <h3 class="text-primary">{{ number_format($report->order_total_amount, 0) }}</h3>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-info">
              <div class="card-body text-center">
                <h6 class="text-muted">訂單數量</h6>
                <h3 class="text-info">{{ number_format($report->order_count) }}</h3>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-success">
              <div class="card-body text-center">
                <h6 class="text-muted">訂單客戶數</h6>
                <h3 class="text-success">{{ number_format($report->order_customer_count) }}</h3>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-warning">
              <div class="card-body text-center">
                <h6 class="text-muted">新客戶數</h6>
                <h3 class="text-warning">{{ number_format($report->new_customer_count) }}</h3>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-4 col-sm-6 mb-3">
            <div class="card border-secondary">
              <div class="card-body text-center">
                <h6 class="text-muted">進貨總金額</h6>
                <h3 class="text-secondary">{{ number_format($report->purchase_total_amount, 0) }}</h3>
              </div>
            </div>
          </div>
          <div class="col-md-4 col-sm-6 mb-3">
            <div class="card border-danger">
              <div class="card-body text-center">
                <h6 class="text-muted">毛利金額</h6>
                <h3 class="text-danger">{{ number_format($report->gross_profit_amount, 0) }}</h3>
              </div>
            </div>
          </div>
          <div class="col-md-4 col-sm-6 mb-3">
            <div class="card border-dark">
              <div class="card-body text-center">
                <h6 class="text-muted">廠商數量</h6>
                <h3 class="text-dark">{{ number_format($report->supplier_count) }}</h3>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 前十大商品 -->
    <div class="card">
      <div class="card-header"><i class="fa-solid fa-trophy"></i> 前十大商品</div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-hover">
            <thead>
              <tr>
                <th class="text-center" width="80">排名</th>
                <th class="text-start">商品代號</th>
                <th class="text-start">商品名稱</th>
                <th class="text-end">銷售數量</th>
                <th class="text-end">銷售金額</th>
              </tr>
            </thead>
            <tbody>
              @forelse($topProducts as $index => $product)
                <tr>
                  <td class="text-center">
                    @if($index + 1 <= 3)
                      <span class="badge bg-warning">{{ $index + 1 }}</span>
                    @else
                      <span class="badge bg-secondary">{{ $index + 1 }}</span>
                    @endif
                  </td>
                  <td class="text-start">{{ $product->product_code }}</td>
                  <td class="text-start">{{ $product->product_name }}</td>
                  <td class="text-end">{{ number_format($product->quantity, 3) }}</td>
                  <td class="text-end">{{ number_format($product->total_amount, 0) }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center text-muted">無商品銷售數據</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('buttom')
@endsection
