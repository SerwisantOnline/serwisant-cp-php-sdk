<?php

namespace Serwisant\SerwisantCp;

use Silex;
use Symfony\Component\HttpFoundation\Request;

abstract class Router
{
  abstract public function createRoutes(Silex\Application $app);

  /**
   * @throws ExceptionNotFound
   */
  protected function notFound()
  {
    throw new ExceptionNotFound;
  }

  protected function expectAuthenticated(Silex\Application $app)
  {
    return function () use ($app) {
      if (false === $app['access_token_customer']->isAuthenticated()) {
        throw new ExceptionUnauthorized;
      }
    };
  }

  protected function expectJson()
  {
    return function (Request $request) {
      if (false === strpos($request->headers->get('Content-Type'), 'application/json')) {
        throw new ExceptionNotFound;
      }
    };
  }
}