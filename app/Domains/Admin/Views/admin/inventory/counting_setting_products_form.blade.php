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
        <button type="submit" form="form-counting-products" data-bs-toggle="tooltip" title="儲存" class="btn btn-primary"><i class="fa fa-save"></i></button>
      </div>
      <h1>盤點料件設定</h1>
      @include('admin.common.breadcumb')
    </div>
  </div>
    <div class="container-fluid">
      <div class="card">
        <div class="card-header"><i class="fas fa-pencil-alt"></i> 盤點料件設定</div>
          <div class="card-body">
            <form id="form-counting-products" method="post" action="{{ $save_url }}" data-oc-toggle="ajax">
              @csrf
              @method('POST')
              <div class="table-responsive">
                <table class="table table-bordered table-hover">
                  <thead>
                    <tr>
                      <td class="text-start">料件</td>
                      <td class="text-start">存放溫度類型</td>
                      <td class="text-start">排序</td>
                    </tr>
                  </thead>
                  <tbody>
                    @php $product_row = 1; @endphp
                    @foreach($countingSetting->products as $key => $row)
                    <tr data-product_row="{{ $product_row }}">
                      <td class="text-start" style="padding-left: 1px;">
                        <div class="container input-group col-sm-12" style="padding-left: 1px;">
                          <div class="col-sm-3">
                            <input type="text" id="input-products-id-{{ $product_row }}" name="products[{{ $product_row }}][product_id]" value="{{ $row->product_id }}" class="form-control" readonly="">
                          </div>
                          <div class="col-sm-8">
                            <input type="text" id="input-products-name-{{ $product_row }}" name="products[{{ $product_row }}][product_name]" value="{{ $row->product_name }}" data-rownum="1" class="form-control schProductName" data-oc-target="autocomplete-product_name-1" autocomplete="off">
                            <ul id="autocomplete-product_name-{{ $product_row }}" class="dropdown-menu"></ul>
                          </div>
                        </div>
                      </td>
                      <td class="text-start">
                              <select id="input-temperature_type_code" name="products[{{ $product_row }}][temperature_type_code]" class="form-control">
                                <option value="">--</option>
                                @foreach($temperature_types as $code => $temperature_type)
                                <option value="{{ $temperature_type->code }}" @if($temperature_type->code==$row->temperature_type_code) selected @endif>{{ $temperature_type->name }}</option>
                                @endforeach
                              </select>
                      </td>
                      <td class="text-start">
                        <input type="text" id="input-products-sort_order-{{ $product_row }}" name="products[{{ $product_row }}][sort_order]" value="{{ $row->sort_order }}" class="form-control">
                      </td>
                    </tr>
                      @php $product_row++; @endphp
                    @endforeach
                  </tbody>

                </table>
              </div>

            </form>
          </div>
          </div>
      </div>
    </div>
</div>
@endsection

@section('buttom')

@endsection
