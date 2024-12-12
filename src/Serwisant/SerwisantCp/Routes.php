<?php

namespace Serwisant\SerwisantCp;

use Silex;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class Routes
{
  const DEVICE_COOKIE_NAME = 'naprawiam_device_uid';

  protected $app;

  public function __construct(Silex\Application $app)
  {
    $this->app = $app;
  }

  abstract public function getRoutes();

  protected function hashIdAssertion(): string
  {
    return '[a-zA-Z0-9]{8,64}';
  }

  protected function expectPublicAccessToken()
  {
    return function () {
      if (!isset($this->app['access_token_public'])) {
        throw new ExceptionNotFound(__CLASS__, __LINE__);
      }
    };
  }

  protected function expectAccessTokens()
  {
    return function () {
      if (!(isset($this->app['access_token_public']) && isset($this->app['access_token_customer']))) {
        throw new ExceptionNotFound(__CLASS__, __LINE__);
      }
    };
  }

  protected function expectAuthenticated()
  {
    return function () {
      if (false === $this->app['access_token_customer']->isAuthenticated()) {
        throw new ExceptionUnauthorized;
      }
    };
  }

  protected function expectJson()
  {
    return function (Request $request) {
      if (false === strpos($request->headers->get('Content-Type'), 'application/json')) {
        throw new ExceptionNotFound(__CLASS__, __LINE__);
      }
    };
  }

  protected function sendDeviceCookie()
  {
    return function (Request $request, Response $response) {

    };
  }
}