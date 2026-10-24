<?php

namespace Serwisant\SerwisantCp;

use Serwisant\SerwisantApi;
use Symfony\Component\HttpFoundation\Request;
use Serwisant\Silex;

class Api extends \Serwisant\SerwisantApi\Api
{
  public function __construct(Silex\Application $app, $access_token, array $load_paths = [], int $debug = 0)
  {
    $this->setAccessToken($access_token)->setDebug($debug);

    // host może wstrzyknąć własnego klienta Guzzle, np. z MockHandler w testach - patrz README
    if (isset($app['api_http_client'])) {
      $this->setHttpClient($app['api_http_client']);
    }

    foreach ($app['gql_query_paths'] as $path) {
      $this->addLoadPath($path);
    }
    foreach ($load_paths as $path) {
      $this->addLoadPath($path);
    }
  }
}