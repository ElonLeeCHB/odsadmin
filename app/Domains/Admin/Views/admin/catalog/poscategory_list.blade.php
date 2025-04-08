<form id="form-term" method="post" data-oc-toggle="ajax" data-oc-load="{{ $list_url }}" data-oc-target="#term">
  <div class="table-responsive">
    <table class="table table-bordered table-hover">
      <thead>
        <tr>
          <td class="text-center" style="width: 1px;"><input type="checkbox" onclick="$('input[name*=\'selected\']').prop('checked', $(this).prop('checked'));" class="form-check-input"/></td>
          <td class="text-start"><a href="{{ $sort_name }}" @if($sort=='name') class="{{ $order }}" @endif>{{ $lang->column_name }}</a></td>
          <td class="text-start">{{ $lang->column_sort_order }}</td>
          <td class="text-start">{{ $lang->column_is_active }}</td>
          <td class="text-end">{{ $lang->column_action }}</td>
        </tr>
      </thead>
      <tbody>
        @foreach($categories as $term)
        <tr>
          <td class="text-center"><input type="checkbox" name="selected[]" value="{{ $term->id }}" class="form-check-input"/></td>
          <td class="text-start">{{ $term->name }}</td>
          <td class="text-start">{{ $term->sort_order }}</td>
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
</form>