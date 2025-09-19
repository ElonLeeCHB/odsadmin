<form id="form-term" method="post" data-oc-toggle="ajax" data-oc-load="{{ $list_url }}" data-oc-target="#bom">
  <div class="table-responsive">
    <table class="table table-bordered table-hover">
      <thead>
        <tr>
          <td class="text-start"><a href="{{ $sort_id }}" @if($sort=='id') class="{{ $order }}" @endif>{{ $lang->column_id }}</a></td>
          <td class="text-start">{{ $lang->column_product_name }}</td>
          <td class="text-start"><a href="{{ $sort_effective_date }}" @if($sort=='effective_date') class="{{ $order }}" @endif>{{ $lang->column_effective_date }}</a></td>
          <td class="text-start"><a href="{{ $sort_expiry_date }}" @if($sort=='expiry_date') class="{{ $order }}" @endif>{{ $lang->column_expiry_date }}</a></td>
          <td class="text-start">版本號</td>
          <td class="text-start">成本</td>
          <td class="text-start">{{ $lang->column_is_active }}</td>
          <td class="text-end">{{ $lang->column_action }}</td>
        </tr>
      </thead>
      <tbody>
        @foreach($boms as $row)
        <tr>
          <td class="text-start">{{ $row->id }}</td>
          <td class="text-start">{{ $row->product_name }}</td>
          <td class="text-start">{{ $row->effective_date }}</td>
          <td class="text-start">{{ $row->expiry_date }}</td>
          <td class="text-start">{{ $row->version }}</td>
          <td class="text-start">{{ $row->total }}</td>
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
  {!! $boms->links('admin.pagination.default') !!}
</form>
