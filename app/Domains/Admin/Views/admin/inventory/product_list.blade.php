<form id="form-member" method="post" data-oc-toggle="ajax" data-oc-load="{{ route('lang.admin.member.members.list') }}" data-oc-target="#member">
	@csrf
	@method('POST')
	<div class="table-responsive">
		<table class="table table-bordered table-hover">
			<thead>
				<tr>
					<td class="text-end"><a href="{{ $sort_id }}" @if($sort=='id') class="{{ $order }}" @endif>{{ $lang->column_id }}</a></td>
					<td class="text-start">{{ $lang->column_code }}</td>
					<td class="text-start">{{ $lang->column_name }}</td>
					<td class="text-start"><a href="{{ $sort_price }}" @if($sort=='price') class="{{ $order }}" @endif>{{ $lang->column_price }}</a></td>
					<td class="text-end">{{ $lang->column_action }}</td>
				</tr>
			</thead>
			<tbody>
				@foreach($products as $row)
				<tr>
					<td class="text-end">{{ $row->id }}</td>
					<td class="text-start">{{ $row->code }}</td>
					<td class="text-start">{{ $row->name }}</td>
					<td class="text-start">{{ $row->price }}</td>
					<td class="text-end"><a href="{{ $row->edit_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_edit }}" class="btn btn-primary"><i class="fas fa-pencil-alt"></i></a></td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
	{!! $products->links('admin.pagination.default', ['products'=>$products]) !!}

    <?php /*
    <div class="row">
        <div class="col-sm-6 text-start">{!! $pagination !!}</div>
        <div class="col-sm-6 text-end">{{ $results }}</div>
    </div>
    */ ?>
</form>