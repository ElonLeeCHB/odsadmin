<nav id="column-left">
  <div id="navigation"><span class="fas fa-bars"></span> {{ $navigation }}</div>
  <ul id="menu">
    @foreach( $menus as $i => $menu )
    <li id="{{ $menu['id'] }}">
      @if($menu['href'])
      <a href="{{ $menu['href'] }}"><i class="{{ $menu['icon'] }}"></i> {{ $menu['name'] }}</a>
      @else
      <a href="#collapse-{{ $i }}" data-bs-toggle="collapse" class="parent collapsed"><i class="{{ $menu['icon'] }}"></i> {{ $menu['name'] }}</a>
      @endif
      @if($menu['children'])
      <ul id="collapse-{{ $i }}" class="collapse">
        @foreach( $menu['children'] as $j => $children_1 )
        <li>
          @if(!empty($children_1['href']))
          <a href="{{ $children_1['href'] }}"><i class="{{ $children_1['icon'] }}"></i> {{ $children_1['name'] }}</a>
          @else
          <a href="#collapse-{{ $i }}-{{ $j }}" data-bs-toggle="collapse" class="parent collapsed"><i class="{{ $children_1['icon'] }}"></i> {{ $children_1['name'] }}</a>
          @endif
          @if($children_1['children'])
          <ul id="collapse-{{ $i }}-{{ $j }}" class="collapse">
            @foreach( $children_1['children'] as $m => $children_2 )
            <li>
              @if(!empty($children_2['href']))
              <a href="{{ $children_2['href'] }}"><i class="{{ $children_2['icon'] }}"></i> {{ $children_2['name'] }}</a>
              @else
              <a href="#collapse-{{ $i }}-{{ $j }}-{{ $m }}" data-bs-toggle="collapse" class="parent collapsed"><i class="{{ $children_2['icon'] }}"></i> {{ $children_2['name'] }}</a>
              @endif 
              @if(!empty($children_2['children']))
              <ul id="collapse-{{ $i }}-{{ $j }}-{{ $m }}" class="collapse">
                @foreach( $children_2['children'] as $n => $children_3 )
                <li><a href="{{ $children_3['href'] }}">{{ $children_3['name'] }}</a></li>
                @endforeach
              </ul>
              @endif
            </li>
            @endforeach
          </ul>
          @endif
        </li>
        @endforeach
      </ul>
      @endif
    </li>
    @endforeach
    <?php /* 
      <li id="menu-levels">
         <a href="#collapse-7" data-bs-toggle="collapse" class="parent collapsed"><i class="fas fa-cog"></i> Level 1</a>
         <ul id="collapse-7" class="collapse">
            <li><a href="">example</a>
            </li>
            <li>
               <a href="#collapse-7-2" data-bs-toggle="collapse" class="parent collapsed">Level 2</a>
               <ul id="collapse-7-2" class="collapse">
                  <li><a href="">example</a>
                  </li>
                  <li>
                     <a href="#collapse-7-2-6" data-bs-toggle="collapse" class="parent collapsed">Level 3</a>
                     <ul id="collapse-7-2-6" class="collapse">
                        <li><a href="http://opencart4x.test/backend/index.php?route=localisation/return_status&amp;user_token=a04539592e4472b5201c41a7e23a6e75">example</a></li>
                        <li><a href="http://opencart4x.test/backend/index.php?route=localisation/return_action&amp;user_token=a04539592e4472b5201c41a7e23a6e75">example</a></li>
                        <li><a href="http://opencart4x.test/backend/index.php?route=localisation/return_reason&amp;user_token=a04539592e4472b5201c41a7e23a6e75">example</a></li>
                     </ul>
                  </li>
               </ul>
            </li>
         </ul>
      </li>
      */ ?>
  </ul>
  <div id="stats">
    <ul>
      <li>
        <div>Orders Completed <span class="float-end">0%</span></div>
        <div class="progress">
          <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"><span class="sr-only">0%</span></div>
        </div>
      </li>
      <li>
        <div>Orders Processing <span class="float-end">0%</span></div>
        <div class="progress">
          <div class="progress-bar progress-bar-warning" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"><span class="sr-only">0%</span></div>
        </div>
      </li>
      <li>
        <div>Other Statuses <span class="float-end">0%</span></div>
        <div class="progress">
          <div class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"><span class="sr-only">0%</span></div>
        </div>
      </li>
    </ul>
  </div>
</nav>
