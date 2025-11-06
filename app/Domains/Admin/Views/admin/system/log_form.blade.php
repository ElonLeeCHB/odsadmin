@extends('admin.app')

@section('pageJsCss')
<style>
  pre {
    background-color: #f4f4f4;
    padding: 15px;
    border-radius: 5px;
    border: 1px solid #ddd;
    overflow-x: auto;
  }
  .info-table th {
    width: 150px;
    background-color: #f8f9fa;
  }
</style>
@endsection

@section('columnLeft')
	@include('admin.common.column_left')
@endsection

@section('content')
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="float-end">
        <button type="button" onclick="window.close();" class="btn btn-light"><i class="fa-solid fa-times"></i> 關閉</button>
      </div>
      <h1>日誌詳情</h1>
      @include('admin.common.breadcumb')
    </div>
  </div>
  <div class="container-fluid">
    <div class="card">
      <div class="card-header"><i class="fa-solid fa-info-circle"></i> 詳細資訊</div>
      <div class="card-body">

        <table class="table table-bordered info-table">
          <tbody>
            <tr>
              <th>時間戳記</th>
              <td>{{ $log['timestamp'] ?? '' }}</td>
            </tr>
            <tr>
              <th>唯一 ID</th>
              <td><code>{{ $log['uniqueid'] ?? '' }}</code></td>
            </tr>
            <tr>
              <th>環境</th>
              <td>
                @php
                  $areaClass = match($log['area'] ?? '') {
                    'production' => 'badge bg-danger',
                    'staging' => 'badge bg-warning',
                    'local' => 'badge bg-info',
                    default => 'badge bg-secondary'
                  };
                @endphp
                <span class="{{ $areaClass }}">{{ $log['area'] ?? '' }}</span>
              </td>
            </tr>
            <tr>
              <th>HTTP Method</th>
              <td>
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
            </tr>
            <tr>
              <th>URL</th>
              <td><code>{{ $log['url'] ?? '' }}</code></td>
            </tr>
            <tr>
              <th>客戶端 IP</th>
              <td><code>{{ $log['client_ip'] ?? '' }}</code></td>
            </tr>
            <tr>
              <th>API 伺服器 IP</th>
              <td><code>{{ $log['api_ip'] ?? '' }}</code></td>
            </tr>
            <tr>
              <th>狀態</th>
              <td>{{ $log['status'] ?? '(無)' }}</td>
            </tr>
            <tr>
              <th>備註</th>
              <td>{{ $log['note'] ?? '' }}</td>
            </tr>
          </tbody>
        </table>

        <h5 class="mt-4"><i class="fa-solid fa-database"></i> 請求資料</h5>
        <pre>{{ json_encode($log['data'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>

        <h5 class="mt-4"><i class="fa-solid fa-code"></i> 完整 JSON</h5>
        <pre>{{ $log_json }}</pre>

      </div>
    </div>
  </div>
</div>
@endsection
