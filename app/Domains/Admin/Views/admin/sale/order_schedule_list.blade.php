<form id="form-order_schedule" method="post" action="{{ $save_url }}" data-oc-toggle="ajax" data-oc-target="#schedule">
  @csrf
  @method('POST')
  <div class="table-responsive">
    <table class="table table-bordered table-hover">
      <thead>
        <tr>
          <td class="text-end"></td>
          <td class="text-start">訂單編號</td>
          <td class="text-start">地址簡稱</td>
          <td class="text-start">送達時間範圍</td>
          <td class="text-start">出餐時間</td>
          <td class="text-start">製餐時間</td>
          <td class="text-start">製餐順序</td>
          <td class="text-start">狀態碼</td>
        </tr>
      </thead>
      <tbody>
        <?php $i = 1;?>
        @foreach($orders as $row)
        <tr>
          <td class="text-center">{{ $i }}</td>
          <td class="text-start"><a href="{{ $row->edit_url }}" title="訂單連結" target="_blank">{{ $row->code }}</a></td>
          <td class="text-start">{{ $row->shipping_road_abbr }}</td>
          <td class="text-start">{{ $row->delivery_time_range }}</td>
          <td class="text-start"><input type="text" name="orders[{{ $row->id }}][production_ready_time]" value="{{ $row->production_ready_time }}" class="form-control"></td>
          <td class="text-start"><input type="text" name="orders[{{ $row->id }}][production_start_time]" value="{{ $row->production_start_time }}" class="form-control"></td>
          <td class="text-start"><input type="text" name="orders[{{ $row->id }}][sort_order]" value="{{ $row->sort_order }}" class="form-control"></td>
          <td>{{ $row->status_text }}</td>
        </tr>

        <?php $i++;?>
        @endforeach
      </tbody>
    </table>
  </div>
</form>