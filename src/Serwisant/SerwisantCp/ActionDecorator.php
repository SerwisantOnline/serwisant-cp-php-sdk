<?php

namespace Serwisant\SerwisantCp;

use Silex;

interface ActionDecorator
{
  public function getLayoutVars($template): array;

  public function getLayoutName($template);

  public function getListLimit();

  public function getTwigExtension(Silex\Application $app);
}