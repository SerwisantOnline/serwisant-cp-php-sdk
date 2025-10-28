<?php

namespace Serwisant\SerwisantCp;

use PragmaRX\Countries\Package\Countries;
use PragmaRX\Countries\Package\Services\Config;
use PragmaRX\Countries\Package\Services\Cache;

class CountryDatabase
{
  protected static ?Countries $instance = null;

  public static function getInstance(): Countries
  {
    if (null === self::$instance) {
      // this is slow and require some optimizations
      self::$instance = new Countries();
    }
    return self::$instance;
  }
}