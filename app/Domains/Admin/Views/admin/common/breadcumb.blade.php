      <ol class="breadcrumb">
        @foreach($breadcumbs as $breadcumb)
          <li class="breadcrumb-item"><a href="{{ $breadcumb->href }}" @if(!empty($breadcumb->cursor)) style="cursor: {{ $breadcumb->cursor }}" @endif >{{ $breadcumb->text }}</a></li>
        @endforeach
      </ol>