<?php

namespace Serwisant\SerwisantCp;

use Symfony\Component\HttpFoundation\Request;

class RoutesDocuments extends Routes
{
  use Traits\RoutesNoToken;

  public function getRoutes()
  {
    $documents = $this->app['controllers_factory'];

    $documents->get('/agreement/{id}', function (Request $request, Token $token, $id) {
      return (new Actions\Agreement($this->app, $request, $token))->show($id);
    })
      ->before($this->expectAccessTokens())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->assert('id', $this->hashIdAssertion())
      ->bind('agreement');

    $documents->get('/statement/{id}', function (Request $request, Token $token, $id) {
      return (new Actions\Statement($this->app, $request, $token))->show($id);
    })
      ->before($this->expectAccessTokens())
      ->assert('token', $this->tokenAssertion())->convert('token', $this->tokenConverter())
      ->assert('id', $this->hashIdAssertion())
      ->bind('statement');

    return $documents;
  }
}