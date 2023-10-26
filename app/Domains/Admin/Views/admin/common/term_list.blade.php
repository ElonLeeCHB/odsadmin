<form id="form-term" method="post" data-oc-toggle="ajax" data-oc-load="{{ $list_url }} data-oc-target="#store">
	<div class="table-responsive">
		<table class="table table-bordered table-hover">
			<thead>
				<tr>
					<td class="text-start"><a href="{{ $sort_id }}" @if($sort=='id') class="{{ $order }}" @endif>{{ $lang->column_id }}</a></td>
					<td class="text-start"><a href="{{ $sort_code }}" @if($sort=='code') class="{{ $order }}" @endif>{{ $lang->column_code }}</a></td>
					<td class="text-start"><a href="{{ $sort_name }}" @if($sort=='name') class="{{ $order }}" @endif>{{ $lang->column_name }}</a></td>
					<td class="text-start"><a href="{{ $sort_taxonomy_name }}" @if($sort=='taxonomy_name') class="{{ $order }}" @endif>{{ $lang->column_taxonomy_name }}</a></td>
					<td class="text-start">{{ $lang->column_is_active }}</td>
					<td class="text-end">{{ $lang->column_action }}</td>
				</tr>
			</thead>
			<tbody>
				@foreach($terms as $row)
                <tr>
					<td class="text-start">{{ $row->id }}</td>
					<td class="text-start">{{ $row->code }}</td>
					<td class="text-start">{{ $row->name }}</td>
					<td class="text-start">{{ $row->taxonomy_name }}</td>
					<td class="text-start">@if($row->is_active)
                                  {{ $lang->text_yes }}
                                @else
                                  {{ $lang->text_no }}
                                @endif</td>
                    <td class="text-end"><a href="{{ $row->edit_url }}" data-bs-toggle="tooltip" title="Edit" class="btn btn-primary"><i class="fa-solid fa-pencil"></i></a></td>
                </tr>
				@endforeach
            </tbody>
		</table>
	</div>
  {!! $terms->links('admin.pagination.default', ['terms'=>$terms]) !!}
</form>