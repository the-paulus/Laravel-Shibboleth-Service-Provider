<?php
use ThePaulus\Shibboleth\Controllers\ShibbolethController;

Route::group(['middleware' => 'web'], function() {
  // Login Route (Shibboleth)
  Route::get('login', 'ThePaulus\\Shibboleth\\Controllers\\ShibbolethController@create');
  // Logout Route (Shibboleth and Local)
  Route::get('logout', 'ThePaulus\\Shibboleth\\Controllers\\ShibbolethController@destroy');
  // Shibboleth IdP Callback
  Route::get('idp', 'ThePaulus\\Shibboleth\\Controllers\\ShibbolethController@idpAuthorize');

  // Login Route (Local)
  Route::get('local', 'ThePaulus\\Shibboleth\\Controllers\\ShibbolethController@localCreate');
  // Login Callback (Local)
  Route::post('local', 'ThePaulus\\Shibboleth\\Controllers\\ShibbolethController@localAuthorize');

  // Login Callback (Emulated)
  Route::get('emulated/idp', 'ThePaulus\\Shibboleth\\Controllers\\ShibbolethController@emulateIdp');
  // Login Callback (Emulated)
  Route::post('emulated/idp', 'ThePaulus\\Shibboleth\\Controllers\\ShibbolethController@emulateIdp');
  // Login Route (Emulated)
  Route::get('emulated/login', 'ThePaulus\\Shibboleth\\Controllers\\ShibbolethController@emulateLogin');
  // Logout Route (Emulated)
  Route::get('emulated/logout', 'ThePaulus\\Shibboleth\\Controllers\\ShibbolethController@emulateLogout');
});
