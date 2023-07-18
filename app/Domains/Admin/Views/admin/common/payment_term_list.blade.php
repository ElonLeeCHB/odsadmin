<form id="form-payment_term" method="post" data-oc-toggle="ajax" data-oc-load="{{ $list_url }}" data-oc-target="#payment_term">
  <div class="table-responsive">
    <table class="table table-bordered table-hover">
      <thead>
        <tr>
          <td class="text-center" style="width: 1px;"><input type="checkbox" onclick="$('input[name*=\'selected\']').prop('checked', $(this).prop('checked'));" class="form-check-input"/></td>
          <td class="text-start"><a href="{{ $sort_type }}" @if($sort=='type') class="{{ $order }}" @endif>{{ $lang->column_type }}</a></td>
          <td class="text-start"><a href="{{ $sort_name }}" @if($sort=='name') class="{{ $order }}" @endif>{{ $lang->column_name }}</a></td>
          <td class="text-start"><a href="{{ $sort_due_date_basis }}" @if($sort=='due_date_basis') class="{{ $order }}" @endif>{{ $lang->column_due_date_basis }}</a></td>
          <td class="text-start"><a href="{{ $sort_sort_order }}" @if($sort=='sort_order') class="{{ $order }}" @endif>{{ $lang->column_sort_order }}</a></td>
          <td class="text-start"><a href="{{ $sort_is_active }}" @if($sort=='is_active') class="{{ $order }}" @endif>{{ $lang->column_is_active }}</a></td>
          <td class="text-end">{{ $lang->column_action }}</td>
        </tr>
      </thead>
      <tbody>
        @foreach($payment_terms as $row)
        <tr>
          <td class="text-center"><input type="checkbox" name="selected[]" value="{{ $row->id }}" class="form-check-input"/></td>
          <td class="text-start">{{ $row->type_name }}</td>
          <td class="text-start">{{ $row->name }}</td>
          <td class="text-start">{{ $row->due_date_basis_name }}</td>
          <td class="text-end">{{ $row->sort_order }}</td>
          <td class="text-end">{{ $row->is_active_text }}</td>
          <td class="text-end"><a href="{{ $row->edit_url }}" data-bs-toggle="tooltip" title="Edit" class="btn btn-primary"><i class="fa-solid fa-pencil"></i></a></td>
        </tr>
        @endforeach
            </tbody>
    </table>
  </div>
  {!! $payment_terms->links('admin.pagination.default', ['payment_terms'=>$payment_terms]) !!}
</form>