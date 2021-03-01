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
      return  (new Actions\Login($app, $request))->newSession();
    })->bind('new_session');

    $app->post('/login', function (Request $request) use ($app) {
      return  (new Actions\Login($app, $request))->createSession();
    })->bind('create_session');

    $app->get('/logout', function (Request $request) use ($app) {
      return  (new Actions\Login($app, $request))->destroySession();
    })->bind('destroy_session');

    $app->get('/signup', function (Request $request) use ($app) {
      return  (new Actions\Signup($app, $request))->newSignup();
    })->bind('new_signup');

    $app->post('/signup', function (Request $request) use ($app) {
      return  (new Actions\Signup($app, $request))->createSignup();
    })->bind('create_signup');
  }
}