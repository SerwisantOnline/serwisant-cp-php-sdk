<?php

namespace Serwisant\SerwisantCp;

use Silex;
use Symfony\Component\HttpFoundation\Request;

class RouterCp implements Router
{
  public function createRoutes(Silex\Application $app)
  {
    $app->get('/', function (Request $request) use ($app) {
      return (new Actions\Dashboard($app, $request))->dashboard();
    })->bind('dashboard');

    $app->get('/login', function (Request $request) use ($app) {
      return (new Actions\Login($app, $request))->newSession();
    })->bind('new_session');

    $app->post('/login', function (Request $request) use ($app) {
      return (new Actions\Login($app, $request))->createSession();
    });

    $app->get('/logout', function (Request $request) use ($app) {
      return (new Actions\Login($app, $request))->destroySession();
    })->bind('destroy_session');

    $app->get('/signup', function (Request $request) use ($app) {
      return (new Actions\Signup($app, $request))->newSignup();
    })->bind('new_signup');

    $app->post('/signup', function (Request $request) use ($app) {
      return (new Actions\Signup($app, $request))->createSignup();
    });

    $app->get('/signup/{token}', function (Request $request, $token) use ($app) {
      return (new Actions\Signup($app, $request))->signupConfirmation($token);
    });

    $app->get('/reset_password', function (Request $request) use ($app) {
      return (new Actions\PasswordReset($app, $request))->newReset();
    })->bind('new_password_reset');

    $app->post('/reset_password', function (Request $request) use ($app) {
      return (new Actions\PasswordReset($app, $request))->createReset();
    });

    $app->get('/set_password/{token}', function (Request $request, $token) use ($app) {
      return (new Actions\PasswordReset($app, $request))->newPassword($token);
    });

    $app->post('/set_password', function (Request $request) use ($app) {
      return (new Actions\PasswordReset($app, $request))->createPassword();
    })->bind('set_password');
  }
}