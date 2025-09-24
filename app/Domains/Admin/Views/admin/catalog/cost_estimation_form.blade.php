@extends('admin.app')

@section('pageJsCss')
<link  href="{{ asset('assets2/public/vendor/select2/select2.min.css') }}" rel="stylesheet" type="text/css"/>
<script src="{{ asset('assets2/public/vendor/select2/select2.min.js') }}"></script>

<style>
.select2-container .select2-selection--single{
   height:100% !important;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
     height:100% !important;
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
      </div>
      <h1>{{ $lang->heading_title }}</h1>
      @include('admin.common.breadcumb')
    </div>
  </div>

  <div class="container-fluid">
    <div class="card">
      <div class="card-header"><i class="fa-solid fa-pencil"></i> 編輯</div>
      <div class="card-body">
        <form id="form-option" action="http://ods.dtstw.com/laravel/zh-Hant/backend/catalog/options/save" method="post" data-oc-toggle="ajax">
          <input type="hidden" name="_token" value="RrMAHb9tVmijoiagwVmCcSLMfM8kDiCBUJfqNKkl" autocomplete="off">          <input type="hidden" name="_method" value="POST">          <input type="hidden" name="option_id" value="1027" id="input-option-id">
          <fieldset>
            <legend>選項</legend>

            <div class="row mb-3 required">
              <label class="col-sm-2 col-form-label">選項名稱</label>
              <div class="col-sm-10">
                                  <input type="hidden" name="option_translations[zh_Hant][id]" value="1024">

                  <div class="input-group">
                    <div class="input-group-text">中文</div>
                    <input type="text" name="option_translations[zh_Hant][name]" value="口味" id="input-name-zh_Hant" class="form-control">
                  </div>
                  <div id="error-name-zh_Hant" class="invalid-feedback"></div>
                              </div>
            </div>

            <div class="row mb-3">
              <label for="input-type" class="col-sm-2 col-form-label">類型</label>
              <div class="col-sm-10">
              <select name="type" id="input-type" class="form-select">
                  <optgroup label="選擇">
                    <option value="select">選項(select)</option>
                    <option value="radio" selected="">單選(radion)</option>
                    <option value="checkbox">複選框(checkbox)</option>
                    <option value="options_with_qty">複選 &amp; 數量</option>

                  </optgroup>
                  <optgroup label="文字框">
                    <option value="text">文字</option>
                    <option value="textarea">多行文字框</option>
                  </optgroup>
                  <optgroup label="檔案">
                    <option value="file">檔案</option>
                  </optgroup>
                  <optgroup label="日期">
                    <option value="date">日期</option>
                    <option value="time">時間</option>
                    <option value="datetime">日期 &amp; 時間</option>
                  </optgroup>
                </select>
              </div>
            </div>
            
            <div class="row mb-3">
              <label for="input-code" class="col-sm-2 col-form-label">編碼</label>
              <div class="col-sm-10">
                <input type="text" name="code" value="" placeholder="本欄請由開發人員修改" id="input-code" class="form-control">
              </div>
            </div>

            <div class="row mb-3">
              <label for="input-note" class="col-sm-2 col-form-label">備註</label>
              <div class="col-sm-10">
                <input type="text" id="input-note" name="note" value="" placeholder="備註" class="form-control">
              </div>
            </div>
          </fieldset>

          
          <fieldset>
            <legend>項目</legend>
            <table id="option-value" class="table table-bordered table-hover">
              <thead>
                <tr>
                  <td>項次</td>
                  <td>選項名稱</td>
                  <td>選項值</td>
                  <td>成本</td>
                </tr>
              </thead>
              <tbody>
                @php $i = 1; @endphp
                @foreach ($product->productOptions as $productOption)
                  
                <tr>
                  <td>{{ $i }}</td>
                  <td>{{ $productOption->name ?? ''}}</td>
                  <td class="w-auto">
                    <table class="table table-bordered" style="width: 100%;">
                      @foreach ($productOption->productOptionValues as $productOptionValue)
                      @if ($productOptionValue->is_active != 1) @continue; @endif
                          <tr>
                              <td>
                                  <input type="checkbox" 
                                          name="option_value[{{ $i }}][option_value_id][]" 
                                          value="{{ $productOptionValue->option_value_id }}"
                                          {{ $productOptionValue->is_default ? 'checked' : '' }}>
                                  {{ $productOptionValue->name ?? '' }}
                              </td>

                              <td>
                                  <div class="input-group">
                                      <span class="input-group-text">對應料件</span>
                                      <input type="text" readonly
                                            name="option_value[{{ $i }}][quantity][{{ $productOptionValue->option_value_id }}]" 
                                            class="form-control"
                                            style="width: 100px;"
                                            min="0" 
                                            value="{{ $productOptionValue->materialProduct->id }}-{{ $productOptionValue->materialProduct->name }}">
                                  </div>
                              </td>

                              <td>
                                  <div class="input-group">
                                      <span class="input-group-text">單位成本</span>
                                      <input type="text" 
                                            name="option_value[{{ $i }}][quantity][{{ $productOptionValue->option_value_id }}]" 
                                            class="form-control"
                                            style="width: 100px;"
                                            min="0" 
                                            value="1">
                                  </div>
                              </td>

                              {{-- ✅ 第三欄：數量 --}}
                              <td>
                                  <div class="input-group">
                                      <span class="input-group-text">數量</span>
                                      <input type="text" 
                                            name="option_value[{{ $i }}][quantity][{{ $productOptionValue->option_value_id }}]" 
                                            class="form-control"
                                            style="width: 60px;"
                                            min="0" 
                                            value="1">
                                  </div>
                              </td>

                              {{-- ✅ 第四欄：金額 --}}
                              <td>成本：<span>0</span>
                              </td>
                          </tr>
                      @endforeach
                    </table>
                  </td>

                  <td>  </td>
                </tr>
                @php $i++; @endphp
                @endforeach
              </tbody

              <tfoot>
                <tr>
                  <td colspan="3"></td>
                  <td class="text-end"><button type="button" onclick="addOptionValue();" data-bs-toggle="tooltip" title="新增項目" class="btn btn-primary"><i class="fa-solid fa-plus-circle"></i></button></td>
                </tr>
              </tfoot>
            </table>
          </fieldset>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@section('buttom')
  <script type="text/javascript">

</script>
@endsection
