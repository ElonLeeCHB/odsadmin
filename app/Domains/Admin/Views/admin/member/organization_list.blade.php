<form id="form-member" method="post" data-oc-toggle="ajax" data-oc-load="{{ route('lang.admin.member.members.list') }}" data-oc-target="#member">
	@csrf
	@method('POST')
	<div class="table-responsive">
		<table class="table table-bordered table-hover">
			<thead>
				<tr>
					<td class="text-end">{{ $lang->column_id }}</td>
					<td class="text-start"><a href="{{ $sort_name }}" @if($sort=='name') class="{{ $order }}" @endif>{{ $lang->column_name }}</a></td>
					<td class="text-start"><a href="{{ $sort_short_name }}" @if($sort=='short_name') class="{{ $order }}" @endif>{{ $lang->column_short_name }}</a></td>
					<td class="text-start">{{ $lang->column_contact }}</td>
					<td class="text-start">{{ $lang->column_contact_phone }}</td>
					<td class="text-end">{{ $lang->column_action }}</td>
				</tr>
			</thead>
			<tbody>
				@foreach($organizations as $row)
				<tr>
					<td class="text-end">{{ $row->id }}</td>
					<td class="text-start">{{ $row->name }}</td>
					<td class="text-start">{{ $row->short_name }}</td>
					<td class="text-start">{{ $row->contact }}</td>
					<td class="text-start">{{ $row->phone }}</td>
					<td class="text-end"><a href="{{ $row->edit_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_edit }}" class="btn btn-primary"><i class="fas fa-pencil-alt"></i></a></td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
	{!! $organizations->links('admin.pagination.default', ['members'=>$organizations]) !!}

    <?php /*
    <div class="row">
        <div class="col-sm-6 text-start">{!! $pagination !!}</div>
        <div class="col-sm-6 text-end">{{ $results }}</div>
    </div>
    */ ?>
</form>