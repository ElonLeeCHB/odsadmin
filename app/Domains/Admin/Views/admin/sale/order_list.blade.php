<form id="form-order" method="post" data-oc-toggle="ajax" data-oc-load="{{ route('lang.admin.sale.orders.list') }}" data-oc-target="#order">
  @csrf
  @method('POST')
  <div class="table-responsive">
    <table class="table table-bordered table-hover">
      <thead>
        <tr>
          <td class="text-center" style="width: 1px;"><input type="checkbox" onclick="$('input[name*=\'selected\']').prop('checked', $(this).prop('checked'));" class="form-check-input"/></td>
          <td></td>
          <td class="text-start"><a href="{{ $sort_code }}" @if($sort=='code') class="{{ $order }}" @endif>{{ $lang->column_code }}</a></td>
          <td class="text-start"><a href="{{ $sort_name }}" @if($sort=='name') class="{{ $order }}" @endif>{{ $lang->column_name }}</a></td>
          <td class="text-start">{{ $lang->column_phone }}</td>
          <td class="text-start">{{ $lang->column_shipping_road_abbr }}</td>
          <td class="text-start"><a href="{{ $sort_delivery_date }}" @if($sort=='delivery_date') class="{{ $order }}" @endif>{{ $lang->column_delivery_date }}</a></td>
          <td class="text-start">{{ $lang->column_status }}</td>
          <td class="text-end">{{ $lang->column_action }}</td>
        </tr>
      </thead>
      <tbody>
        <?php $i = 1; ?>
        @foreach($orders as $row)
        <?php 
          $page = request()->input('page') ?? 1;
          $no = ($page-1) * 10 + $i;
        ?>
        <tr>
          <td class="text-center"><input type="checkbox" name="selected[]" value="{{ $row->id }}" class="form-check-input"/></td>
          <td>{{ $no ?? '' }}</td>
          <td class="text-start">{{ $row->code }}</td>
          <td class="text-start">{{ $row->personal_name }}</td>
          <td class="text-start">mob:{{ $row->mobile }}<BR>tel:{{ $row->telephone }}</td>
          <td class="text-start">{{ $row->shipping_road_abbr }}</td>
          <td class="text-start">{{ $row->delivery_date }}</td>
          <td class="text-start">{{ $row->status_name }}</td>
          <td class="text-end"><a href="{{ $row->edit_url }}" data-bs-toggle="tooltip" title="{{ $lang->button_edit }}" class="btn btn-primary"><i class="fas fa-pencil-alt"></i></a></td>
        </tr>
        <?php $i++;?>
        @endforeach
      </tbody>
    </table>
  </div>
  {!! $orders->links('admin.pagination.default') !!}

    <?php /*
    <div class="row">
        <div class="col-sm-6 text-start">{!! $pagination !!}</div>
        <div class="col-sm-6 text-end">{{ $results }}</div>
    </div>
    */ ?>
</form>