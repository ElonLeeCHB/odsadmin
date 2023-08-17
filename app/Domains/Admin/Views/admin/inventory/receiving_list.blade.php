<form id="form-receiving" method="post" data-oc-toggle="ajax" data-oc-load="{{ $list_url }}" data-oc-target="#receiving">
  <div class="table-responsive">
    <table class="table table-bordered table-hover">
      <thead>
        <tr>
          <td class="text-center" style="width: 1px;"><input type="checkbox" onclick="$('input[name*=\'selected\']').prop('checked', $(this).prop('checked'));" class="form-check-input"/></td>
          <td class="text-start"><a href="{{ $sort_id }}" @if($sort=='id') class="{{ $order }}" @endif>{{ $lang->column_id }}</a></td>
          <td class="text-start"><a href="{{ $sort_receiving_date }}" @if($sort=='name') class="{{ $order }}" @endif>{{ $lang->column_name }}</a></td>
          <td class="text-start">{{ $lang->column_type }}</td>
          <td class="text-end">{{ $lang->column_action }}</td>
        </tr>
      </thead>
      <tbody>
        @foreach($receivings as $receiving)
        <tr>
          <td class="text-center"><input type="checkbox" name="selected[]" value="{{ $receiving->id }}" class="form-check-input"/></td>
          <td class="text-start">{{ $receiving->id }}</td>
          <td class="text-start">{{ $receiving->receiving_date }}</td>
          <td class="text-start">{{ $receiving->type }}</td>
          <td class="text-end"><a href="{{ $receiving->edit_url }}" data-bs-toggle="tooltip" title="Edit" class="btn btn-primary"><i class="fa-solid fa-pencil"></i></a></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</form>