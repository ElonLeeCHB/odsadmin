<form id="form-option" method="post" data-oc-toggle="ajax" data-oc-load="{{ $action }}" data-oc-target="#option">
  @csrf
  @method('POST')
  <div class="table-responsive">
    <table class="table table-bordered table-hover">
      <thead>
        <tr>
          <td class="text-center" style="width: 1px;"><input type="checkbox" onclick="$('input[name*=\'selected\']').prop('checked', $(this).prop('checked'));" class="form-check-input"/></td>
          <td class="text-end">{{ $lang->column_id }}</td>
          <td class="text-start"><a href="{{ $sort_name }}" @if($sort=='full_name') class="{{ $order }}" @endif>{{ $lang->column_name }}</a></td>
          <td class="text-end">{{ $lang->column_action }}</td>
        </tr>
      </thead>
      <tbody>
        @foreach($options as $row)
        <tr>
          <td class="text-center"><input type="checkbox" name="selected[]" value="{{ $row->id }}" class="form-check-input"/></td>
          <td class="text-end">{{ $row->id }}</td>
          <td class="text-start">{{ $row->name }}</td>
          <td class="text-end"><a href="{{ $row->edit_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_edit }}" class="btn btn-primary"><i class="fas fa-pencil-alt"></i></a></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  {!! $pagination !!}
</form>