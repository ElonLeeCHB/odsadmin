<form id="form-unit" method="post" data-oc-toggle="ajax" data-oc-load="{{ $list_url }}" data-oc-target="#unit">
  <div class="table-responsive">
    <table class="table table-bordered table-hover">
      <thead>
        <tr>
  				<td class="text-center" style="width: 1px;"><input type="checkbox" onclick="$('input[name*=\'selected\']').prop('checked', $(this).prop('checked'));" class="form-check-input"/></td>
          <td class="text-start"><a href="{{ $sort_id }}" @if($sort=='id') class="{{ $order }}" @endif>{{ $lang->column_id }}</a></td>
          <td class="text-start"><a href="{{ $sort_code }}" @if($sort=='code') class="{{ $order }}" @endif>{{ $lang->column_code }}</a></td>
          <td class="text-start">{{ $lang->column_name }}</td>
          <td class="text-start">{{ $lang->column_is_active }}</td>
          <td class="text-end">{{ $lang->column_action }}</td>
        </tr>
      </thead>
      <tbody>
        @foreach($units as $unit)
				<tr>
          <td class="text-center"><input type="checkbox" name="selected[]" value="{{ $unit->id }}" class="form-check-input"/></td>
          <td class="text-start">{{ $unit->id }}</td>
          <td class="text-start">{{ $unit->code }}</td>
          <td class="text-start">{{ $unit->name }}</td>
          <td class="text-start">{{ $unit->is_active_name }}</td>
					<td class="text-end"><a href="{{ $unit->edit_url }}" data-bs-toggle="tooltip" title="Edit" class="btn btn-primary"><i class="fa-solid fa-pencil"></i></a></td>
				</tr>
        @endforeach
			</tbody>
    </table>
  </div>
</form>
  {!! $units->links('admin.pagination.default') !!}