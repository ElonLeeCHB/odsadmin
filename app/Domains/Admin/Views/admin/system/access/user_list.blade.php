<form id="form-user" method="post" data-oc-toggle="ajax" data-oc-load="{{ $list_url }}" data-oc-target="#user">
  @csrf
  @method('POST')
  <div class="table-responsive">
    <table class="table table-bordered table-hover">
      <thead>
        <tr>
          <td class="text-center" style="width: 1px;"><input type="checkbox" onclick="$('input[name*=\'selected\']').prop('checked', $(this).prop('checked'));" class="form-check-input"/></td>
          <td class="text-end">{{ $lang->column_id }}</td>
          <td class="text-start"><a href="{{ $sort_username }}" @if($sort=='username') class="{{ $order }}" @endif>{{ $lang->column_username }}</a></td>
          <td class="text-start"><a href="{{ $sort_name }}" @if($sort=='full_name') class="{{ $order }}" @endif>{{ $lang->column_name }}</a></td>
          <td class="text-start"><a href="{{ $sort_email }}" @if($sort=='email') class="{{ $order }}" @endif>{{ $lang->column_email }}</a></td>
          <td class="text-start"><a href="{{ $sort_created_at }}" @if($sort=='created_at') class="{{ $order }}" @endif>{{ $lang->column_date_added }}</a></td>
          <td class="text-start">是否啟用</td>
          <td class="text-end">{{ $lang->column_action }}</td>
        </tr>
      </thead>
      <tbody>
        @foreach($users as $row)
        <tr>
          <td class="text-center"><input type="checkbox" name="selected[]" value="{{ $row->user_id }}" class="form-check-input"/></td>
          <td class="text-end">{{ $row->user_id }}</td>
          <td class="text-start">{{ $row->user->username }}</td>
          <td class="text-start">{{ $row->user->name }}</td>
          <td class="text-start">{{ $row->user->email }}</td>
          <td class="text-start d-none d-lg-table-cell">{{ $row->user->created_at?->format('Y-m-d') }}</td>
          <td class="text-start">@if($row->user->is_active)
                                      {{ $lang->text_yes }}
                                    @else
                                      {{ $lang->text_no }}
                                    @endif</td>
          <td class="text-end"><a href="{{ $row->user->edit_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_edit }}" class="btn btn-primary"><i class="fas fa-pencil-alt"></i></a></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  {!! $users->links('admin.pagination.default', ['users'=>$users]) !!}
</form>