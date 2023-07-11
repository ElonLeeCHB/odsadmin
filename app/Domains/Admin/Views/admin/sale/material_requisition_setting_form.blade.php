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
          {{-- <a href="javascript:void(0)" data-bs-toggle="tooltip" title="Orders" class="btn btn-warning"><i class="fas fa-receipt"></i></a> --}}
        {{-- <button type="submit" form="form-member" data-bs-toggle="tooltip" title="Save" class="btn btn-primary"><i class="fas fa-save"></i></button> --}}
        <button type="submit" form="form-member" data-bs-toggle="tooltip" title="儲存" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="{{ $back }}" data-bs-toggle="tooltip" title="Back" class="btn btn-light"><i class="fas fa-reply"></i></a>
      </div>
      <h1>{{ $lang->text_material_requisition_setting }}</h1>
      @include('admin.common.breadcumb')
    </div>
  </div>
  <div class="container-fluid">
    <div class="card">
      <div class="card-header"><i class="fas fa-pencil-alt"></i> {{ $lang->text_form }}</div>
      <div class="card-body">
        <ul class="nav nav-tabs">
            <li class="nav-item"><a href="#tab-general" data-bs-toggle="tab" class="nav-link active">{{ $lang->tab_general }}</a></li>
            <!--<li class="nav-item"><a href="#tab-address" data-bs-toggle="tab" class="nav-link">{{ $lang->tab_address }}</a></li>-->
        </ul>
        <form id="form-member" action="{{ $save }}" method="post" data-oc-toggle="ajax">
          @csrf
          @method('POST')
          <div class="tab-content">
            <div id="tab-general" class="tab-pane active">

              <div class="row mb-3">
                <label class="col-sm-2 col-form-label">備料項目</label>
                <div class="col-sm-10">
                  <div class="input-group">
                    <?php /* 下面 taxearea 的內容請注意是否會產生多餘空格 */ ?>
                    <textarea style="width:300px;height:500px;" id="input-sales_saleable_product_ingredients" name="sales_saleable_product_ingredients">{{ $sales_saleable_product_ingredients }}</textarea>
                  </div>
                  <div class="form-text">(格式：代號,名稱)</div>
                  <div id="error-code" class="invalid-feedback"></div>
                </div>
              </div>
              
              <div class="row mb-3">
                <label class="col-sm-2 col-form-label">需要除2的潤餅</label>
                <div class="col-sm-10">
                  <div class="input-group">
                    <?php /* 下面 taxearea 的內容請注意是否會產生多餘空格 */ ?>
                    <textarea style="width:300px;height:250px;" id="input-sales_burrito_half_of_6_inch" name="sales_burrito_half_of_6_inch">{{ $sales_burrito_half_of_6_inch }}</textarea>
                  </div>
                  <div class="form-text">(格式：代號,名稱)</div>
                  <div id="error-code" class="invalid-feedback"></div>
                </div>
              </div>
              
              <div class="row mb-3">
                <label class="col-sm-2 col-form-label">訂單備料表的顯示項目</label>
                <div class="col-sm-10">
                  <div class="input-group">
                    <?php /* 下面 taxearea 的內容請注意是否會產生多餘空格 */ ?>
                    <textarea style="width:300px;height:500px;" id="input-sales_ingredients_table_items" name="sales_ingredients_table_items">{{ $sales_ingredients_table_items }}</textarea>
                  </div>
                  <div class="form-text">(格式：代號,名稱)</div>
                  <div id="error-code" class="invalid-feedback"></div>
                </div>
              </div>


            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@section('buttom')
<script>

</script>
@endsection
