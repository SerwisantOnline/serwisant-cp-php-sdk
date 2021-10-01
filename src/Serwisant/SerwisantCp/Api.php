<?php

namespace Serwisant\SerwisantCp;

use Serwisant\SerwisantApi;
use Symfony\Component\HttpFoundation\Request;
use Silex;

class Api extends \Serwisant\SerwisantApi\Api
{
  public function __construct(Silex\Application $app, Request $request, $access_token)
  {
    $this
      ->setUpFromRequest($request)
      ->setAccessToken($access_token);

    foreach ($app['gql_query_paths'] as $path) {
      $this->addLoadPath($path);
    }
  }
}