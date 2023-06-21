<form id="form-supplier" method="post" data-oc-toggle="ajax" data-oc-load="{{ $list_url }} data-oc-target="#store">
	<div class="table-responsive">
		<table class="table table-bordered table-hover">
			<thead>
				<tr>
					<td class="text-start"><a href="{{ $sort_id }}" @if($sort=='id') class="{{ $order }}" @endif>{{ $lang->column_id }}</a></td>
					<td class="text-start"><a href="{{ $sort_code }}" @if($sort=='code') class="{{ $order }}" @endif>{{ $lang->column_code }}</a></td>
					<td class="text-start"><a href="{{ $sort_name }}" @if($sort=='name') class="{{ $order }}" @endif>{{ $lang->column_name }}</a></td>
					<td class="text-start"><a href="{{ $sort_short_name }}" @if($sort=='short_name') class="{{ $order }}" @endif>{{ $lang->column_short_name }}</a></td>
					<td class="text-end">{{ $lang->column_action }}</td>
				</tr>
			</thead>
			<tbody>
				@foreach($suppliers as $supplier)
                <tr>
					<td class="text-start">{{ $supplier->id }}</td>
					<td class="text-start">{{ $supplier->code }}</td>
					<td class="text-start">{{ $supplier->name }}</td>
					<td class="text-start">{{ $supplier->short_name }}</td>
                    <td class="text-end"><a href="{{ $supplier->edit_url }}" data-bs-toggle="tooltip" title="Edit" class="btn btn-primary"><i class="fa-solid fa-pencil"></i></a></td>
                </tr>
				@endforeach
            </tbody>
		</table>
	</div>
</form>