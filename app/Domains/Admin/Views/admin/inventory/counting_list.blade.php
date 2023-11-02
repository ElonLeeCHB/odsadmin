<form id="form-counting" method="post" data-oc-toggle="ajax" data-oc-load="{{ $list_url }}" data-oc-target="#counting">
  <div class="table-responsive">
    <table class="table table-bordered table-hover">
      <thead>
        <tr>
  				<td class="text-center" style="width: 1px;"><input type="checkbox" onclick="$('input[name*=\'selected\']').prop('checked', $(this).prop('checked'));" class="form-check-input"/></td>
          <td class="text-start"><a href="{{ $sort_id }}" @if($sort=='id') class="{{ $order }}" @endif>{{ $lang->column_id }}</a></td>
          <td class="text-start"><a href="{{ $sort_task_date }}" @if($sort=='task_date') class="{{ $order }}" @endif>{{ $lang->column_form_date }}</a></td>
          <td class="text-start">{{ $lang->column_status }}</td>
          <td class="text-end">{{ $lang->column_action }}</td>
        </tr>
      </thead>
      <tbody>
        @foreach($countings as $row)
				<tr>
          <td class="text-center"><input type="checkbox" name="selected[]" value="{{ $row->id }}" class="form-check-input"/></td>
          <td class="text-start">{{ $row->id }}</td>
          <td class="text-start">{{ $row->task_date }}</td>
          <td class="text-start">{{ $row->status_name }}</td>
					<td class="text-end"><a href="{{ $row->edit_url }}" data-bs-toggle="tooltip" title="Edit" class="btn btn-primary"><i class="fa-solid fa-pencil"></i></a></td>
				</tr>
        @endforeach
			</tbody>
    </table>
  </div>
</form>
@if(count($countings) > 0)
  {!! $countings->links('admin.pagination.default') !!}
@endif
 
