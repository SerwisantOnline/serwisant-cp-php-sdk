<?php

namespace Serwisant\SerwisantCp\Traits;

use Serwisant\SerwisantCp\Token;
use Symfony\Component\HttpFoundation\Request;

trait RoutesNoToken
{
  protected function tokenConverter(): callable
  {
    return function ($token, Request $request) {
      return new Token();
    };
  }

  protected function tokenAssertion(): string
  {
    return '[a-zA-Z0-9]{4,32}';
  }
}
