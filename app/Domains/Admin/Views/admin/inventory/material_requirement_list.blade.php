<form id="form-requirement" method="post" data-oc-toggle="ajax" data-oc-load="{{ $list_url }}" data-oc-target="#unit">
  <div class="table-responsive">
    <table class="table table-bordered table-hover">
      <thead>
        <tr>
          <td class="text-start"><a href="{{ $sort_required_date }}" @if($sort=='required_date') class="{{ $order }}" @endif>{{ $lang->column_required_date }}</a></td>
          <td class="text-start"><a href="{{ $sort_product_id }}" @if($sort=='product_id') class="{{ $order }}" @endif>{{ $lang->column_product_id }}</a></td>
          <td class="text-start"><a href="{{ $sort_supplier_product_code }}" @if($sort=='supplier_product_code') class="{{ $order }}" @endif>{{ $lang->column_product_supplier_own_product_code }}</a></td>
          <td class="text-start"><a href="{{ $sort_supplier_short_name }}" @if($sort=='supplier_short_name') class="{{ $order }}" @endif>{{ $lang->column_supplier_short_name }}</a></td>
          <td class="text-start"><a href="{{ $sort_product_name }}" @if($sort=='product_name') class="{{ $order }}" @endif>{{ $lang->column_product_name }}</a></td>
          <td class="text-start">{{ $lang->column_product_stock_unit_name }}</td>
          <td class="text-start">{{ $lang->column_product_stock_quantity_needed }}</td>
          <td class="text-end">{{ $lang->column_action }}</td>
        </tr>
      </thead>
      <tbody>
        @foreach($requirements as $row)
				<tr>
          <td class="text-start">{{ $row->required_date }}</td>
          <td class="text-start">{{ $row->product_id }}</td>
          <td class="text-start">{{ $row->supplier_own_product_code }}</td>
          <td class="text-start">{{ $row->supplier_short_name }}</td>
          <td class="text-start">{{ $row->product_name }}</td>
          <td class="text-start">{{ $row->stock_unit_name }}</td>
          <td class="text-end">{{ number_format($row->stock_quantity, 1, '.', ',') }}</td>
					<td class="text-end"><a href="{{ $row->product_edit_url }}" data-bs-toggle="tooltip" title="料件連結" class="btn btn-primary" target="_blank"><i class="fas fa-external-link-alt"></i></a></td>
				</tr>
        @endforeach
			</tbody>
    </table>
  </div>
</form>
  {!! $requirements->links('admin.pagination.default') !!}