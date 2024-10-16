<form id="form-term" method="post" data-oc-toggle="ajax" data-oc-load="{{ $list_url }}" data-oc-target="#term">
  <div class="table-responsive">
    <table class="table table-bordered table-hover">
      <thead>
        <tr>
          <td class="text-start"><a href="{{ $sort_id }}" @if($sort=='id') class="{{ $order }}" @endif>{{ $lang->column_id }}</a></td>
          <td class="text-start">{{ $lang->column_product_name }}</td>
          <td class="text-start"><a href="{{ $sort_effective_date }}" @if($sort=='effective_date') class="{{ $order }}" @endif>{{ $lang->column_effective_date }}</a></td>
          <td class="text-start"><a href="{{ $sort_expiry_date }}" @if($sort=='expiry_date') class="{{ $order }}" @endif>{{ $lang->column_expiry_date }}</a></td>
          <td class="text-start">成本</td>
          <td class="text-start">{{ $lang->column_is_active }}</td>
          <td class="text-end">{{ $lang->column_action }}</td>
        </tr>
      </thead>
      <tbody>
        @foreach($boms as $term)
        <tr>
          <td class="text-start">{{ $term->id }}</td>
          <td class="text-start">{{ $term->product_name }}</td>
          <td class="text-start">{{ $term->effective_date }}</td>
          <td class="text-start">{{ $term->expiry_date }}</td>
          <td class="text-start">{{ $term->total }}</td>
          <td class="text-start">@if($term->is_active)
                                  {{ $lang->text_yes }}
                                @else
                                  {{ $lang->text_no }}
                                @endif</td>
          <td class="text-end"><a href="{{ $term->edit_url }}" data-bs-toggle="tooltip" title="Edit" class="btn btn-primary"><i class="fa-solid fa-pencil"></i></a></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  {!! $boms->links('admin.pagination.default') !!}
</form>
