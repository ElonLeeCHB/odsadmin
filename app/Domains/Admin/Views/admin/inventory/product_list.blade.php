<form id="form-product" method="post" data-oc-toggle="ajax" data-oc-load="{{ route('lang.admin.member.members.list') }}" data-oc-target="#member">
  @csrf
  @method('POST')
  <div class="table-responsive">
    <table class="table table-bordered table-hover">
      <thead>
        <tr>
          <td class="text-center" style="width: 1px;"><input type="checkbox" onclick="$('input[name*=\'selected\']').prop('checked', $(this).prop('checked'));" class="form-check-input"/></td>
          <td class="text-end"><a href="{{ $sort_id }}" @if($sort=='id') class="{{ $order }}" @endif>{{ $lang->column_id }}</a></td>
          <td class="text-start"><a href="{{ $sort_name }}" @if($sort=='name') class="{{ $order }}" @endif>{{ $lang->column_name }}</a></td>
          <td class="text-start"><a href="{{ $sort_specification }}" @if($sort=='specification') class="{{ $order }}" @endif>{{ $lang->column_specification }}</a></td>
          <td class="text-start"><a href="{{ $sort_accounting_category_code }}" @if($sort=='accounting_category_code') class="{{ $order }}" @endif>{{ $lang->column_accounting_category_code }}</a></td>
          <td class="text-start">{{ $lang->column_supplier_short_name }}</td>
          <td class="text-start">{{ $lang->column_is_inventory_managed }}</td>
          <td class="text-start">{{ $lang->column_is_active }}</td>
          <td class="text-end">{{ $lang->column_action }}</td>
        </tr>
      </thead>
      <tbody>
        @foreach($products as $row)
        <tr>
          <td class="text-center"><input type="checkbox" name="selected[]" value="{{ $row->id }}" class="form-check-input"/></td>
          <td class="text-end">{{ $row->id }}</td>
          <td class="text-start">{{ $row->name }}</td>
          <td class="text-start">{{ $row->specification }}</td>
          <td class="text-start">{{ $row->accounting_category_name }}</td>
          <td class="text-start">{{ $row->supplier_short_name }}</td>
          <td class="text-start">@if($row->is_inventory_managed)
                                  {{ $lang->text_yes }}
                                @else
                                  {{ $lang->text_no }}
                                @endif</td>
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
  {!! $pagination !!}
</form>