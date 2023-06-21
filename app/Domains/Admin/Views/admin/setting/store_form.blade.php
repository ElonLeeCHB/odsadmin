@extends('admin.app')

@section('pageJsCss')
@endsection

@section('columnLeft')
  @include('admin.common.column_left')
@endsection

@section('content')
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="float-end">
        <button type="submit" form="form-store" data-bs-toggle="tooltip" title="" class="btn btn-primary" data-bs-original-title="Save" aria-label="Save"><i class="fa-solid fa-floppy-disk"></i></button>
        <a href="http://opencart4011.test/backend/index.php?route=setting/store&amp;user_token=1c390f293d50ae6fb7341381d08e5114" data-bs-toggle="tooltip" title="Back" class="btn btn-light"><i class="fa-solid fa-reply"></i></a>
      </div>
      <h1>Stores</h1>
      <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="http://opencart4011.test/backend/index.php?route=common/dashboard&amp;user_token=1c390f293d50ae6fb7341381d08e5114">Home</a></li>
                  <li class="breadcrumb-item"><a href="http://opencart4011.test/backend/index.php?route=setting/store&amp;user_token=1c390f293d50ae6fb7341381d08e5114">Stores</a></li>
                  <li class="breadcrumb-item"><a href="http://opencart4011.test/backend/index.php?route=setting/store|form&amp;user_token=1c390f293d50ae6fb7341381d08e5114">Settings</a></li>
              </ol>
    </div>
  </div>
  <div class="container-fluid">
    <div class="card">
      <div class="card-header"><i class="fa-solid fa-pencil"></i> Edit Store</div>
      <div class="card-body">
        <form id="form-store" action="http://opencart4011.test/backend/index.php?route=setting/store|save&amp;user_token=1c390f293d50ae6fb7341381d08e5114" method="post" data-oc-toggle="ajax">
          <ul class="nav nav-tabs">
            <li class="nav-item"><a href="#tab-store" data-bs-toggle="tab" class="nav-link active">Store</a></li>
          </ul>
          <div class="tab-content">
            <div id="tab-store" class="tab-pane active">
              <div class="row mb-3 required">
                <label for="input-name" class="col-sm-2 col-form-label">Store Name</label>
                <div class="col-sm-10">
                  <input type="text" name="config_name" value="中華一餅" placeholder="Store Name" id="input-name" class="form-control">
                  <div id="error-name" class="invalid-feedback"></div>
                </div>
              </div>
              <div class="row mb-3 required">
                <label for="input-owner" class="col-sm-2 col-form-label">Store Owner</label>
                <div class="col-sm-10">
                  <input type="text" name="config_owner" value="王陽明" placeholder="Store Owner" id="input-owner" class="form-control">
                  <div id="error-owner" class="invalid-feedback"></div>
                </div>
              </div>
              <div class="row mb-3 required">
                <label for="input-address" class="col-sm-2 col-form-label">Address</label>
                <div class="col-sm-10">
                  <textarea name="config_address" rows="5" placeholder="Address" id="input-address" class="form-control">光華路68號</textarea>
                  <div id="error-address" class="invalid-feedback"></div>
                </div>
              </div>
              <div class="row mb-3">
                <label for="input-geocode" class="col-sm-2 col-form-label">Geocode</label>
                <div class="col-sm-10">
                  <input type="text" name="config_geocode" value="" placeholder="Geocode" id="input-geocode" class="form-control">
                  <div class="form-text">Please enter your store location geocode manually.</div>
                </div>
              </div>
              <div class="row mb-3 required">
                <label for="input-email" class="col-sm-2 col-form-label">E-Mail</label>
                <div class="col-sm-10">
                  <input type="text" name="config_email" value="admin@example.org" placeholder="E-Mail" id="input-email" class="form-control">
                  <div id="error-email" class="invalid-feedback"></div>
                </div>
              </div>
              <div class="row mb-3 required">
                <label for="input-telephone" class="col-sm-2 col-form-label">Telephone</label>
                <div class="col-sm-10">
                  <input type="text" name="config_telephone" value="28825252" placeholder="Telephone" id="input-telephone" class="form-control">
                  <div id="error-telephone" class="invalid-feedback"></div>
                </div>
              </div>
              <div class="row mb-3">
                <label for="input-image" class="col-sm-2 col-form-label">Image</label>
                <div class="col-sm-10">
                  <div class="card image">
                    <img src="http://opencart4011.test/image/cache/no_image-100x100.png" alt="" title="" id="thumb-image" data-oc-placeholder="http://opencart4011.test/image/cache/no_image-100x100.png" class="card-img-top"> <input type="hidden" name="config_image" value="" id="input-image">
                    <div class="card-body">
                      <button type="button" data-oc-toggle="image" data-oc-target="#input-image" data-oc-thumb="#thumb-image" class="btn btn-primary btn-sm btn-block"><i class="fa-solid fa-pencil"></i> Edit</button>
                      <button type="button" data-oc-toggle="clear" data-oc-target="#input-image" data-oc-thumb="#thumb-image" class="btn btn-warning btn-sm btn-block"><i class="fa-regular fa-trash-can"></i> Clear</button>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row mb-3">
                <label for="input-open" class="col-sm-2 col-form-label">Opening Times</label>
                <div class="col-sm-10">
                  <textarea name="config_open" rows="5" placeholder="Opening Times" id="input-open" class="form-control"></textarea>
                  <div class="form-text">Fill in your stores opening times.</div>
                </div>
              </div>
              <div class="row mb-3">
                <label for="input-comment" class="col-sm-2 col-form-label">Comment</label>
                <div class="col-sm-10">
                  <textarea name="config_comment" rows="5" placeholder="Comment" id="input-comment" class="form-control"></textarea>
                  <div class="form-text">This field is for any special notes you would like to tell the customer i.e. Store does not accept cheques.</div>
                </div>
              </div>
            </div>
          </div>
      </div>
    </div>
  </div>
</div>

@endsection
