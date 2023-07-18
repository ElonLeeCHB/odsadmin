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
          <td class="text-start">{{ $lang->column_phone }}</td>
          <td class="text-start"><a href="{{ $sort_email }}" @if($sort=='email') class="{{ $order }}" @endif>{{ $lang->column_email }}</a></td>
          <td class="text-start"><a href="{{ $sort_date_added }}" @if($sort=='date_created') class="{{ $order }}" @endif>{{ $lang->column_date_added }}</a></td>
          <td class="text-end">{{ $lang->column_action }}</td>
        </tr>
      </thead>
      <tbody>
        @foreach($users as $row)
        <tr>
          <td class="text-center"><input type="checkbox" name="selected[]" value="{{ $row->id }}" class="form-check-input"/></td>
          <td class="text-end">{{ $row->id }}</td>
          <td class="text-start">{{ $row->username }}</td>
          <td class="text-start">{{ $row->name }}</td>
          <td class="text-start">mob:{{ $row->mobile }}<BR>tel:{{ $row->telephone }}</td>
          <td class="text-start">{{ $row->email }}</td>
          <td class="text-start d-none d-lg-table-cell">{{ $row->created_date }}</td>
          <td class="text-end"><a href="{{ $row->edit_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_edit }}" class="btn btn-primary"><i class="fas fa-pencil-alt"></i></a></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  {!! $users->links('admin.pagination.default', ['users'=>$users]) !!}
</form>