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
        <button type="submit" form="form-counting" data-bs-toggle="tooltip" title="{{ $lang->button_save }}" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="{{ $back_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_back }}" class="btn btn-light"><i class="fa-solid fa-reply"></i></a>
      </div>
      <h1>{{ $lang->heading_title }}</h1>
      @include('admin.common.breadcumb')
    </div>
  </div>
  <div class="container-fluid">
    <div class="card">
      <?php /*<div class="card-header"><i class="fa-solid fa-pencil"></i> {{ $lang->text_form }}</div>*/ ?>
      <div class="card-body">




        <form id="form-counting" action="{{ $save_url }}" method="post" data-oc-toggle="ajax" enctype="multipart/form-data">
            @csrf
            @method('POST')
          <ul class="nav nav-tabs">
          <li class="nav-item"><a href="#tab-general" data-bs-toggle="tab" class="nav-link active">{{ $lang->tab_general }}</a></li>
          </ul>
          <div class="tab-content">
            <div id="tab-general" class="tab-pane active">


              <div class="row mb-3">
                <label class="col-sm-2 col-form-label">匯入檔案</label>
                <div class="col-sm-10">
                  <div class="input-group">
                    <button type="button" id="btn-import" data-bs-toggle="tooltip" data-loading-text="Loading..." title="匯入檔案" class="btn btn-info" 
                        aria-label="匯入檔案">上傳</button>
                  </div>
                  <div class="form-text">若有匯入檔案，本次作業所有內容會以此檔作更新，請注意！</div>
                  <div id="error-code" class="invalid-feedback"></div>
                </div>
              </div>

              {{-- column_code--}}
              <div class="row mb-3">
                <label class="col-sm-2 col-form-label">{{ $lang->column_code }}</label>
                <div class="col-sm-10">
                  <div class="input-group">
                  <input type="text" id="input-code" name="code" value="{{ $counting->code }}" data-oc-target="autocomplete-code" class="form-control" @if(!empty($counting->code)) readonly @endif />
                  <input type="text" id="input-counting_id" name="counting_id" value="{{ $counting->counting_id }}" placeholder="盤點單的流水號" class="form-control" readonly/>
                  </div>
                  <div class="form-text"></div>
                  <div id="error-code" class="invalid-feedback"></div>
                </div>
              </div>
                
              {{-- column_task_date--}}
              <div class="row mb-3 required">
                <label class="col-sm-2 col-form-label">{{ $lang->column_task_date }}</label>
                <div class="col-sm-10">
                  <div class="input-group">
                    <input type="text" id="input-task_date" name="task_date" value="{{ $counting->task_date }}" class="form-control" />
                  </div>
                  <div class="form-text">(識別碼有可能用在程式裡面。請小心設定。)</div>
                  <div id="error-code" class="invalid-feedback"></div>
                </div>
              </div>

              {{-- comment --}}
              <div class="row mb-3">
                <label for="input-comment" class="col-sm-2 col-form-label">備註</label>
                <div class="col-sm-10">
                  <div class="input-group">
                    <input type="text" id="input-comment" name="comment" value="{{ $counting->comment }}" class="form-control">
                  </div>
                  <div id="error-comment" class="invalid-feedback"></div>
                </div>
              </div>









              @php $product_row = 1; @endphp
              <div class="table-responsive">
                <table id="products" class="table table-striped table-bordered table-hover">
                  <thead>
                    <tr>
                      <td class="text-left"></td>
                      <td class="text-left">流水號</td>
                      <td class="text-left">品名</td>
                      <td class="text-left">規格</td>
                      <td class="text-left" style="width:100px;">庫存單位</td>
                      <td class="text-left" style="width:100px;">盤點單位</td>
                      <td class="text-left" style="width:100px;">盤點數量</td>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($counting_products as $counting_product)
                    <tr id="product-row{{ $product_row }}" data-rownum="{{ $product_row }}">
                      <td class="text-left">
                        <button type="button" onclick="$('#product-row{{ $product_row }}').remove();" data-toggle="tooltip" title="" class="btn btn-danger" data-original-title="Remove"><i class="fa fa-minus-circle"></i></button>
                      </td>
                      <td class="text-left">
                        <input type="text" id="input-products-product_id-{{ $product_row }}" name="products[{{ $product_row }}][product_id]" value="{{ $counting_product->product_id ?? '' }}" data-rownum="{{ $product_row }}" class="form-control" readonly>
                        <ul id="autocomplete-product_name-{{ $product_row }}" class="dropdown-menu"></ul>
                        <input type="hidden" id="input-products-id-{{ $product_row }}" name="products[{{ $product_row }}][id]" value="{{ $counting_product->product_id ?? '' }}" class="form-control" readonly>
                      </td>
                      <td class="text-left">
                        <input type="text" id="input-products-name-{{ $product_row }}" name="products[{{ $product_row }}][name]" value="{{ $counting_product->product_name ?? '' }}" data-rownum="{{ $product_row }}" class="form-control schProductName" data-oc-target="autocomplete-product_name-{{ $product_row }}" autocomplete="off">
                        <ul id="autocomplete-product_name-{{ $product_row }}" class="dropdown-menu"></ul>
                        <input type="hidden" id="input-products-id-{{ $product_row }}" name="products[{{ $product_row }}][id]" value="{{ $counting_product->product_id ?? '' }}" class="form-control" readonly>
                      </td>
                      <td class="text-left">
                        <input type="text" id="input-products-specification-{{ $product_row }}" name="products[{{ $product_row }}][specification]" value="{{ $counting_product->product_specification ?? '' }}" class="form-control" readonly>
                      </td>
                      <td class="text-left">
                        <input type="text" id="input-products-price-{{ $product_row }}" name="products[{{ $product_row }}][price]" value="{{ $counting_product->price ?? 0 }}" class="form-control productPriceInputs clcProduct" data-rownum="{{ $product_row }}">
                      </td>
                      <td class="text-left">
                        <input type="text" id="input-products-receiving_quantity-{{ $product_row }}" name="products[{{ $product_row }}][receiving_quantity]" value="{{ $counting_product->receiving_quantity }}" class="form-control productPriceInputs clcProduct" data-rownum="{{ $product_row }}">
                      </td>
                      <td class="text-left">
                        <input type="text" id="input-products-amount-{{ $product_row }}" name="products[{{ $product_row }}][amount]" value="{{ $counting_product->amount ?? 0 }}" class="form-control productAmountInputs clcProduct" data-rownum="{{ $product_row }}" readonly>
                      </td>
                    </tr>
                    @php $product_row++; @endphp
                    @endforeach
                  </tbody>
                  <tfoot>
                    <tr>
                      <td colspan="10" class="text-left">
                        <button type="button" onclick="addReceivingProduct()" data-toggle="tooltip" title="" class="btn btn-primary" data-original-title=""><i class="fa fa-plus-circle"></i></button>
                      </td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>


