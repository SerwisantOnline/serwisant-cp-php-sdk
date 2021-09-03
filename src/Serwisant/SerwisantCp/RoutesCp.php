<?php

namespace Serwisant\SerwisantCp;

use Symfony\Component\HttpFoundation\Request;

class RoutesCp extends Routes
{
  use Traits\RoutesNoToken;

  public function getRoutes()
  {
    $cp = $this->app['controllers_factory'];

    // USER

    $cp->get('/login', function (Request $request, Token $token) {
      return (new Actions\Login($this->app, $request, $token))->new();
    })
      ->before($this->expectAccessTokens())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('new_session');

    $cp->post('/login/resolve', function (Request $request, Token $token) {
      return (new Actions\Login($this->app, $request, $token))->resolveCredential();
    })
      ->before($this->expectAccessTokens())
      ->before($this->expectJson())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('new_session_resolve_login');

    $cp->post('/login', function (Request $request, Token $token) {
      return (new Actions\Login($this->app, $request, $token))->create();
    })
      ->before($this->expectAccessTokens())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter());

    $cp->get('/logout', function (Request $request, Token $token) {
      return (new Actions\Login($this->app, $request, $token))->destroy();
    })
      ->before($this->expectAccessTokens())
      ->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('destroy_session');

    $cp->get('/signup', function (Request $request, Token $token) {
      return (new Actions\Signup($this->app, $request, $token))->new();
    })
      ->before($this->expectAccessTokens())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('new_signup');

    $cp->post('/signup', function (Request $request, Token $token) {
      return (new Actions\Signup($this->app, $request, $token))->create();
    })
      ->before($this->expectAccessTokens())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter());

    $cp->get('/signup/{signup_token}', function (Request $request, Token $token, string $signup_token) {
      return (new Actions\Signup($this->app, $request, $token))->confirm($signup_token);
    })
      ->before($this->expectAccessTokens())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter());

    $cp->get('/reset_password', function (Request $request, Token $token) {
      return (new Actions\PasswordReset($this->app, $request, $token))->new();
    })
      ->before($this->expectAccessTokens())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('new_password_reset');

    $cp->post('/reset_password', function (Request $request, Token $token) {
      return (new Actions\PasswordReset($this->app, $request, $token))->create();
    })
      ->before($this->expectAccessTokens())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter());

    $cp->get('/set_password/{password_token}', function (Request $request, Token $token, $password_token) {
      return (new Actions\PasswordReset($this->app, $request, $token))->newPassword($password_token);
    })
      ->before($this->expectAccessTokens())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter());

    $cp->post('/set_password', function (Request $request, Token $token) {
      return (new Actions\PasswordReset($this->app, $request, $token))->createPassword();
    })
      ->before($this->expectAccessTokens())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('set_password');

    $cp->get('/viewer', function (Request $request, Token $token) {
      return (new Actions\Viewer($this->app, $request, $token))->edit();
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('viewer');

    $cp->post('/viewer', function (Request $request, Token $token) {
      return (new Actions\Viewer($this->app, $request, $token))->update();
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('viewer_update');

    // TEMPORARY FILES

    $cp->post('/temporary_file', function (Request $request, Token $token) {
      return (new Actions\TemporaryFile($this->app, $request, $token))->create();
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter());

    $cp->delete('/temporary_file', function (Request $request, Token $token) {
      return 'OK';
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter());

    $cp->get('/temporary_file', function (Request $request, Token $token) {
      return (new Actions\TemporaryFile($this->app, $request, $token))->show();
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('temporary_file');

    // REPAIRS

    $cp->get('/repairs', function (Request $request, Token $token) {
      return (new Actions\Repairs($this->app, $request, $token))->index();
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('repairs');

    $cp->get('/repairs/create', function (Request $request, Token $token) {
      return (new Actions\Repairs($this->app, $request, $token))->new();
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('new_repair');

    $cp->post('/repairs/create', function (Request $request, Token $token) {
      return (new Actions\Repairs($this->app, $request, $token))->create();
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('create_repair');

    $cp->get('/repair/{id}', function (Request $request, Token $token, $id) {
      return (new Actions\Repairs($this->app, $request, $token))->show($id);
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->assert('id', $this->hashIdAssertion())
      ->bind('repair');

    // TICKETS

    $cp->get('/tickets', function (Request $request, Token $token) {
      return (new Actions\Tickets($this->app, $request, $token))->index();
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('tickets');

    $cp->get('/ticket/{id}', function (Request $request, Token $token, $id) {
      return (new Actions\Tickets($this->app, $request, $token))->show($id);
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->assert('id', $this->hashIdAssertion())
      ->bind('ticket');

    $cp->get('/tickets/create', function (Request $request, Token $token) {
      return (new Actions\Tickets($this->app, $request, $token))->new();
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('new_ticket');

    $cp->post('/tickets/create', function (Request $request, Token $token) {
      return (new Actions\Tickets($this->app, $request, $token))->create();
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('create_ticket');

    // MESSAGES

    $cp->get('/messages', function (Request $request, Token $token) {
      return (new Actions\Messages($this->app, $request, $token))->index();
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('messages');

    $cp->get('/message/{id}', function (Request $request, Token $token, $id) {
      return (new Actions\Messages($this->app, $request, $token))->show($id);
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->assert('id', $this->hashIdAssertion())
      ->bind('message');

    $cp->get('/messages/create', function (Request $request, Token $token) {
      return (new Actions\Messages($this->app, $request, $token))->new();
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('new_message');

    $cp->post('/messages/create', function (Request $request, Token $token) {
      return (new Actions\Messages($this->app, $request, $token))->create();
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('create_message');

    $cp->get('/message/{id}/create', function (Request $request, Token $token, $id) {
      return (new Actions\Messages($this->app, $request, $token))->newReply($id);
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->assert('id', $this->hashIdAssertion())
      ->bind('new_message_reply');

    $cp->post('/message/{id}/create', function (Request $request, Token $token, $id) {
      return (new Actions\Messages($this->app, $request, $token))->createReply($id);
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->assert('id', $this->hashIdAssertion())
      ->bind('create_message_reply');

    // MISC

    $cp->get('/', function (Request $request, Token $token) {
      return (new Actions\Dashboard($this->app, $request, $token))->index();
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('dashboard');

    $cp->get('/ac/vendor', function (Request $request, Token $token) {
      return (new Actions\Autocomplete($this->app, $request, $token))->vendor();
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())
      ->convert('token', $this->tokenConverter())
      ->bind('autocomplete_vendor');

    $cp->get('/ac/model', function (Request $request, Token $token) {
      return (new Actions\Autocomplete($this->app, $request, $token))->model();
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('autocomplete_model');

    return $cp;
  }
}