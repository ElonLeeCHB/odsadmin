<form id="form-taxonomy" method="post" data-oc-toggle="ajax" data-oc-load="{{ $list_url }}" data-oc-target="#member">
	@csrf
	@method('POST')
	<div class="table-responsive">
		<table class="table table-bordered table-hover">
			<thead>
				<tr>
					<td class="text-start"><a href="{{ $sort_code }}" @if($sort=='code') class="{{ $order }}" @endif>{{ $lang->column_code }}</a></td>
					<td class="text-start">{{ $lang->column_name }}</td>
					<td class="text-start">{{ $lang->column_is_active }}</td>
					<td class="text-end">{{ $lang->column_action }}</td>
				</tr>
			</thead>
			<tbody>
				@foreach($taxonomies as $row)
				<tr>
					<td class="text-start">{{ $row->code }}</td>
					<td class="text-start">{{ $row->name }}</td>
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
	{!! $taxonomies->links('admin.pagination.default', ['taxonomies'=>$taxonomies]) !!}
</form>