<input type="hidden" id="input-trigger-upload" data-oc-toggle="upload" data-oc-url="{{ $import_url }}" >
@endsection

@section('buttom')
<script type="text/javascript">

var current_url = window.location.href;
var path_url = current_url.split('?')[0];
var query_url = current_url.split('?')[1];
//console.log('path_url='+path_url+', query_url='+query_url);

// 拆解網址路徑
var parts = path_url.split('/');
var secondToLastPart = parts[parts.length - 2];
var lastPart = parts[parts.length - 1];

if(secondToLastPart == 'form' && $.isNumeric(lastPart)){
  import_url = currentURL;
}else if(lastPart == 'form'){
  import_url = 
}

//var parts = currentURL.split('?')[0].split('/'); //問號之前的網址，以 / 分隔



// var default_import_url = "{{ $import_url }}";
// var import_url = '';
// var import_url_without_counting_id = '';
// //var result = parts.pop(); // 最後一部份
// var secondToLastPart = parts[parts.length - 2];
// var lastPart = parts[parts.length - 1];

// if(secondToLastPart == 'form' && $.isNumeric(lastPart)){
//   import_url = currentURL;
// }else if(lastPart == 'form'){
//   import_url_without_counting_id = 123;
// }




// console.log('secondToLastPart='+secondToLastPart+', lastPart='+lastPart);


// $(document).ready(function() {

//   $('#btn-import').on('click', function(){
//     let counting_id =  $('#input-counting_id').val();
//     let import_url = original_import_url + '/' + counting_id;
// alert(import_url)
//     // $('#input-trigger-upload').data('oc-url', import_url);
//     // $('#input-trigger-upload').trigger('click');
//   });



// });
</script>
@endsection