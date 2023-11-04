@extends('admin.app')

@section('pageJsCss')
  <script src="{{ asset('assets/vendor/moment-with-locales.js') }}" type="text/javascript"></script>
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
                  <div class="form-text"></div>
                  <div id="error-code" class="invalid-feedback"></div>
                </div>
              </div>

              {{-- column_form_code--}}
              <div class="row mb-3">
                <label class="col-sm-2 col-form-label">{{ $lang->column_form_code }}</label>
                <div class="col-sm-10">
                  <div class="input-group">
                  <input type="text" id="input-code" name="code" value="{{ $counting->code }}" data-oc-target="autocomplete-code" placeholder="單號" class="form-control" readonly/>
                  <input type="text" id="input-counting_id" name="counting_id" value="{{ $counting_id }}" placeholder="流水號" class="form-control" readonly/>
                  </div>
                  <div class="form-text"></div>
                  <div id="error-code" class="invalid-feedback"></div>
                </div>
              </div>
                
              {{-- column_form_date--}}
              <div class="row mb-3 required">
                <label class="col-sm-2 col-form-label">{{ $lang->column_form_date }}</label>
                <div class="col-sm-10">
                  <div class="input-group">
                    <input type="text" id="input-form_date" name="form_date" value="{{ $counting->form_date_ymd }}" class="form-control date" />
                  </div>
                  <div class="form-text"></div>
                  <div id="error-code" class="invalid-feedback"></div>
                </div>
              </div>

              <div class="row mb-3">
                <label class="col-sm-2 col-form-label">總金額</label>
                <div class="col-sm-10">
                  <div class="input-group">
                    <input type="text" id="input-total" name="total" value="{{ $counting->total }}" class="form-control" />
                  </div>
                  <div class="form-text"></div>
                  <div id="error-total" class="invalid-feedback"></div>
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

              <div id="counting_products_wrapper" class="table-responsive">
              {!! $counting_product_list !!}
              </div>

            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<input type="hidden" id="input-trigger-upload" data-oc-toggle="readExcel" data-oc-url="{{ $import_url }}" >

@endsection

@section('buttom')
<script type="text/javascript">
var current_url = window.location.href;
var path_url = current_url.split('?')[0];
var query_url = current_url.split('?')[1];
var product_row = {{ $product_row }};

// 查料件名稱
$(document).on('click', '.schProductName', function() {
  $(this).autocomplete({
    'source': function (request, response) {
      let supplier_id = $('#input-supplier_id').val();
      let supplier_url = '';

      if(request.length == 0 && $.isNumeric(supplier_id) && supplier_id > 0){
        supplier_url = '&equal_supplier_id=' + supplier_id + '&limit=0&pagination=0';
      }
      $.ajax({
          url: "{{ $product_autocomplete_url }}?equal_is_inventory_managed=1&equal_is_active=1&with=product_units&filter_name=" + encodeURIComponent(request) + '&extra_columns=stock_unit_code,stock_unit_name,counting_unit_name' + supplier_url,
          dataType: 'json',
          success: function (json) {
            response(json);
          }
        });
    },
    'select': function (item) {
      var rownum = $(this).data("rownum");
      $('#input-products-id-'+rownum).val(item.product_id);
      $('#input-products-name-'+rownum).val(item.name);
      $('#input-products-specification-'+rownum).val(item.specification);
      $('#input-products-stock_unit_code-'+rownum).val(item.stock_unit_code);
      $('#input-products-stock_unit_name-'+rownum).val(item.stock_unit_name);
      $('#input-products-unit_name-'+rownum).val(item.counting_unit_name);

      var selectElement = $('#input-products-receiving_unit_code-'+rownum);
      selectElement.empty();

      $.each(item.product_units, function(index, product_unit) {
        var option = $('<option></option>');

        option.val(product_unit.source_unit_code);
        option.text(product_unit.source_unit_name);
        option.attr('data-multiplier', product_unit.destination_quantity);
        //console.log('unit.source_unit_code='+unit.source_unit_code+', unit.source_unit_name='+unit.source_unit_name+', unit.destination_quantity='+unit.destination_quantity)

        selectElement.append(option);
      });
    }
  });
});


$('#btn-import').on('click', function(){
  let counting_id = $('#input-counting_id').val();
  let import_url = path_url + '/' + counting_id; //若是新增單據，檔案上傳後會取得新id，要跟著變化，若連續上傳(匯入)，要更新到同一張單據。
  //let today = moment().format('YYYY-MM-DD');
  $('#input-trigger-upload').data('oc-url', import_url);
  $('#input-trigger-upload').trigger('click');
});

