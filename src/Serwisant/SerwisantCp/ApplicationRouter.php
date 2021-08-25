<?php

namespace Serwisant\SerwisantCp;

use Silex;

class ApplicationRouter implements Router
{

  public function createRoutes(Silex\Application $app)
  {
    $app->mount('/', (new RoutesCp($app))->getRoutes());
    $app->mount('/code', (new RoutesCa($app))->getRoutes());
  }
}