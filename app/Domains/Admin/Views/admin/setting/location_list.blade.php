<form id="form-location" method="post" data-oc-toggle="ajax" data-oc-load="{{ $list_url }}" data-oc-target="#location">
  <div class="table-responsive">
    <table class="table table-bordered table-hover">
      <thead>
        <tr>
          <td class="text-center" style="width: 1px;"><input type="checkbox" onclick="$('input[name*=\'selected\']').prop('checked', $(this).prop('checked'));" class="form-check-input"/></td>
          <td class="text-start"><a href="{{ $sort_short_name }}" @if($sort=='short_name') class="{{ $order }}" @endif>{{ $lang->column_short_name }}</a></td>
          <td class="text-end">{{ $lang->column_action }}</td>
        </tr>
      </thead>
      <tbody>
        @foreach($locations as $location)
        <tr>
          <td class="text-center"><input type="checkbox" name="selected[]" value="{{ $location->id }}" class="form-check-input"/></td>
          <td class="text-start">{{ $location->short_name }}</td>
          <td class="text-end"><a href="{{ $location->edit_url }}" data-bs-toggle="tooltip" title="Edit" class="btn btn-primary"><i class="fa-solid fa-pencil"></i></a></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  {!! $locations->links('admin.pagination.default', ['locations'=>$locations]) !!}
</form>