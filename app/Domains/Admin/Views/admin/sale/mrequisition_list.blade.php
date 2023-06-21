<form id="form-member" method="post" data-oc-toggle="ajax" data-oc-load="{{ route('lang.admin.sale.mrequisition.list') }}" data-oc-target="#member">
	@csrf
	@method('POST')
	<div class="table-responsive">
		<table class="table table-bordered table-hover">
			<thead>
				<tr>
          <td class="text-center" style="width: 1px;"><input type="checkbox" onclick="$('input[name*=\'selected\']').prop('checked', $(this).prop('checked'));" class="form-check-input"/></td>
					<td class="text-start"><a href="{{ $sort_required_date }}" @if($sort=='required_date') class="{{ $order }}" @endif>{{ $lang->column_required_date }}</a></td>
					<td class="text-end">{{ $lang->column_action }}</td>
				</tr>
			</thead>
			<tbody>
				@foreach($mrequisitions as $row)
				<tr>
					<td class="text-end">{{ $row->id }}</td>
					<td class="text-start">{{ $row->name }}</td>
					<td class="text-end"><a href="{{ $row->edit_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_edit }}" class="btn btn-primary"><i class="fas fa-pencil-alt"></i></a></td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
  @if(!empty($mrequisitions))
	{!! $mrequisitions->links('admin.pagination.default', ['mrequisitions'=>$mrequisitions]) !!}
  @endif

    <?php /*
    <div class="row">
        <div class="col-sm-6 text-start">{!! $pagination !!}</div>
        <div class="col-sm-6 text-end">{{ $results }}</div>
    </div>
    */ ?>
</form>