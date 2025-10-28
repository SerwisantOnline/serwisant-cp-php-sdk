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
      $config = new Config([
        'cache' => [
          'enabled' => true,
          'service' => Cache\Service::class,
          'duration' => 180,
          'directory' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . '__pragmarx-countries-cache__',
        ],
        'hydrate' => [
          'before' => true,
          'after' => false,
          'elements' => [
            'flag' => true,
            'borders' => false,
            'cities' => false,
            'currencies' => false,
            'geometry' => false,
            'states' => false,
            'taxes' => false,
            'timezones' => false,
            'timezones_times' => false,
            'topology' => false,
          ],
        ],
        'maps' => [
          'lca3' => 'cca3',
          'currencies' => 'currency',
        ],
        'validation' => [
          'enabled' => true,
          'rules' => [
            'country' => 'name.common',
            'name' => 'name.common',
            'nameCommon' => 'name.common',
            'cca2',
            'cca2',
            'cca3',
            'ccn3',
            'cioc',
            'currencies' => 'ISO4217',
            'language_short' => 'ISO639_3',
          ],
        ],
      ]);
      self::$instance = new Countries($config, null, new CountryDatabaseHelper($config), null, null);
    }

    return self::$instance;
  }
}