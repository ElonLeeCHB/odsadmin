@extends('admin.app')

@section('content')
<div id="content">
  <div class="container-fluid">
    <br/><br/>
    <div class="row justify-content-sm-center">
      <div class="col-sm-4 col-md-6">
        <div class="card">
          <div class="card-header"><i class="fas fa-lock"></i> {{ $lang->text_login }}</div>
          <div class="card-body">
              <form id="form-login" action="{{ route('lang.admin.login') }}" method="post">
                @csrf
                @if($errors->has('password'))                   
                <div class="alert alert-danger alert-dismissible"><i class="fas fa-exclamation-circle"></i> {{ $errors->first('password') }}</div>
                @endif

                @if(session()->has('error_warning'))
                <div class="alert alert-danger alert-dismissible"><i class="fas fa-exclamation-circle"></i> {{ session('error_warning') }}</div>
                @endif

                <div class="row mb-3">
                  <label for="input-email" class="form-label">{{ $lang->entry_email }}</label>
                  <div class="input-group">
                    <div class="input-group-text"><i class="fas fa-user"></i></div>
                    <input type="text" name="email" value="" placeholder="{{ $lang->entry_email }}" id="input-email" class="form-control"/>
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="input-password" class="form-label">{{ $lang->entry_password }}</label>
                  <div class="input-group mb-2">
                    <div class="input-group-text"><i class="fas fa-lock"></i></div>
                    <input type="password" name="password" value="" placeholder="{{ $lang->entry_password }}" id="input-password" class="form-control"/>
                  </div>
                  <?php /*<div class="mb-3"><a href="{{ route('lang.admin.password.request') }}">{{ $lang->text_forgotten }}</a></div>*/ ?>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> {{ $lang->button_login }}</button>
                </div>
              </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('buttom')
<script type="text/javascript"> 
$(document).ready(function(){

  // 重新取得 csrf token
  $('form').submit(function(e) {
    e.preventDefault(); 
    e.returnValue = false; 
    var $form = $(this); 
    $.ajax({ 
      method: "get",
      url: '{{ $refresh_token_url }}', 
      context: $form, 
      success: function (response) { 
        $('meta[name="csrf-token"]').attr('content', response); 
        $('input[name="_token"]').val(response); 
        this.off('submit'); 
        this.submit(); 
        console.log('success: ')
        console.log(JSON.stringify(response))
      }, 
      error: function (thrownError) { 
        console.log('thrownError: ')
        console.log(JSON.stringify(thrownError))
      } 
    });
  }); 
}); 
</script> 
@endsection