<?php

namespace Serwisant\SerwisantCp;

use Silex;
use Symfony\Component\HttpFoundation\Request;
use Serwisant\SerwisantApi\Types\SchemaPublic\SecretTokenSubject;

class RouterSelfHosted extends Router
{
  public function createRoutes(Silex\Application $app)
  {
    $app->get('/heartbeat', function () {
      return 'OK';
    });

    $app->get('/cp_assets/{file}_{type}', function (Request $request, $file, $type) use ($app) {
      return (new Actions\Asset($app, $request))->call($file, $type);
    })
      ->assert('file', '^[a-zA-Z]+$')
      ->assert('type', '^[a-zA-Z]{2,3}$')
      ->bind('assets');

    $this->createCaRoutes($app);
    $this->createCpRoutes($app);
  }

  private function createCpRoutes(Silex\Application $app)
  {
    $app->get('/', function (Request $request) use ($app) {
      return (new Actions\Dashboard($app, $request))->dashboard();
    })->bind('dashboard');

    $app->get('/agreement/{id}', function (Request $request, $id) use ($app) {
      return (new Actions\Agreement($app, $request))->call($id);
    })->assert('id', '\w+')->bind('agreement');

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

  private function createCaRoutes(Silex\Application $app)
  {
    $app->get('/token/{token}', function (Request $request, $token) use ($app) {
      switch ($this->tokenSubject($app, $request, $token)) {
        case SecretTokenSubject::REPAIR:
          return (new Actions\RepairDetailsByToken($app, $request))->call($token);
        case  SecretTokenSubject::ONLINEPAYMENT:
          return (new Actions\PaymentByToken($app, $request))->call($token);
        default:
          $this->notFound();
      }
    })
      ->assert('token', '\w+')
      ->bind('token');

    $app->post('/token/{token}/payment/pay', function (Request $request, $token) use ($app) {
      switch ($this->tokenSubject($app, $request, $token)) {
        case  SecretTokenSubject::ONLINEPAYMENT:
          return (new Actions\PaymentPayByToken($app, $request))->setToken($token)->call();
        default:
          return $this->notFound();
      }
    })
      ->before($this->expectJson())
      ->assert('token', '\w+')
      ->bind('token_payment_pay');

    $app->get('/token/{token}/payment/pool', function (Request $request, $token) use ($app) {
      switch ($this->tokenSubject($app, $request, $token)) {
        case  SecretTokenSubject::ONLINEPAYMENT:
          return (new Actions\PaymentPoolTransaction($app, $request))->call();
        default:
          return $this->notFound();
      }
    })
      ->before($this->expectJson())
      ->assert('token', '\w+')
      ->bind('token_payment_pool');

    $app->get('/token/{token}/repair/accept', function (Request $request, $token) use ($app) {
      switch ($this->tokenSubject($app, $request, $token)) {
        case SecretTokenSubject::REPAIR:
          return (new Actions\RepairDecisionByToken($app, $request))->accept($token);
        default:
          return $this->notFound();
      }
    })
      ->assert('token', '\w+')
      ->bind('token_repair_accept');

    $app->get('/token/{token}/repair/accept/{offer_id}', function (Request $request, $token, $offer_id) use ($app) {
      switch ($this->tokenSubject($app, $request, $token)) {
        case SecretTokenSubject::REPAIR:
          return (new Actions\RepairDecisionByToken($app, $request))->acceptOffer($token, $offer_id);
        default:
          return $this->notFound();
      }
    })
      ->assert('token', '\w+')
      ->assert('offer_id', '\w+')
      ->bind('token_repair_accept_offer');

    $app->get('/token/{token}/repair/reject', function (Request $request, $token) use ($app) {
      switch ($this->tokenSubject($app, $request, $token)) {
        case SecretTokenSubject::REPAIR:
          return (new Actions\RepairDecisionByToken($app, $request))->reject($token);
        default:
          return $this->notFound();
      }
    })
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