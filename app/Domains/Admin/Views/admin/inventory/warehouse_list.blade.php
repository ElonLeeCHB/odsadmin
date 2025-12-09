<form id="form-warehouse" method="post" data-oc-toggle="ajax" data-oc-load="{{ $list_url }}" data-oc-target="#warehouse">
  <div class="table-responsive">
    <table class="table table-bordered table-hover">
      <thead>
        <tr>
          <td class="text-center" style="width: 1px;"><input type="checkbox" onclick="$('input[name*=\'selected\']').prop('checked', $(this).prop('checked'));" class="form-check-input"/></td>
          <td class="text-start"><a href="{{ $sort_id }}" @if($sort=='id') class="{{ $order }}" @endif>{{ $lang->column_id }}</a></td>
          <td class="text-start"><a href="{{ $sort_name }}" @if($sort=='name') class="{{ $order }}" @endif>{{ $lang->column_name }}</a></td>
          <td class="text-start"><a href="{{ $sort_is_active }}" @if($sort=='is_active') class="{{ $order }}" @endif>{{ $lang->column_is_active }}</a></td>
          <td class="text-start"><a href="{{ $sort_sort_order }}" @if($sort=='sort_order') class="{{ $order }}" @endif>{{ $lang->column_sort_order }}</a></td>
          <td class="text-end">{{ $lang->column_action }}</td>
        </tr>
      </thead>
      <tbody>
        @foreach($warehouses as $warehouse)
				<tr>
          <td class="text-center"><input type="checkbox" name="selected[]" value="{{ $warehouse->id }}" class="form-check-input"/></td>
          <td class="text-start">{{ $warehouse->id }}</td>
          <td class="text-start">{{ $warehouse->name }}</td>
          <td class="text-start">{{ $warehouse->is_active }}</td>
          <td class="text-start">{{ $warehouse->sort_order }}</td>
					<td class="text-end"><a href="{{ $warehouse->edit_url }}" data-bs-toggle="tooltip" title="Edit" class="btn btn-primary"><i class="fa-solid fa-pencil"></i></a></td>
				</tr>
        @endforeach
			</tbody>
    </table>
  </div>
</form>