
<strong>需求日期： {{ $statics['required_date'] ?? ''}}</strong> <BR>
上次更新時間： {{ $statics['cache_created_at'] ?? '' }} &nbsp; 上下午分界點：13:00 開始算下午<BR>
  套餐數:{{ $info['total_package'] ?? 0 }}, &nbsp;
  盒餐:{{ $info['total_lunchbox'] ?? 0 }}, &nbsp;
  便當：{{ $info['total_bento'] ?? 0 }}, &nbsp;
  油飯盒:{{ $info['total_oilRiceBox'] ?? 0 }}, &nbsp;
  3吋潤餅:{{ $info['total_3inlumpia'] ?? 0 }}, &nbsp;
  6吋潤餅:{{ ceil($info['total_6inlumpia']) ?? 0 }}({{ $info['total_3inlumpia'] ?? 0 }}/2), &nbsp;
  小刈包:{{ $info['total_small_guabao'] ?? 0 }}, &nbsp;
  大刈包:{{ $info['total_big_guabao'] ?? 0 }}, &nbsp;

