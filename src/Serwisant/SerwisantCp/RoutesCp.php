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

  private function routeRequirements($template_binding, $cp)
  {
    return $cp
      ->before($this->expectAccessTokens())
      ->before($this->expectAuthenticated())
      ->assert('token', $this->tokenAssertion())
      ->convert('token', $this->tokenConverter())
      ->bind($template_binding);
  }

  private function routeIdRequirements($template_binding, $cp)
  {
    return $this->routeRequirements($template_binding, $cp)->assert('id', $this->hashIdAssertion());
  }

  private function routeNoAuthRequirements($template_binding, $cp)
  {
    return $cp
      ->before($this->expectAccessTokens())
      ->assert('token', $this->tokenAssertion())
      ->convert('token', $this->tokenConverter())
      ->bind($template_binding);
  }

  /**
   * @param $cp
   * @return void
   */
  private function calendarRoutes($cp): void
  {
    $this->routeRequirements('calendar_schedule_dates', $cp->get('/calendar/schedule_dates', function (Request $request, Token $token) {
      return (new Actions\Calendar($this->app, $request, $token))->scheduleDates();
    }));

    $this->routeRequirements('calendar_ticket_dates', $cp->get('/calendar/ticket_dates', function (Request $request, Token $token) {
      return (new Actions\Calendar($this->app, $request, $token))->ticketDates();
    }));
  }

  /**
   * @param $cp
   * @return void
   * @throws ExceptionNotFound
   */
  private function devicesRoutes($cp): void
  {
    $this->routeRequirements('devices', $cp->get('/devices', function (Request $request, Token $token) {
      return (new Actions\Devices($this->app, $request, $token))->index();
    }));

    $this->routeIdRequirements('device', $cp->get('/device/{id}', function (Request $request, Token $token, $id) {
      return (new Actions\Devices($this->app, $request, $token))->show($id);
    }));

    $this->routeRequirements('new_device', $cp->get('/devices/create', function (Request $request, Token $token) {
      return (new Actions\Devices($this->app, $request, $token))->new();
    }));

    $this->routeRequirements('create_device', $cp->post('/devices/create', function (Request $request, Token $token) {
      return (new Actions\Devices($this->app, $request, $token))->create();
    }));
  }

  /**
   * @param $cp
   * @return void
   */
  private function messagesRoutes($cp): void
  {
    $this->routeRequirements('messages', $cp->get('/messages', function (Request $request, Token $token) {
      return (new Actions\Messages($this->app, $request, $token))->index();
    }));

    $this->routeIdRequirements('message', $cp->get('/message/{id}', function (Request $request, Token $token, $id) {
      return (new Actions\Messages($this->app, $request, $token))->show($id);
    }));

    $this->routeRequirements('new_message', $cp->get('/messages/create', function (Request $request, Token $token) {
      return (new Actions\Messages($this->app, $request, $token))->new();
    }));

    $this->routeRequirements('create_message', $cp->post('/messages/create', function (Request $request, Token $token) {
      return (new Actions\Messages($this->app, $request, $token))->create();
    }));

    $this->routeIdRequirements('new_message_reply', $cp->get('/message/{id}/create', function (Request $request, Token $token, $id) {
      return (new Actions\Messages($this->app, $request, $token))->newReply($id);
    }));

    $this->routeIdRequirements('create_message_reply', $cp->post('/message/{id}/create', function (Request $request, Token $token, $id) {
      return (new Actions\Messages($this->app, $request, $token))->createReply($id);
    }));
  }

  /**
   * @param $cp
   * @return void
   */
  private function miscRoutes($cp): void
  {
    $this->routeRequirements('contact', $cp->get('/contact', function (Request $request, Token $token) {
      return (new Actions\Contact($this->app, $request, $token))->show();
    }));

    $this->routeRequirements('dashboard', $cp->get('/', function (Request $request, Token $token) {
      return (new Actions\Dashboard($this->app, $request, $token))->index();
    }));

    $this->routeRequirements('autocomplete_vendor', $cp->get('/ac/vendor', function (Request $request, Token $token) {
      return (new Actions\Autocomplete($this->app, $request, $token))->vendor();
    }));

    $this->routeRequirements('autocomplete_model', $cp->get('/ac/model', function (Request $request, Token $token) {
      return (new Actions\Autocomplete($this->app, $request, $token))->model();
    }));
  }

  /**
   * @param $cp
   * @return void
   * @throws ExceptionNotFound
   */
  private function ticketsRoutes($cp): void
  {
    $this->routeRequirements('tickets', $cp->get('/tickets', function (Request $request, Token $token) {
      return (new Actions\Tickets($this->app, $request, $token))->index();
    }));

    $this->routeIdRequirements('ticket', $cp->get('/ticket/{id}', function (Request $request, Token $token, $id) {
      return (new Actions\Tickets($this->app, $request, $token))->show($id);
    }));

    $this->routeIdRequirements('ticket_print', $cp->get('/ticket/{id}/print', function (Request $request, Token $token, $id) {
      return (new Actions\Tickets($this->app, $request, $token))->print($id);
    }));

    $this->routeRequirements('new_ticket', $cp->get('/tickets/create', function (Request $request, Token $token) {
      return (new Actions\Tickets($this->app, $request, $token))->new();
    }));

    $this->routeRequirements('create_ticket', $cp->post('/tickets/create', function (Request $request, Token $token) {
      return (new Actions\Tickets($this->app, $request, $token))->create();
    }));

    $this->routeIdRequirements('ticket_rate', $cp->post('/tickets/{id}/rate', function (Request $request, Token $token, $id) {
      return (new Actions\Tickets($this->app, $request, $token))->rate($id);
    }));
  }

  /**
   * @param $cp
   * @return void
   * @throws Exception
   * @throws ExceptionNotFound
   */
  private function repairsRoutes($cp): void
  {
    $this->routeRequirements('repairs', $cp->get('/repairs', function (Request $request, Token $token) {
      return (new Actions\Repairs($this->app, $request, $token))->index();
    }));

    $this->routeRequirements('new_repair', $cp->get('/repairs/create', function (Request $request, Token $token) {
      return (new Actions\Repairs($this->app, $request, $token))->new();
    }));

    $this->routeRequirements('create_repair', $cp->post('/repairs/create', function (Request $request, Token $token) {
      return (new Actions\Repairs($this->app, $request, $token))->create();
    }));

    $this->routeIdRequirements('repair', $cp->get('/repair/{id}', function (Request $request, Token $token, $id) {
      return (new Actions\Repairs($this->app, $request, $token))->show($id);
    }));

    $this->routeIdRequirements('repair_print', $cp->get('/repair/{id}/print/{type}', function (Request $request, Token $token, $id, $type) {
      return (new Actions\Repairs($this->app, $request, $token))->print($id, $type);
    }))->assert('type', '[a-z]{5,10}');

    $this->routeIdRequirements('repair_rate', $cp->post('/repair/{id}/rate', function (Request $request, Token $token, $id) {
      return (new Actions\Repairs($this->app, $request, $token))->rate($id);
    }));

    $this->routeIdRequirements('repair_accept', $cp->post('/repair/{id}/accept', function (Request $request, Token $token, $id) {
      return (new Actions\RepairDecision($this->app, $request, $token))->accept($id);
    }))->before($this->expectJson());

    $this->routeIdRequirements('repair_accept_offer', $cp->post('/repair/{id}/accept/{offer_id}', function (Request $request, Token $token, $id, $offer_id) {
      return (new Actions\RepairDecision($this->app, $request, $token))->acceptOffer($id, $offer_id);
    }))->assert('offer_id', '\w+')->before($this->expectJson());

    $this->routeIdRequirements('repair_reject', $cp->post('/repair/{id}/reject', function (Request $request, Token $token, $id) {
      return (new Actions\RepairDecision($this->app, $request, $token))->reject($id);
    }))->before($this->expectJson());

    $this->routeRequirements('repair_prompt', $cp->get('/repairs/submit_prompt', function (Request $request, Token $token) {
      return (new Actions\Repairs($this->app, $request, $token))->submitPrompt();
    }));
  }

  /**
   * @param $cp
   * @return void
   */
  private function temporaryFilesRoutes($cp): void
  {
    $this->routeRequirements('temporary_file_create', $cp->post('/temporary_file', function (Request $request, Token $token) {
      return (new Actions\TemporaryFile($this->app, $request, $token))->create();
    }));

    $this->routeRequirements('temporary_file_delete', $cp->delete('/temporary_file', function (Request $request, Token $token) {
      return 'OK';
    }));

    $this->routeRequirements('temporary_file', $cp->get('/temporary_file', function (Request $request, Token $token) {
      return (new Actions\TemporaryFile($this->app, $request, $token))->show();
    }));
  }

  /**
   * @param $cp
   * @return void
   */
  private function userRoutes($cp): void
  {
    $this->routeNoAuthRequirements('new_session', $cp->get('/login', function (Request $request, Token $token) {
      return (new Actions\Login($this->app, $request, $token))->new();
    }));

    $this->routeNoAuthRequirements('new_session_resolve_login', $cp->post('/login/resolve', function (Request $request, Token $token) {
      return (new Actions\Login($this->app, $request, $token))->resolveCredential();
    }))->before($this->expectJson());

    $this->routeNoAuthRequirements('', $cp->post('/login', function (Request $request, Token $token) {
      return (new Actions\Login($this->app, $request, $token))->create();
    }));

    $this->routeRequirements('destroy_session', $cp->get('/logout', function (Request $request, Token $token) {
      return (new Actions\Login($this->app, $request, $token))->destroy();
    }));

    $this->routeNoAuthRequirements('new_signup', $cp->get('/signup', function (Request $request, Token $token) {
      return (new Actions\Signup($this->app, $request, $token))->new();
    }));

    $this->routeNoAuthRequirements('', $cp->post('/signup', function (Request $request, Token $token) {
      return (new Actions\Signup($this->app, $request, $token))->create();
    }));

    $this->routeNoAuthRequirements('new_access_request', $cp->get('/access_request/ask/{customer}', function (Request $request, Token $token, string $customer) {
      return (new Actions\AccessRequest($this->app, $request, $token))->ask($customer);
    }));

    $this->routeNoAuthRequirements('', $cp->post('/access_request/ask/{customer}', function (Request $request, Token $token, string $customer) {
      return (new Actions\AccessRequest($this->app, $request, $token))->sendLink($customer);
    }));

    $this->routeNoAuthRequirements('', $cp->get('/access_request/create', function (Request $request, Token $token) {
      return (new Actions\AccessRequest($this->app, $request, $token))->new();
    }));

    $this->routeNoAuthRequirements('create_access_request', $cp->post('/access_request/create', function (Request $request, Token $token) {
      return (new Actions\AccessRequest($this->app, $request, $token))->create();
    }));

    $this->routeNoAuthRequirements('', $cp->get('/signup/{signup_token}', function (Request $request, Token $token, string $signup_token) {
      return (new Actions\Signup($this->app, $request, $token))->confirm($signup_token);
    }));

    $this->routeNoAuthRequirements('new_password_reset', $cp->get('/reset_password', function (Request $request, Token $token) {
      return (new Actions\PasswordReset($this->app, $request, $token))->new();
    }));

    $this->routeNoAuthRequirements('', $cp->post('/reset_password', function (Request $request, Token $token) {
      return (new Actions\PasswordReset($this->app, $request, $token))->create();
    }));

    $this->routeNoAuthRequirements('', $cp->get('/set_password', function (Request $request, Token $token) {
      return (new Actions\PasswordReset($this->app, $request, $token))->newPassword();
    }));

    $this->routeNoAuthRequirements('set_password', $cp->post('/set_password', function (Request $request, Token $token) {
      return (new Actions\PasswordReset($this->app, $request, $token))->createPassword();
    }));

    $this->routeRequirements('viewer', $cp->get('/viewer', function (Request $request, Token $token) {
      return (new Actions\Viewer($this->app, $request, $token))->edit();
    }));

    $this->routeRequirements('viewer_update', $cp->post('/viewer', function (Request $request, Token $token) {
      return (new Actions\Viewer($this->app, $request, $token))->update();
    }));

    $this->routeRequirements('viewer_password', $cp->get('/viewer/set_password', function (Request $request, Token $token) {
      return (new Actions\ViewerPassword($this->app, $request, $token))->edit();
    }));

    $this->routeRequirements('viewer_password_update', $cp->post('/viewer/set_password', function (Request $request, Token $token) {
      return (new Actions\ViewerPassword($this->app, $request, $token))->update();
    }));
  }
}