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
        <button type="button" data-bs-toggle="tooltip" title="刪除" class="btn btn-danger" onclick="confirm('確定要刪除嗎？') && $('#form-store').submit();"><i class="fa-regular fa-trash-can"></i></button>
        <a href="{{ $add_url }}" data-bs-toggle="tooltip" title="新增" class="btn btn-primary"><i class="fa fa-plus"></i></a>
      </div>
      <h1>門市管理</h1>
      @include('admin.common.breadcumb')
    </div>
  </div>
  <div class="container-fluid">
    <div class="card">
      <div class="card-header"><i class="fa-solid fa-list"></i> 門市列表</div>
      <div id="store" class="card-body">
        {!! $list !!}
      </div>
    </div>
  </div>
</div>
@endsection

@section('buttom')
<script type="text/javascript">
$('#store').on('click', 'thead a, .pagination a', function(e) {
    e.preventDefault();

    $('#store').load(this.href);
});
</script>
@endsection
