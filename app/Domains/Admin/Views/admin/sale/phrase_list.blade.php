<form id="form-phrase" method="post" data-oc-toggle="ajax" data-oc-load="{{ $list_url }}" data-oc-target="#phrase">
  @csrf
  @method('POST')
  <div class="table-responsive">
    <table class="table table-bordered table-hover">
      <thead>
        <tr>
          <td class="text-center" style="width: 1px;"><input type="checkbox" onclick="$('input[name*=\'selected\']').prop('checked', $(this).prop('checked'));" class="form-check-input"/></td>
          <td class="text-end"><a href="{{ $sort_id }}" @if($sort=='id') class="{{ $order }}" @endif>{{ $lang->column_id }}</a></td>
          <td class="text-start">{{ $lang->column_name }}</td>
          <td class="text-start">{{ $lang->column_taxonomy }}</td>
          <td class="text-start">{{ $lang->column_is_active }}</td>
          <td class="text-end">{{ $lang->column_action }}</td>
        </tr>
      </thead>
      <tbody>
        @foreach($phrases as $row)
        <tr>
          <td class="text-center"><input type="checkbox" name="selected[]" value="{{ $row->id }}" class="form-check-input"/></td>
          <td class="text-end">{{ $row->id }}</td>
          <td class="text-start">{{ $row->name }}</td>
          <td class="text-start">{{ $row->taxonomy->name }}</td>
          <td class="text-start">@if($row->is_active)
                                  {{ $lang->text_yes }}
                                @else
                                  {{ $lang->text_no }}
                                @endif</td>
          <td class="text-end"><a href="{{ $row->edit_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_edit }}" class="btn btn-primary"><i class="fas fa-pencil-alt"></i></a></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  {!! $phrases->links('admin.pagination.default', ['phrases'=>$phrases]) !!}
</form>