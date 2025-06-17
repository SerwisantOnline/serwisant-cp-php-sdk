<?php

namespace Serwisant\SerwisantCp;

use Serwisant\SerwisantApi\Types\SchemaPublic\SecretTokenSubject;
use Symfony\Component\HttpFoundation\Request;

class RoutesCa extends Routes
{
  private Api $api;

  /**
   * @return Api
   */
  protected function api(): Api
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

    $this->repair($ca);
    $this->ticket($ca);
    $this->payments($ca);
    $this->temporaryFiles($ca);

    // token page - can show: repair status, ticket status, payment page, service supplier starting page

    $ca->get('/{token}', function (Request $request, Token $token) {
      switch ($token->subjectType()) {
        case SecretTokenSubject::TICKET:
          return (new Actions\TicketByToken($this->app, $request, $token))->call();
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
      ->assert('token', $this->tokenAssertion())
      ->convert('token', $this->tokenConverter())
      ->bind('token');

    // rating action

    $ca->post('/{token}/rate', function (Request $request, Token $token) {
      switch ($token->subjectType()) {
        case SecretTokenSubject::TICKET:
          return (new Actions\TicketByToken($this->app, $request, $token))->rate();
        case SecretTokenSubject::REPAIR:
          return (new Actions\RepairByToken($this->app, $request, $token))->rate();
        default:
          throw new ExceptionNotFound(__CLASS__, __LINE__);
      }
    })
      ->before($this->expectPublicAccessToken())
      ->assert('token', $this->tokenAssertion())
      ->convert('token', $this->tokenConverter())
      ->bind('token_rate');

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

  /**
   * @param $ca
   * @return void
   */
  private function temporaryFiles($ca): void
  {
    // temporary files - required for tickets
    $ca->post('/{token}/temporary_file', function (Request $request, Token $token) {
      return (new Actions\TemporaryFilePublic($this->app, $request, $token))->create();
    })
      ->before($this->expectPublicAccessToken())
      ->assert('token', $this->tokenAssertion())
      ->convert('token', $this->tokenConverter());

    $ca->delete('/{token}/temporary_file', function (Request $request, Token $token) {
      return 'OK';
    })
      ->before($this->expectPublicAccessToken())
      ->assert('token', $this->tokenAssertion())
      ->convert('token', $this->tokenConverter());

    $ca->get('/{token}/temporary_file', function (Request $request, Token $token) {
      return (new Actions\TemporaryFilePublic($this->app, $request, $token))->show();
    })
      ->before($this->expectPublicAccessToken())
      ->assert('token', $this->tokenAssertion())
      ->convert('token', $this->tokenConverter())
      ->bind('token_temporary_file');
  }

  /**
   * @param $ca
   * @return void
   * @throws ExceptionNotFound
   */
  private function ticket($ca): void
  {
    // ticket anonymous submit

    $ca->get('/{token}/ticket/create', function (Request $request, Token $token) {
      if ($token->subjectType() === SecretTokenSubject::LICENCE) {
        return (new Actions\TicketsPublic($this->app, $request, $token))->new();
      }
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    })
      ->before($this->expectPublicAccessToken())
      ->assert('token', $this->tokenAssertion())
      ->convert('token', $this->tokenConverter())
      ->bind('token_new_ticket');

    $ca->post('/{token}/ticket/create', function (Request $request, Token $token) {
      if ($token->subjectType() === SecretTokenSubject::LICENCE) {
        return (new Actions\TicketsPublic($this->app, $request, $token))->create($request->cookies->get(self::DEVICE_COOKIE_NAME));
      }
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    })
      ->before($this->expectPublicAccessToken())
      ->assert('token', $this->tokenAssertion())
      ->convert('token', $this->tokenConverter())
      ->bind('token_create_ticket');
  }

  /**
   * @param $ca
   * @return void
   * @throws ExceptionNotFound
   */
  private function payments($ca): void
  {
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
  }

  /**
   * @param $ca
   * @return void
   * @throws ExceptionNotFound
   */
  private function repair($ca): void
  {
    // repair anonymous submit

    $ca->get('/{token}/repair/create', function (Request $request, Token $token) {
      if ($token->subjectType() === SecretTokenSubject::LICENCE) {
        return (new Actions\RepairsPublic($this->app, $request, $token))->new();
      }
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    })
      ->before($this->expectPublicAccessToken())
      ->assert('token', $this->tokenAssertion())
      ->convert('token', $this->tokenConverter())
      ->bind('token_new_repair');

    $ca->post('/{token}/repair/create', function (Request $request, Token $token) {
      if ($token->subjectType() === SecretTokenSubject::LICENCE) {
        return (new Actions\RepairsPublic($this->app, $request, $token))->create($request->cookies->get(self::DEVICE_COOKIE_NAME));
      }
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    })
      ->before($this->expectPublicAccessToken())
      ->assert('token', $this->tokenAssertion())
      ->convert('token', $this->tokenConverter())
      ->bind('token_create_repair');

    $ca->get('/{token}/repair/submit_prompt', function (Request $request, Token $token) {
      if ($token->subjectType() === SecretTokenSubject::LICENCE) {
        return (new Actions\RepairsPublic($this->app, $request, $token))->submitPrompt();
      }
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    })
      ->before($this->expectPublicAccessToken())
      ->assert('token', $this->tokenAssertion())
      ->convert('token', $this->tokenConverter())
      ->bind('token_repair_submit_prompt');

    // repair (by its token)

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
  }
}