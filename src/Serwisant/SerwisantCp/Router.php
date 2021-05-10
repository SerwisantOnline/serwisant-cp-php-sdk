<?php

namespace Serwisant\SerwisantCp;

use Silex;
use Symfony\Component\HttpFoundation\Request;

abstract class Router
{
  abstract public function createRoutes(Silex\Application $app);

  protected function notFound()
  {
    throw new ExceptionNotFound;
  }

  protected function expectJson()
  {
    return function (Request $request) {
      if (false === strpos($request->headers->get('Content-Type'), 'application/json')) {
        throw new ExceptionNotFound('Not a JSON request');
      }
    };
  }
}