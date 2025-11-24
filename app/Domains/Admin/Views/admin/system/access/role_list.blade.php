<form id="form-role" method="post" data-oc-toggle="ajax" data-oc-load="{{ $list_url }}" data-oc-target="#role">
  @csrf
  @method('POST')
  <div class="table-responsive">
    <table class="table table-bordered table-hover">
      <thead>
        <tr>
          <td class="text-center" style="width: 1px;"><input type="checkbox" onclick="$('input[name*=\'selected\']').prop('checked', $(this).prop('checked'));" class="form-check-input"/></td>
          <td class="text-start"><a href="{{ $sort_id }}" @if($sort=='id') class="{{ $order }}" @endif>ID</a></td>
          <td class="text-start"><a href="{{ $sort_name }}" @if($sort=='name') class="{{ $order }}" @endif>角色代碼</a></td>
          <td class="text-start">顯示名稱</td>
          <td class="text-start">說明</td>
          <td class="text-start">Guard</td>
          <td class="text-start">權限數</td>
          <td class="text-start d-none d-lg-table-cell">建立時間</td>
          <td class="text-end">操作</td>
        </tr>
      </thead>
      <tbody>
        @foreach($roles as $row)
        <tr>
          <td class="text-center"><input type="checkbox" name="selected[]" value="{{ $row->id }}" class="form-check-input"/></td>
          <td class="text-start">{{ $row->id }}</td>
          <td class="text-start">{{ $row->name }}</td>
          <td class="text-start">{{ $row->title ?? '' }}</td>
          <td class="text-start">{{ $row->description ?? '' }}</td>
          <td class="text-start">{{ $row->guard_name }}</td>
          <td class="text-start">{{ $row->permissions_count ?? 0 }}</td>
          <td class="text-start d-none d-lg-table-cell">{{ $row->created_at }}</td>
          <td class="text-end"><a href="{{ $row->edit_url }}" data-bs-toggle="tooltip" title="Edit" class="btn btn-primary"><i class="fa-solid fa-pencil"></i></a></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  {!! $roles->links('admin.pagination.default') !!}
</form>
