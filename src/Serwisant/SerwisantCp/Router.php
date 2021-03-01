<?php

namespace Serwisant\SerwisantCp;

use Silex;

interface Router
{
  public function createRoutes(Silex\Application $app);
}