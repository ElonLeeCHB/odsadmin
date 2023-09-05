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
        <button type="submit" form="form-order_schedule" data-bs-toggle="tooltip" title="儲存" class="btn btn-primary"><i class="fa fa-save"></i></button>
      </div>
	    <h1>{{ $lang->heading_title }}</h1>
      @include('admin.common.breadcumb')
    </div>
  </div>
  <div class="container-fluid">
    <div class="card">
      <div class="card-header">
        <div class="row">
        
          <label for="input-required_date" class="col-sm-1 col-form-label">{{ $lang->column_delivery_date }}</label>
          <div class="col-sm-3">
            <div class="input-group">
              <input type="text" id="input-required_date" name="required_date" value="{{ $delivery_date }}" class="form-control date"/>
              <input type="hidden" id="input-required_date_2ymd" name="required_date_2ymd" value="{{ $delivery_date_2ymd }}">
              <div class="input-group-text"><i class="fa-regular fa-calendar"></i></div>
              <button type="button" id="button-search" class="btn btn-primary btn-sm float-end" data-bs-toggle="tooltip" title="查詢" >查詢</button>
            </div>
            <div id="error-required_date" class="invalid-feedback"></div>
          </div>
        </div>
      </div>
      <div id="schedule" class="card-body">{!! $list !!}</div>
    </div>
  </div>
</div>
@endsection

@section('buttom')
<script type="text/javascript">
$('#schedule').on('click', 'thead a, .pagination a', function(e) {
  e.preventDefault();

  $('#schedule').load(this.href);
});

$(function(){
  $('#button-search').on('click', function() {
    let delivery_date = $('#input-required_date').val();

    window.location.href = "{{ $index_url }}/" + delivery_date;;
  });
});

</script>
@endsection