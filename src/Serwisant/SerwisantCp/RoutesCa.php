<?php

namespace Serwisant\SerwisantCp;

use Serwisant\SerwisantApi\Types\SchemaPublic\SecretTokenSubject;
use Symfony\Component\HttpFoundation\Request;

class RoutesCa extends Routes
{
  private $api;

  protected function api()
  {
    if (is_null($this->api) && isset($this->app['access_token_public'])) {
      $this->api = new Api($this->app, $this->app['access_token_public'], [$this->app['base_dir'] . '/queries/public']);
    }
    return $this->api;
  }

  protected function tokenConverter(): callable
  {
    return function (string $token, Request $request) {
      if ($this->api()) {
        $result = $this->api()->publicQuery()->secretToken($token);
        $t = new Token($result->token, $result->subjectType);
        $this->app['token'] = $t;
        return $t;
      } else {
        throw new ExceptionNotFound(__CLASS__, __LINE__);
      }
    };
  }

  protected function tokenAssertion(): string
  {
    return '[a-zA-Z0-9]{4,32}';
  }

  public function getRoutes()
  {
    $ca = $this->app['controllers_factory'];

    // tickets

    $ca->get('/{token}/tickets/create', function (Request $request, Token $token) {
      if ($token->subjectType() === SecretTokenSubject::LICENCE) {
        return (new Actions\TicketsPublic($this->app, $request, $token))->new();
      }
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    })
      ->before($this->expectPublicAccessToken())
      ->assert('token', $this->tokenAssertion())
      ->convert('token', $this->tokenConverter())
      ->bind('token_new_ticket');

    $ca->post('/{token}/tickets/create', function (Request $request, Token $token) {
      if ($token->subjectType() === SecretTokenSubject::LICENCE) {
        return (new Actions\TicketsPublic($this->app, $request, $token))->create($request->cookies->get(self::DEVICE_COOKIE_NAME));
      }
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    })
      ->before($this->expectPublicAccessToken())
      ->assert('token', $this->tokenAssertion())
      ->convert('token', $this->tokenConverter())
      ->bind('token_create_ticket');

    // payments

    $ca->post('/{token}/payment/pay', function (Request $request, Token $token) {
      switch ($token->subjectType()) {
        case  SecretTokenSubject::ONLINEPAYMENT:
          return (new Actions\PaymentPayByToken($this->app, $request, $token))->call();
        default:
          throw new ExceptionNotFound(__CLASS__, __LINE__);
      }
    })
      ->before($this->expectPublicAccessToken())
      ->before($this->expectJson())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('token_payment_pay');

    $ca->get('/{token}/payment/pool', function (Request $request, Token $token) {
      switch ($token->subjectType()) {
        case  SecretTokenSubject::ONLINEPAYMENT:
          return (new Actions\PaymentPoolTransaction($this->app, $request, $token))->call();
        default:
          throw new ExceptionNotFound(__CLASS__, __LINE__);
      }
    })
      ->before($this->expectPublicAccessToken())
      ->before($this->expectJson())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('token_payment_pool');

    // repair

    $ca->post('/{token}/repair/accept', function (Request $request, Token $token) {
      switch ($token->subjectType()) {
        case SecretTokenSubject::REPAIR:
          return (new Actions\RepairDecisionByToken($this->app, $request, $token))->accept();
        default:
          throw new ExceptionNotFound(__CLASS__, __LINE__);
      }
    })
      ->before($this->expectPublicAccessToken())
      ->before($this->expectJson())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('token_repair_accept');

    $ca->post('/{token}/repair/accept/{offer_id}', function (Request $request, Token $token, $offer_id) {
      switch ($token->subjectType()) {
        case SecretTokenSubject::REPAIR:
          return (new Actions\RepairDecisionByToken($this->app, $request, $token))->acceptOffer($offer_id);
        default:
          throw new ExceptionNotFound(__CLASS__, __LINE__);
      }
    })
      ->before($this->expectPublicAccessToken())
      ->before($this->expectJson())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->assert('offer_id', '\w+')
      ->bind('token_repair_accept_offer');

    $ca->post('/{token}/repair/reject', function (Request $request, Token $token) {
      switch ($token->subjectType()) {
        case SecretTokenSubject::REPAIR:
          return (new Actions\RepairDecisionByToken($this->app, $request, $token))->reject();
        default:
          throw new ExceptionNotFound(__CLASS__, __LINE__);
      }
    })
      ->before($this->expectPublicAccessToken())
      ->before($this->expectJson())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('token_repair_reject');

    // token page - can show: repair status, payment page, service supplier starting page

    $ca->get('/{token}', function (Request $request, Token $token) {
      switch ($token->subjectType()) {
        case SecretTokenSubject::REPAIR:
          return (new Actions\RepairByToken($this->app, $request, $token))->call();
        case  SecretTokenSubject::ONLINEPAYMENT:
          return (new Actions\PaymentByToken($this->app, $request, $token))->call();
        case SecretTokenSubject::LICENCE:
          return (new Actions\SubscriberByToken($this->app, $request, $token))->call();
        default:
          throw new ExceptionNotFound(__CLASS__, __LINE__);
      }
    })
      ->before($this->expectPublicAccessToken())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('token');

    // this works only on self-hosted - shows main page (service supplier starting page) based on pre-defined key-secret

    $ca->get('/', function (Request $request) {
      if ($this->api()) {
        $result = $this->api()->publicQuery()->configuration();
        $t = new Token($result->panelToken, SecretTokenSubject::LICENCE);
        return (new Actions\SubscriberByToken($this->app, $request, $t))->call();
      } else {
        throw new ExceptionNotFound(__CLASS__, __LINE__);
      }
    });

    return $ca;
  }
}