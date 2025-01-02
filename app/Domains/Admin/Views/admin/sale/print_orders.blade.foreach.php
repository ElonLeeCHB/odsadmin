@foreach($orders as $key => $order)
  @foreach($order['product_data'] as $main_category_code => $category)
    @foreach($category['Columns'] ?? [] as $type_name => $type)
      @foreach($type ?? [] as $option_value_id => $name)

      @endforeach

    @endforeach
  @endforeach
@endforeach


<td style="width:24px;" class=" fw-bold">{{ $category['name'] }}</td>