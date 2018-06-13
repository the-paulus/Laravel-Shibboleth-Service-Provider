<?php
use ThePaulus\Shibboleth\Controllers\ShibbolethController;

Route::group(['middleware' => 'web'], function() {
  // Login Route (Shibboleth)
  Route::get('login', ShibbolethController::class . '@create');
  // Logout Route (Shibboleth and Local)
  Route::get('logout', ShibbolethController::class . '@destroy');
  // Shibboleth IdP Callback
  Route::get('idp', ShibbolethController::class . '@idpAuthorize');

  // Login Route (Local)
  Route::get('local', ShibbolethController::class . '@localCreate');
  // Login Callback (Local)
  Route::post('local', ShibbolethController::class . '@localAuthorize');

  // Login Callback (Emulated)
  Route::get('emulated/idp', ShibbolethController::class . '@emulateIdp');
  // Login Callback (Emulated)
  Route::post('emulated/idp', ShibbolethController::class . '@emulateIdp');
  // Login Route (Emulated)
  Route::get('emulated/login', ShibbolethController::class . '@emulateLogin');
  // Logout Route (Emulated)
  Route::get('emulated/logout', ShibbolethController::class . '@emulateLogout');
});
