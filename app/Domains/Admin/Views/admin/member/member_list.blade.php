<form id="form-member" method="post" data-oc-toggle="ajax" data-oc-load="{{ $list_url }}" data-oc-target="#member">
  @csrf
  @method('POST')
  <div class="table-responsive">
    <table class="table table-bordered table-hover">
      <thead>
        <tr>
          <td class="text-center" style="width: 1px;"><input type="checkbox" onclick="$('input[name*=\'selected\']').prop('checked', $(this).prop('checked'));" class="form-check-input"/></td>
          <td class="text-start"><a href="{{ $sort_id }}" @if($sort=='id') class="{{ $order }}" @endif>{{ $lang->column_id }}</a></td>
          <td class="text-start"><a href="{{ $sort_name }}" @if($sort=='full_name') class="{{ $order }}" @endif>{{ $lang->column_name }}</a></td>
          <td class="text-start">{{ $lang->column_phone }}</td>
          <td class="text-start"><a href="{{ $sort_payment_company }}" @if($sort=='payment_company') class="{{ $order }}" @endif>{{ $lang->column_payment_company }}</a></td>
          <td class="text-start"><a href="{{ $sort_date_added }}" @if($sort=='date_created') class="{{ $order }}" @endif>{{ $lang->column_date_added }}</a></td>
          <td class="text-end">{{ $lang->column_action }}</td>
        </tr>
      </thead>
      <tbody>
        @foreach($members as $row)
        <tr>
          <td class="text-center"><input type="checkbox" name="selected[]" value="{{ $row->id }}" class="form-check-input"/></td>
          <td class="text-end">{{ $row->id }}</td>
          <td class="text-start">{{ $row->name }}</td>
          <td class="text-start">mob:{{ $row->mobile }}<BR>tel:{{ $row->telephone }}</td>
          <td class="text-start">{{ $row->payment_company }}</td>
					<td class="text-start d-none d-lg-table-cell">{{ $row->date_created }}</td>
          <td class="text-end"><a href="{{ $row->edit_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_edit }}" class="btn btn-primary"><i class="fas fa-pencil-alt"></i></a></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  {!! $members->links('admin.pagination.default', ['members'=>$members]) !!}

    <?php /*
    <div class="row">
        <div class="col-sm-6 text-start">{!! $pagination !!}</div>
        <div class="col-sm-6 text-end">{{ $results }}</div>
    </div>
    */ ?>
</form>