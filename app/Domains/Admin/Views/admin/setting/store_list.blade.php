<form id="form-store" method="post" data-oc-toggle="ajax" data-oc-load="http://opencart4011.test/backend/index.php?route=setting/store|list&amp;user_token=d228439e875f9feb93030e2bc42348d8" data-oc-target="#store">
	<div class="table-responsive">
		<table class="table table-bordered table-hover">
			<thead>
				<tr>
					<td class="text-start"><a href="{{ $sort_name }}" @if($sort=='full_name') class="{{ $order }}" @endif>{{ $lang->column_name }}</a></td>
					<td class="text-start"><a href="{{ $sort_short_name }}" @if($sort=='short_name') class="{{ $order }}" @endif>{{ $lang->column_short_name }}</a></td>
					<td class="text-start">是否公司</td>
					<td class="text-start">是否品牌</td>
					<td class="text-start">是否門市</td>
					<td class="text-end">{{ $lang->column_action }}</td>
				</tr>
			</thead>
			<tbody>
				@foreach($organizations as $organization)
                <tr>
                    <td class="text-start">{{ $organization->name }}</td>
					<td class="text-start">{{ $organization->short_name }}</td>
					<td class="text-start">{{ $organization->is_juridical_entity }}</td>
					<td class="text-start">{{ $organization->is_brand }}</td>
					<td class="text-start">{{ $organization->is_location }}</td>
                    <td class="text-end"><a href="{{ $organization->edit_url }}" data-bs-toggle="tooltip" title="Edit" class="btn btn-primary"><i class="fa-solid fa-pencil"></i></a></td>
                </tr>
				@endforeach
            </tbody>
		</table>
	</div>
</form>