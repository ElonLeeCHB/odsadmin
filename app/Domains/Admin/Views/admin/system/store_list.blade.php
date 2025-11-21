<form id="form-store" method="post" data-oc-toggle="ajax" data-oc-load="{{ $list_url }}" data-oc-target="#store">
  @csrf
  @method('POST')
  <div class="table-responsive">
    <table class="table table-bordered table-hover">
      <thead>
        <tr>
          <td class="text-center" style="width: 1px;"><input type="checkbox" onclick="$('input[name*=\'selected\']').prop('checked', $(this).prop('checked'));" class="form-check-input"/></td>
          <td class="text-start"><a href="{{ $sort_id }}" @if($sort=='id') class="{{ $order }}" @endif>ID</a></td>
          <td class="text-start"><a href="{{ $sort_code }}" @if($sort=='code') class="{{ $order }}" @endif>門市代碼</a></td>
          <td class="text-start"><a href="{{ $sort_name }}" @if($sort=='name') class="{{ $order }}" @endif>門市名稱</a></td>
          <td class="text-start">縣市</td>
          <td class="text-start">鄉鎮市區</td>
          <td class="text-start">電話</td>
          <td class="text-start">店長</td>
          <td class="text-center">狀態</td>
          <td class="text-end">操作</td>
        </tr>
      </thead>
      <tbody>
        @foreach($stores as $row)
        <tr>
          <td class="text-center"><input type="checkbox" name="selected[]" value="{{ $row->id }}" class="form-check-input"/></td>
          <td class="text-start">{{ $row->id }}</td>
          <td class="text-start">{{ $row->code }}</td>
          <td class="text-start">{{ $row->name }}</td>
          <td class="text-start">{{ $row->state_name ?? '' }}</td>
          <td class="text-start">{{ $row->city_name ?? '' }}</td>
          <td class="text-start">{{ $row->phone ?? '' }}</td>
          <td class="text-start">{{ $row->manager_name ?? '' }}</td>
          <td class="text-center">
            @if($row->is_active)
              <span class="badge bg-success">啟用</span>
            @else
              <span class="badge bg-secondary">停用</span>
            @endif
          </td>
          <td class="text-end"><a href="{{ $row->edit_url }}" data-bs-toggle="tooltip" title="Edit" class="btn btn-primary"><i class="fa-solid fa-pencil"></i></a></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  {!! $stores->links('admin.pagination.default') !!}
</form>
