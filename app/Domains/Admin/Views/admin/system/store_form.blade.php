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
        <button type="submit" form="form-store" data-bs-toggle="tooltip" title="儲存" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="{{ $back }}" data-bs-toggle="tooltip" title="{{ $lang->button_back ?? '返回' }}" class="btn btn-light"><i class="fas fa-reply"></i></a>
      </div>
      <h1>門市管理</h1>
      @include('admin.common.breadcumb')
    </div>
  </div>
    <div class="container-fluid">
      <div class="card">
        <div class="card-header"><i class="fas fa-pencil-alt"></i> {{ $lang->text_form ?? '表單' }}</div>
        <div class="card-body">
          <form id="form-store" action="{{ $save }}" method="post" data-oc-toggle="ajax">
            @csrf
            @method('POST')

            <div class="tab-content">
              <div id="tab-data" class="tab-pane active">

                {{-- 門市代碼 --}}
                <div class="row mb-3">
                  <label for="input-code" class="col-sm-2 col-form-label">門市代碼</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <input type="text" id="input-code" name="code" value="{{ $store->code ?? '' }}" class="form-control" placeholder="例如：SH001, TPE001（選填）"/>
                    </div>
                    <div class="form-text">門市唯一識別碼，建議使用英文+數字（選填）</div>
                    <div id="error-code" class="invalid-feedback"></div>
                  </div>
                </div>

                {{-- 門市名稱 --}}
                <div class="row mb-3 required">
                  <label for="input-name" class="col-sm-2 col-form-label">門市名稱</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <input type="text" id="input-name" name="name" value="{{ $store->name ?? '' }}" class="form-control" placeholder="例如：台北信義店"/>
                    </div>
                    <div id="error-name" class="invalid-feedback"></div>
                  </div>
                </div>

                {{-- 縣市 --}}
                <div class="row mb-3">
                  <label for="input-state_id" class="col-sm-2 col-form-label">縣市</label>
                  <div class="col-sm-10">
                    <select id="input-state_id" name="state_id" class="form-select">
                      <option value="">-- 請選擇縣市 --</option>
                      @foreach($states as $state)
                      <option value="{{ $state->id }}" @if(($store->state_id ?? '') == $state->id) selected @endif>
                        {{ $state->name }}
                      </option>
                      @endforeach
                    </select>
                    <div id="error-state_id" class="invalid-feedback"></div>
                  </div>
                </div>

                {{-- 鄉鎮市區 --}}
                <div class="row mb-3">
                  <label for="input-city_id" class="col-sm-2 col-form-label">鄉鎮市區</label>
                  <div class="col-sm-10">
                    <select id="input-city_id" name="city_id" class="form-select">
                      <option value="">-- 請先選擇縣市 --</option>
                      @foreach($cities as $city)
                      <option value="{{ $city->id }}" @if(($store->city_id ?? '') == $city->id) selected @endif>
                        {{ $city->name }}
                      </option>
                      @endforeach
                    </select>
                    <div id="error-city_id" class="invalid-feedback"></div>
                  </div>
                </div>

                {{-- 地址 --}}
                <div class="row mb-3">
                  <label for="input-address" class="col-sm-2 col-form-label">地址</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <textarea id="input-address" name="address" class="form-control" rows="2" placeholder="請輸入門市地址">{{ $store->address ?? '' }}</textarea>
                    </div>
                    <div id="error-address" class="invalid-feedback"></div>
                  </div>
                </div>

                {{-- 電話 --}}
                <div class="row mb-3">
                  <label for="input-phone" class="col-sm-2 col-form-label">電話</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <input type="text" id="input-phone" name="phone" value="{{ $store->phone ?? '' }}" class="form-control" placeholder="例如：02-12345678"/>
                    </div>
                    <div id="error-phone" class="invalid-feedback"></div>
                  </div>
                </div>

                {{-- 店長 --}}
                <div class="row mb-3">
                  <label for="input-manager_id" class="col-sm-2 col-form-label">店長</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <input type="text" id="input-manager_id" name="manager_id" value="{{ $store->manager_id ?? '' }}" class="form-control" placeholder="店長 User ID（選填）"/>
                    </div>
                    <div class="form-text">請輸入店長的使用者 ID，或留空</div>
                    <div id="error-manager_id" class="invalid-feedback"></div>
                  </div>
                </div>

                {{-- 狀態 --}}
                <div class="row mb-3">
                  <label for="input-is_active" class="col-sm-2 col-form-label">狀態</label>
                  <div class="col-sm-10">
                    <div class="form-check form-switch">
                      <input type="hidden" name="is_active" value="0"/>
                      <input type="checkbox" id="input-is_active" name="is_active" value="1" class="form-check-input" @if(($store->is_active ?? true)) checked @endif/>
                      <label class="form-check-label" for="input-is_active">啟用</label>
                    </div>
                    <div class="form-text">停用後，此門市將無法使用</div>
                    <div id="error-is_active" class="invalid-feedback"></div>
                  </div>
                </div>

                @if($store_id)
                {{-- 建立時間 --}}
                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label">建立時間</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <input type="text" value="{{ $store->created_at }}" class="form-control" disabled/>
                    </div>
                  </div>
                </div>

                {{-- 更新時間 --}}
                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label">更新時間</label>
                  <div class="col-sm-10">
                    <div class="input-group">
                      <input type="text" value="{{ $store->updated_at }}" class="form-control" disabled/>
                    </div>
                  </div>
                </div>
                @endif

              </div>
              <input type="hidden" id="input-store_id" name="store_id" value="{{ $store_id }}"/>
            </div>
          </form>
          </div>
          </div>
        </div>
    </div>
</div>
@endsection

@section('buttom')
<script type="text/javascript">
// 儲存當前選擇的鄉鎮市區 ID，用於 AJAX 更新後恢復選擇
var selectedCityId = {{ $store->city_id ?? 0 }};

// 當選擇縣市時，載入該縣市下的鄉鎮市區
$('#input-state_id').on('change', function() {
  var stateId = $(this).val();

  // 清空鄉鎮市區選單
  var citySelect = $('#input-city_id');
  citySelect.html('<option value="">-- 載入中... --</option>');

  if (!stateId) {
    // 如果沒有選擇縣市，顯示提示
    citySelect.html('<option value="">-- 請先選擇縣市 --</option>');
    selectedCityId = 0; // 清除記憶的選擇
    return;
  }

  // AJAX 取得鄉鎮市區列表
  $.ajax({
    type: 'GET',
    url: "{{ $cities_list_url }}",
    data: {
      equal_parent_id: stateId
    },
    dataType: 'json',
    success: function(cities) {
      var html = '<option value="">-- 請選擇鄉鎮市區 --</option>';

      if (cities && cities.length > 0) {
        $.each(cities, function(index, city) {
          html += '<option value="' + city.city_id + '">' + city.name + '</option>';
        });
      }

      citySelect.html(html);

      // 如果之前有選擇的鄉鎮市區，恢復選擇
      if (selectedCityId) {
        citySelect.val(selectedCityId);
        selectedCityId = 0; // 清除記憶，避免下次切換縣市時誤用
      }
    },
    error: function() {
      citySelect.html('<option value="">-- 載入失敗，請重試 --</option>');
    }
  });
});

// 頁面載入時，如果有選擇縣市，觸發一次載入（用於編輯時）
$(document).ready(function() {
  var stateId = $('#input-state_id').val();
  if (stateId && selectedCityId) {
    $('#input-state_id').trigger('change');
  }
});
</script>
@endsection
