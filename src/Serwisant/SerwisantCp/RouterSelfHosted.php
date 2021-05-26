<?php

namespace Serwisant\SerwisantCp;

use Silex;
use Symfony\Component\HttpFoundation\Request;
use Serwisant\SerwisantApi\Types\SchemaPublic\SecretTokenSubject;

class RouterSelfHosted extends Router
{
  const HASHID_ASSERTION = '\w{8,64}';

  public function createRoutes(Silex\Application $app)
  {
    $app->get('/heartbeat', function () {
      return 'OK';
    });
    $this->createCaRoutes($app);
    $this->createCpRoutes($app);
  }

  private function createCpRoutes(Silex\Application $app)
  {
    $app->get('/', function (Request $request) use ($app) {
      return (new Actions\Dashboard($app, $request))->index();
    })
      ->bind('dashboard');

    $app->get('/agreement/{id}', function (Request $request, $id) use ($app) {
      return (new Actions\Agreement($app, $request))->show($id);
    })
      ->assert('id', self::HASHID_ASSERTION)
      ->bind('agreement');

    $app->get('/login', function (Request $request) use ($app) {
      return (new Actions\Login($app, $request))->new();
    })
      ->bind('new_session');

    $app->post('/login/resolve', function (Request $request) use ($app) {
      return (new Actions\Login($app, $request))->resolveCredential();
    })
      ->before($this->expectJson())
      ->bind('new_session_resolve_login');

    $app->post('/login', function (Request $request) use ($app) {
      return (new Actions\Login($app, $request))->create();
    });

    $app->get('/logout', function (Request $request) use ($app) {
      return (new Actions\Login($app, $request))->destroy();
    })
      ->bind('destroy_session');

    $app->get('/signup', function (Request $request) use ($app) {
      return (new Actions\Signup($app, $request))->new();
    })->bind('new_signup');

    $app->post('/signup', function (Request $request) use ($app) {
      return (new Actions\Signup($app, $request))->create();
    });

    $app->get('/signup/{token}', function (Request $request, $token) use ($app) {
      return (new Actions\Signup($app, $request))->confirm($token);
    });

    $app->get('/reset_password', function (Request $request) use ($app) {
      return (new Actions\PasswordReset($app, $request))->new();
    })->bind('new_password_reset');

    $app->post('/reset_password', function (Request $request) use ($app) {
      return (new Actions\PasswordReset($app, $request))->create();
    });

    $app->get('/set_password/{token}', function (Request $request, $token) use ($app) {
      return (new Actions\PasswordReset($app, $request))->newPassword($token);
    });

    $app->post('/set_password', function (Request $request) use ($app) {
      return (new Actions\PasswordReset($app, $request))->createPassword();
    })
      ->bind('set_password');

    $app->get('/repairs', function (Request $request) use ($app) {
      return (new Actions\Repairs($app, $request))->index();
    })
      ->bind('repairs');

    $app->get('/repairs/create', function (Request $request) use ($app) {
      return (new Actions\Repairs($app, $request))->new();
    })
      ->bind('new_repair');

    $app->post('/repairs/create', function (Request $request) use ($app) {
      return (new Actions\Repairs($app, $request))->create();
    })
      ->bind('create_repair');

    $app->get('/repair/{id}', function (Request $request, $id) use ($app) {
      return (new Actions\Repairs($app, $request))->show($id);
    })
      ->assert('id', self::HASHID_ASSERTION)
      ->bind('repair');

    $app->get('/tickets', function (Request $request) use ($app) {
      return (new Actions\Tickets($app, $request))->index();
    })
      ->bind('tickets');

    $app->get('/messages', function (Request $request) use ($app) {
      return (new Actions\Messages($app, $request))->index();
    })
      ->bind('messages');
  }

  private function createCaRoutes(Silex\Application $app)
  {
    $app->get('/token/{token}', function (Request $request, $token) use ($app) {
      switch ($this->tokenSubject($app, $request, $token)) {
        case SecretTokenSubject::REPAIR:
          return (new Actions\RepairByToken($app, $request))->call($token);
        case  SecretTokenSubject::ONLINEPAYMENT:
          return (new Actions\PaymentByToken($app, $request))->call($token);
        default:
          $this->notFound();
          return null;
      }
    })
      ->assert('token', '\w+')
      ->bind('token');

    $app->post('/token/{token}/payment/pay', function (Request $request, $token) use ($app) {
      switch ($this->tokenSubject($app, $request, $token)) {
        case  SecretTokenSubject::ONLINEPAYMENT:
          return (new Actions\PaymentPayByToken($app, $request))->call($token);
        default:
          $this->notFound();
          return null;
      }
    })
      ->before($this->expectJson())
      ->assert('token', '\w+')
      ->bind('token_payment_pay');

    $app->get('/token/{token}/payment/pool', function (Request $request, $token) use ($app) {
      switch ($this->tokenSubject($app, $request, $token)) {
        case  SecretTokenSubject::ONLINEPAYMENT:
          return (new Actions\PaymentPoolTransaction($app, $request))->call($token);
        default:
          return $this->notFound();
      }
    })
      ->before($this->expectJson())
      ->assert('token', '\w+')
      ->bind('token_payment_pool');

    $app->post('/token/{token}/repair/accept', function (Request $request, $token) use ($app) {
      switch ($this->tokenSubject($app, $request, $token)) {
        case SecretTokenSubject::REPAIR:
          return (new Actions\RepairDecisionByToken($app, $request))->accept($token);
        default:
          return $this->notFound();
      }
    })
      ->before($this->expectJson())
      ->assert('token', '\w+')
      ->bind('token_repair_accept');

    $app->post('/token/{token}/repair/accept/{offer_id}', function (Request $request, $token, $offer_id) use ($app) {
      switch ($this->tokenSubject($app, $request, $token)) {
        case SecretTokenSubject::REPAIR:
          return (new Actions\RepairDecisionByToken($app, $request))->acceptOffer($token, $offer_id);
        default:
          return $this->notFound();
      }
    })
      ->before($this->expectJson())
      ->assert('token', '\w+')
      ->assert('offer_id', '\w+')
      ->bind('token_repair_accept_offer');

    $app->post('/token/{token}/repair/reject', function (Request $request, $token) use ($app) {
      switch ($this->tokenSubject($app, $request, $token)) {
        case SecretTokenSubject::REPAIR:
          return (new Actions\RepairDecisionByToken($app, $request))->reject($token);
        default:
          return $this->notFound();
      }
    })
      ->before($this->expectJson())
      ->assert('token', '\w+')
      ->bind('token_repair_reject');
  }

  private function tokenSubject(Silex\Application $app, Request $request, $secret_token)
  {
    $public_api = new Api($app, $request, $app['access_token_public']);
    $result = $public_api->publicQuery()->secretToken($secret_token);
    return $result->subjectType;
  }
}