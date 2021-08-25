<?php

namespace Serwisant\SerwisantCp;

use Serwisant\SerwisantApi\Types\SchemaPublic\SecretTokenSubject;
use Symfony\Component\HttpFoundation\Request;

class RoutesCa extends Routes
{
  protected function tokenAssertion(): string
  {
    return '[a-zA-Z0-9]{6,32}';
  }

  protected function tokenConverter(): callable
  {
    return function (string $token, Request $request) {
      $result = (new Api($this->app, $request, $this->app['access_token_public']))->publicQuery()->secretToken($token);
      $t = new Token($result->token, $result->subjectType);
      $this->app['token'] = $t;
      return $t;
    };
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
        default:
          throw new ExceptionNotFound(__CLASS__, __LINE__);
      }
    })
      ->before($this->expectPublicAccessToken())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('token');

    return $ca;
  }
}