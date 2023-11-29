<?php

namespace Serwisant\SerwisantCp;

use Symfony\Component\HttpFoundation\Request;

class RoutesShared extends Routes
{
  use Traits\RoutesNoToken;

  public function getRoutes()
  {
    $controller = $this->app['controllers_factory'];

    $controller->get('/lang/{lang}', function (Request $request, Token $token, $lang) {
      return (new Actions\Lang($this->app, $request, $token))->update($lang);
    })
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->bind('lang_update');

    $controller->get('/agreement/{id}', function (Request $request, Token $token, $id) {
      return (new Actions\Agreement($this->app, $request, $token))->show($id);
    })
      ->before($this->expectAccessTokens())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->assert('id', $this->hashIdAssertion())
      ->bind('agreement');

    $controller->get('/statement/{id}', function (Request $request, Token $token, $id) {
      return (new Actions\Statement($this->app, $request, $token))->show($id);
    })
      ->before($this->expectAccessTokens())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->assert('id', $this->hashIdAssertion())
      ->bind('statement');

    return $controller;
  }
}