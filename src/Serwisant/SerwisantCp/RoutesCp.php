<?php

namespace Serwisant\SerwisantCp;

use Symfony\Component\HttpFoundation\Request;

class RoutesCp extends Routes
{
  use Traits\RoutesNoToken;

  public function getRoutes()
  {
    $cp = $this->app['controllers_factory'];

    $this->userRoutes($cp);
    $this->temporaryFilesRoutes($cp);
    $this->repairsRoutes($cp);
    $this->ticketsRoutes($cp);
    $this->devicesRoutes($cp);
    $this->calendarRoutes($cp);
    $this->messagesRoutes($cp);
    $this->miscRoutes($cp);

    return $cp;
  }

  /**
   * @param $cp
   * @return void
   */
  private function calendarRoutes($cp): void
  {
    $cp->get('/calendar/schedule_dates', function (Request $request, Token $token) {
      return (new Actions\Calendar($this->app, $request, $token))->scheduleDates();
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('calendar_schedule_dates');

    $cp->get('/calendar/ticket_dates', function (Request $request, Token $token) {
      return (new Actions\Calendar($this->app, $request, $token))->ticketDates();
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('calendar_ticket_dates');
  }

  /**
   * @param $cp
   * @return void
   * @throws ExceptionNotFound
   */
  private function devicesRoutes($cp): void
  {
    $cp->get('/devices', function (Request $request, Token $token) {
      return (new Actions\Devices($this->app, $request, $token))->index();
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('devices');

    $cp->get('/device/{id}', function (Request $request, Token $token, $id) {
      return (new Actions\Devices($this->app, $request, $token))->show($id);
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->assert('id', $this->hashIdAssertion())
      ->bind('device');
  }

  /**
   * @param $cp
   * @return void
   */
  private function messagesRoutes($cp): void
  {
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
  }

  /**
   * @param $cp
   * @return void
   */
  private function miscRoutes($cp): void
  {
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

    $cp->get('/lang/{lang}', function (Request $request, Token $token, $lang) {
      return (new Actions\Lang($this->app, $request, $token))->update($lang);
    })
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('lang_update');
  }

  /**
   * @param $cp
   * @return void
   * @throws ExceptionNotFound
   */
  private function ticketsRoutes($cp): void
  {
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

    $cp->get('/ticket/{id}/print', function (Request $request, Token $token, $id) {
      return (new Actions\Tickets($this->app, $request, $token))->print($id);
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->assert('id', $this->hashIdAssertion())
      ->bind('ticket_print');

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
  }

  /**
   * @param $cp
   * @return void
   * @throws Exception
   * @throws ExceptionNotFound
   */
  private function repairsRoutes($cp): void
  {
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

    $cp->get('/repair/{id}/print/{type}', function (Request $request, Token $token, $id, $type) {
      return (new Actions\Repairs($this->app, $request, $token))->print($id, $type);
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->assert('id', $this->hashIdAssertion())
      ->assert('type', '[a-z]{5,10}')
      ->bind('repair_print');

    $cp->post('/repair/{id}/accept', function (Request $request, Token $token, $id) {
      return (new Actions\RepairDecision($this->app, $request, $token))->accept($id);
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->assert('id', $this->hashIdAssertion())
      ->before($this->expectJson())
      ->bind('repair_accept');

    $cp->post('/repair/{id}/accept/{offer_id}', function (Request $request, Token $token, $id, $offer_id) {
      return (new Actions\RepairDecision($this->app, $request, $token))->acceptOffer($id, $offer_id);
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->assert('id', $this->hashIdAssertion())
      ->assert('offer_id', '\w+')
      ->before($this->expectJson())
      ->bind('repair_accept_offer');

    $cp->post('/repair/{id}/reject', function (Request $request, Token $token, $id) {
      return (new Actions\RepairDecision($this->app, $request, $token))->reject($id);
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->assert('id', $this->hashIdAssertion())
      ->before($this->expectJson())
      ->bind('repair_reject');
  }

  /**
   * @param $cp
   * @return void
   */
  private function temporaryFilesRoutes($cp): void
  {
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
  }

  /**
   * @param $cp
   * @return void
   */
  private function userRoutes($cp): void
  {
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

    $cp->get('/access_request/ask/{customer}', function (Request $request, Token $token, string $customer) {
      return (new Actions\AccessRequest($this->app, $request, $token))->ask($customer);
    })
      ->before($this->expectAccessTokens())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('new_access_request');

    $cp->post('/access_request/ask/{customer}', function (Request $request, Token $token, string $customer) {
      return (new Actions\AccessRequest($this->app, $request, $token))->sendLink($customer);
    })
      ->before($this->expectAccessTokens())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter());

    $cp->get('/access_request/create', function (Request $request, Token $token) {
      return (new Actions\AccessRequest($this->app, $request, $token))->new();
    })
      ->before($this->expectAccessTokens())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter());

    $cp->post('/access_request/create', function (Request $request, Token $token) {
      return (new Actions\AccessRequest($this->app, $request, $token))->create();
    })
      ->before($this->expectAccessTokens())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('create_access_request');

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

    $cp->get('/set_password', function (Request $request, Token $token) {
      return (new Actions\PasswordReset($this->app, $request, $token))->newPassword();
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

    $cp->get('/viewer/set_password', function (Request $request, Token $token) {
      return (new Actions\ViewerPassword($this->app, $request, $token))->edit();
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('viewer_password');

    $cp->post('/viewer/set_password', function (Request $request, Token $token) {
      return (new Actions\ViewerPassword($this->app, $request, $token))->update();
    })
      ->before($this->expectAccessTokens())->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter());
  }
}