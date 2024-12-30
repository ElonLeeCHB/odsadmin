<form id="form-setting" method="post" data-oc-toggle="ajax" data-oc-load="{{ $list_url }}" data-oc-target="#setting">
  @csrf
  @method('POST')
  <div class="table-responsive">
    <table class="table table-bordered table-hover">
      <thead>
        <tr>
          <td class="text-center" style="width: 1px;"><input type="checkbox" onclick="$('input[name*=\'selected\']').prop('checked', $(this).prop('checked'));" class="form-check-input"/></td>
          <td class="text-end" >{{ $lang->column_id }}</td>
          <td class="text-start">{{ $lang->column_location }}</td>
          <td class="text-start"><a href="{{ $sort_group }}" @if($sort=='group') class="{{ $order }}" @endif>{{ $lang->column_group }}</a></td>
          <td class="text-start"><a href="{{ $sort_setting_key }}" @if($sort=='setting_key') class="{{ $order }}" @endif>{{ $lang->column_setting_key }}</a></td>
          <td class="text-start">{{ $lang->column_is_json }}</td>
          <td class="text-start">{{ $lang->column_comment }}</td>
          <td class="text-end">{{ $lang->column_action }}</td>
        </tr>
      </thead>
      <tbody>
        @foreach($settings as $row)
        <tr>
          <td class="text-center"><input type="checkbox" name="selected[]" value="{{ $row->id }}" class="form-check-input"/></td>
          <td class="text-end">{{ $row->id }}</td>
          <td class="text-start">{{ $row->location_name }}</td>
          <td class="text-start">{{ $row->group }}</td>
          <td class="text-start">{{ $row->setting_key }}</td>
          <td class="text-start col-md-3">
            @if($row->is_json)
              是
            @else
              否
            @endif
          </td>
          <td class="text-start col-md-3">{{ $row->comment }}</td>
          <td class="text-end"><a href="{{ $row->edit_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_edit }}" class="btn btn-primary"><i class="fas fa-pencil-alt"></i></a></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  {!! $settings->links('admin.pagination.default', ['users'=>$settings]) !!}
</form>
