<!DOCTYPE html>
<html dir="ltr" lang="en">
  <head>
    <meta charset="UTF-8"/>
    <title>{{ $appName }}</title>
    <base href="{{ $base }}"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <meta http-equiv="cache-control" content="no-cache">
    <meta http-equiv="expires" content="0">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link  href="{{ asset('assets-admin/stylesheet/bootstrap.css') }}" rel="stylesheet" media="screen"/>
    <link  href="{{ asset('assets-admin/stylesheet/fonts/fontawesome/css/all.min.css') }}" rel="stylesheet" type="text/css"/>
    <link  href="{{ asset('assets-admin/stylesheet/stylesheet.css') }}" rel="stylesheet" type="text/css"/>
    <script src="{{ asset('assets-admin/javascript/jquery/jquery-3.6.1.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('assets-admin/javascript/jquery/datetimepicker/moment.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('assets-admin/javascript/jquery/datetimepicker/moment-with-locales.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('assets-admin/javascript/jquery/datetimepicker/daterangepicker.js') }}" type="text/javascript"></script>
    <link  href="{{ asset('assets-admin/javascript/jquery/datetimepicker/daterangepicker.css') }}" rel="stylesheet" type="text/css"/>
    <script src="{{ asset('assets-admin/javascript/ckeditor/ckeditor.js') }}" type="text/javascript"></script>
    <script src="{{ asset('assets-admin/javascript/ckeditor/adapters/jquery.js') }}" type="text/javascript"></script>
    <script src="{{ asset('assets-admin/javascript/common.js') }}" type="text/javascript"></script>
    <script src="{{ asset('assets/javascript/common.js') }}" type="text/javascript"></script>

    {{-- 頁面套件 --}}
    @yield('pageJsCss')

    {{-- 自訂 --}}
    @yield('customJsCss')

    <style>
.nav-select {
  margin-top: 10px;
  margin-left: auto;
  margin-right: auto;
  display: block;
  font-weight: bolder;
  font-size: larger;
}
    </style>
  </head>
  <body>
    <div id="container">
      <div id="alert" class="toast-container position-fixed top-0 end-0 p-3">
        @if (session()->has('warning'))
        <div class="alert alert-danger alert-dismissible"><i class="fa-solid fa-circle-exclamation"></i> {{ session()->get("warning") }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
      </div>
      <header id="header" class="navbar navbar-expand navbar-light bg-light">
        <div class="container-fluid">
          <a href="javascript:void(0){{-- route('lang.admin.dashboard') --}}" class="navbar-brand d-none d-xxl-block"><img width="180" src="{{ asset('assets-admin/image/logo.png') }}" alt="Chinabing" title="Chinabing"></a>

          @if (Auth::check())
            <button type="button" id="button-menu" class="btn btn-link d-inline-block d-xxl-none"><i class="fa-solid fa-bars"></i></button>
            <ul class="nav navbar-nav">
              <li class="nav-item dropdown">
                <select class="nav-select form-control" id="input-nav_location_id" name="nav_location_id">
                  {{--<option value="2" @if($location_id==2) selected @endif>和平門市</option>--}}
                  <option value="2" selected>和平門市</option>
                </select>
              </li>
              <li id="nav-notification" class="nav-item dropdown">
                <a href="#" data-bs-toggle="dropdown" class="nav-link dropdown-toggle"><i class="fa-regular fa-bell"></i></a>
                <div class="dropdown-menu dropdown-menu-end">
                  <span class="dropdown-item text-center">無資料</span>
                </div>
              </li>
              </li>
              <li id="nav-profile" class="nav-item dropdown">
                <a href="#" data-bs-toggle="dropdown" class="nav-link dropdown-toggle"><img src="{{ asset('assets-admin/image/profile.png') }}" alt="{{ $authUser->name ?? '' }}" title="{{ $authUser->username ?? '' }}" class="rounded-circle"/><span class="d-none d-md-inline d-lg-inline">&nbsp;&nbsp;&nbsp;{{ $authUser->name ?? '' }} <i class="fa-solid fa-caret-down fa-fw"></i></span></a>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><a href="javascript:void(0)" class="dropdown-item"><i class="fa-solid fa-user-circle fa-fw"></i> 我的帳戶</a></li>

                  <li>
                    <hr class="dropdown-divider">
                  </li>
                  <li>
                    <h6 class="dropdown-header">快捷選單</h6>
                  </li>
                  <li><a href="{{ route('lang.admin.sale.orders.index') }}" target="_blank" class="dropdown-item"><i class="fa-solid fa-file fa-fw"></i> 訂單作業</a></li>
                  <li><a href="{{ route('lang.admin.member.members.index') }}" target="_blank" class="dropdown-item"><i class="fa-solid fa-file fa-fw"></i> 客戶作業</a></li>
                </ul>
              </li>
              <li id="nav-logout" class="nav-item">
                    <a href="{{ route('lang.admin.logout') }}" class="nav-link" onclick="event.preventDefault();document.getElementById('logout-form').submit();">
                      <i class="fa-solid fa-sign-out"></i> <span class="d-none d-md-inline">Logout</span>
                    </a>
                <form id="logout-form" action="{{ route('lang.admin.logout') }}" method="POST" class="d-none">
                  @csrf
                  </form>
                  </li>
            </ul>
          @endif
        </div>
      </header>
      @yield('columnLeft')

      @yield('content')

      <footer id="footer"><a href="javascript:void(0)">中華一餅</a> &copy; 2009-2023 All Rights Reserved.</footer>

      @yield('buttom')

    </div>

    <script src="{{ asset('assets-admin/javascript/bootstrap/js/bootstrap.bundle.min.js') }}" type="text/javascript"></script>
    <script type="text/javascript"><!--

      $.ajaxSetup({
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
          'Origin': 'http://localhost:60502'
        }
      });
    </script>
{{--
<script>
$.ajax({
    url: "http://dods.dtstw.com/api/wwwv2/hello",
    type: "get",
    beforeSend: function(xhr) {
        xhr.setRequestHeader("Origin", "https://localhost:60502"); //沒用！
        xhr.setRequestHeader("X-Api-Key", "3bDCn7eX3ARkdyYT"); //有用！
    },
    success: function(response) {
        console.log("Success:", response);
    },
    error: function(xhr, status, error) {
        console.error("Error:", status, error);
    }
});
</script>
--}}
  </body>
</html>
