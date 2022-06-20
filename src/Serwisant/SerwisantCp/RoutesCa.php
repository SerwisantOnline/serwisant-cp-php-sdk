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