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
        <a href="{{ $add_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_add }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i></a>
        <button type="submit" form="form-option" formaction="{{ $delete_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_delete }}" onclick="return confirm('{{ $lang->text_confirm }}');" class="btn btn-danger"><i class="fa-regular fa-trash-can"></i></button>
      </div>
	  <h1>{{ $lang->heading_title }}</h1>
      @include('admin.common.breadcumb')
    </div>
  </div>
  <div class="container-fluid">
    <div class="card">
      <div class="card-header"><i class="fas fa-list"></i> {{ $lang->text_list }}</div>
      <div id="option" class="card-body">{!! $list !!}</div>
    </div>
  </div>
</div>
@endsection

@section('buttom')
<script type="text/javascript"><!--
$('#option').on('click', 'thead a, .pagination a', function(e) {
  e.preventDefault();

  $('#option').load(this.href);
});
//--></script>
@endsection