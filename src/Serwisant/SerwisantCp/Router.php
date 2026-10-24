<?php

namespace Serwisant\SerwisantCp;

use Serwisant\Silex;

interface Router
{
  public function createRoutes(Silex\Application $app);
}