// 單價、數量 觸發計算
$('#counting_products_wrapper').on('focusout', '.clcProduct', function(){
  let rownum = $(this).closest('[data-rownum]').data('rownum');
  calcProduct(rownum)
});
function calcProduct(rownum){
  let price = $('#input-products-price-'+rownum).val() ?? 0;
  let quantity = $('#input-products-quantity-'+rownum).val() ?? 0;
  let amount = price * quantity;
  
  quantity = $('#input-products-amount-'+rownum).val(amount);
  //console.log('price='+price+', quantity='+quantity+', amount='+amount);
  sumTotal();
}

function sumTotal(){
  let counting_amount = 0;

  $('.productAmountInputs').each(function() {
    var inputValue = parseFloat($(this).val()) || 0;
    counting_amount += inputValue;
  });

  $('#input-total').val(counting_amount);
}


// Read Excel
$(document).on('click', '[data-oc-toggle=\'readExcel\']', function () {
    var element = this;

    if (!$(element).prop('disabled')) {
        $('#form-upload').remove();

        $('body').prepend('<form enctype="multipart/form-data" id="form-upload" style="display: none;"><input type="file" name="file" value=""/></form>');

        $('#form-upload input[name=\'file\']').trigger('click');

        $('#form-upload input[name=\'file\']').on('change', function (e) {
            if ((this.files[0].size / 1024) > $(element).attr('data-oc-size-max')) {
                alert($(element).attr('data-oc-size-error'));

                $(this).val('');
            }
        });

        if (typeof timer != 'undefined') {
            clearInterval(timer);
        }

        var timer = setInterval(function () {
            if ($('#form-upload input[name=\'file\']').val() != '') {
                clearInterval(timer);

                $.ajax({
                    url: $(element).attr('data-oc-url'),
                    type: 'post',
                    data: new FormData($('#form-upload')[0]),
                    dataType: 'html',
                    cache: false,
                    contentType: false,
                    processData: false,

                    beforeSend: function () {
                        $(element).prop('disabled', true).addClass('loading');
                    },
                    complete: function () {
                        $(element).prop('disabled', false).removeClass('loading');
                    },
                    success: function (response) {
                        $('#counting_products_wrapper').html(response);
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                    }
                });
            }
        }, 500);
    }
});


function addCountingProduct(){
  let html = '';
  html += '<tr id="product-row'+product_row +'" data-rownum="'+product_row +'">';
  html += '  <td class="text-left">';
  html += '    <button type="button" onclick="$(\'#product-row'+product_row +'\').remove();" data-toggle="tooltip" title="" class="btn btn-danger" data-original-title="Remove"><i class="fa fa-minus-circle"></i></button>';
  html += '  </td>';
  html += '  <td class="text-right">'+product_row +'</td>';
  html += '  <td class="text-left">';
  html += '    <input type="text" id="input-products-id-'+product_row +'" name="products['+product_row +'][id]" value="" data-rownum="'+product_row +'" class="form-control" readonly>';
  html += '  </td>';
  html += '  <td class="text-left">';
  html += '    <input type="text" id="input-products-name-'+product_row +'" name="products['+product_row +'][name]" value="" data-rownum="'+product_row +'" class="form-control schProductName" data-oc-target="autocomplete-product_name-'+product_row +'" autocomplete="off">';
  html += '    <ul id="autocomplete-product_name-'+product_row +'" class="dropdown-menu"></ul>';
  html += '  </td>';
  html += '  <td class="text-left">';
  html += '    <input type="text" id="input-products-specification-'+product_row +'" name="products['+product_row +'][specification]" value="" class="form-control" readonly>';
  html += '  </td>';
  html += '  <td class="text-left">';
  html += '    <input type="text" id="input-products-stock_unit_name-'+product_row +'" name="products['+product_row +'][stock_unit_name]" value="" class="form-control" readonly>';
  html += '  </td>';
  html += '  <td class="text-left">';
  html += '    <input type="text" id="input-products-unit_name-'+product_row +'" name="products['+product_row +'][unit_name]" value="" class="form-control" readonly>';
  html += '  </td>';
  html += '  <td class="text-left">';
  html += '    <input type="text" id="input-products-price-'+product_row +'" name="products['+product_row +'][price]" value="" class="form-control productPriceInputs clcProduct" data-rownum="'+product_row +'">';
  html += '  </td>';
  html += '  <td class="text-left">';
  html += '    <input type="text" id="input-products-quantity-'+product_row +'" name="products['+product_row +'][quantity]" value="" class="form-control productPriceInputs clcProduct" data-rownum="'+product_row +'">';
  html += '  </td>';
  html += '  <td class="text-left">';
  html += '    <input type="text" id="input-products-amount-'+product_row +'" name="products['+product_row +'][amount]" value="" class="form-control productAmountInputs" data-rownum="'+product_row +'" readonly>';
  html += '  </td>';
  html += '</tr>';

  $('#products tbody').append(html);

  product_row++;
}
</script>
@endsection