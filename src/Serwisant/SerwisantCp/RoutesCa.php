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

  /**
   * @param $template_binding
   * @param $ca
   * @return mixed
   */
  private function routeRequirements($template_binding, $ca)
  {
    $ca->before($this->expectPublicAccessToken())->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter());
    if (trim($template_binding) != '') {
      $ca->bind($template_binding);
    }
    return $ca;
  }

  public function getRoutes()
  {
    $ca = $this->app['controllers_factory'];

    $this->repair($ca);
    $this->ticket($ca);
    $this->payments($ca);
    $this->temporaryFiles($ca);

    // confirm email of anonymous repair/ticket applicant to send him communication
    $this->routeRequirements('', $ca->get('/{token}/anonymous_applicant/{activation_token}', function (Request $request, Token $token, string $activation_token) {
      return (new Actions\AnonymousApplicantActivate($this->app, $request, $token))->call($activation_token);
    }));

    // rating action
    $this->routeRequirements('token_rate', $ca->post('/{token}/rate', function (Request $request, Token $token) {
      switch ($token->subjectType()) {
        case SecretTokenSubject::TICKET:
          return (new Actions\TicketByToken($this->app, $request, $token))->rate();
        case SecretTokenSubject::REPAIR:
          return (new Actions\RepairByToken($this->app, $request, $token))->rate();
        default:
          throw new ExceptionNotFound(__CLASS__, __LINE__);
      }
    }));

    // token page - can show: repair status, ticket status, payment page, service supplier starting page
    $this->routeRequirements('token', $ca->get('/{token}', function (Request $request, Token $token) {
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
    }));

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
    $this->routeRequirements('', $ca->post('/{token}/temporary_file', function (Request $request, Token $token) {
      return (new Actions\TemporaryFilePublic($this->app, $request, $token))->create();
    }));

    $this->routeRequirements('', $ca->delete('/{token}/temporary_file', function (Request $request, Token $token) {
      return 'OK';
    }));

    $this->routeRequirements('token_temporary_file', $ca->get('/{token}/temporary_file', function (Request $request, Token $token) {
      return (new Actions\TemporaryFilePublic($this->app, $request, $token))->show();
    }));
  }

  /**
   * @param $ca
   * @return void
   * @throws ExceptionNotFound
   */
  private function ticket($ca): void
  {
    // ticket anonymous submit
    $this->routeRequirements('token_new_ticket', $ca->get('/{token}/ticket/create', function (Request $request, Token $token) {
      if ($token->subjectType() === SecretTokenSubject::LICENCE) {
        return (new Actions\TicketsPublic($this->app, $request, $token))->new();
      }
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    }));

    $this->routeRequirements('token_create_ticket', $ca->post('/{token}/ticket/create', function (Request $request, Token $token) {
      if ($token->subjectType() === SecretTokenSubject::LICENCE) {
        return (new Actions\TicketsPublic($this->app, $request, $token))->create($request->cookies->get(self::DEVICE_COOKIE_NAME));
      }
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    }));
  }

  /**
   * @param $ca
   * @return void
   * @throws ExceptionNotFound
   */
  private function payments($ca): void
  {
    $this->routeRequirements('token_payment_pay', $ca->post('/{token}/payment/pay', function (Request $request, Token $token) {
      switch ($token->subjectType()) {
        case  SecretTokenSubject::ONLINEPAYMENT:
          return (new Actions\PaymentPayByToken($this->app, $request, $token))->call();
        default:
          throw new ExceptionNotFound(__CLASS__, __LINE__);
      }
    }))->before($this->expectJson());

    $this->routeRequirements('token_payment_pool', $ca->get('/{token}/payment/pool', function (Request $request, Token $token) {
      switch ($token->subjectType()) {
        case  SecretTokenSubject::ONLINEPAYMENT:
          return (new Actions\PaymentPoolTransaction($this->app, $request, $token))->call();
        default:
          throw new ExceptionNotFound(__CLASS__, __LINE__);
      }
    }))->before($this->expectJson());
  }

  /**
   * @param $ca
   * @return void
   * @throws ExceptionNotFound
   */
  private function repair($ca): void
  {
    $this->routeRequirements('token_new_repair', $ca->get('/{token}/repair/create', function (Request $request, Token $token) {
      if ($token->subjectType() === SecretTokenSubject::LICENCE) {
        return (new Actions\RepairsPublic($this->app, $request, $token))->new();
      }
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    }));

    $this->routeRequirements('token_create_repair', $ca->post('/{token}/repair/create', function (Request $request, Token $token) {
      if ($token->subjectType() === SecretTokenSubject::LICENCE) {
        return (new Actions\RepairsPublic($this->app, $request, $token))->create($request->cookies->get(self::DEVICE_COOKIE_NAME));
      }
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    }));

    $this->routeRequirements('token_repair_submit_prompt', $ca->get('/{token}/repair/submit_prompt', function (Request $request, Token $token) {
      if ($token->subjectType() === SecretTokenSubject::LICENCE) {
        return (new Actions\RepairsPublic($this->app, $request, $token))->submitPrompt();
      }
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    }));

    $this->routeRequirements('token_repair_accept', $ca->post('/{token}/repair/accept', function (Request $request, Token $token) {
      switch ($token->subjectType()) {
        case SecretTokenSubject::REPAIR:
          return (new Actions\RepairDecisionByToken($this->app, $request, $token))->accept();
        default:
          throw new ExceptionNotFound(__CLASS__, __LINE__);
      }
    }));

    $this->routeRequirements('token_repair_accept_offer', $ca->post('/{token}/repair/accept/{offer_id}', function (Request $request, Token $token, $offer_id) {
      switch ($token->subjectType()) {
        case SecretTokenSubject::REPAIR:
          return (new Actions\RepairDecisionByToken($this->app, $request, $token))->acceptOffer($offer_id);
        default:
          throw new ExceptionNotFound(__CLASS__, __LINE__);
      }
    }))->before($this->expectJson())->assert('offer_id', '\w+');

    $this->routeRequirements('token_repair_reject', $ca->post('/{token}/repair/reject', function (Request $request, Token $token) {
      switch ($token->subjectType()) {
        case SecretTokenSubject::REPAIR:
          return (new Actions\RepairDecisionByToken($this->app, $request, $token))->reject();
        default:
          throw new ExceptionNotFound(__CLASS__, __LINE__);
      }
    }))->before($this->expectJson());
  }
}