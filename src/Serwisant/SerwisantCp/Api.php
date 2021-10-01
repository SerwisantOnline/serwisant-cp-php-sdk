<?php

namespace Serwisant\SerwisantCp;

use Serwisant\SerwisantApi;
use Symfony\Component\HttpFoundation\Request;
use Silex;

class Api extends \Serwisant\SerwisantApi\Api
{
  public function __construct(Silex\Application $app, $access_token)
  {
    $this->setAccessToken($access_token);

    foreach ($app['gql_query_paths'] as $path) {
      $this->addLoadPath($path);
    }
  }
}