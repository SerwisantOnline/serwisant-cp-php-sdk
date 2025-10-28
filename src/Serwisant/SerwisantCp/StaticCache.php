<?php

namespace Serwisant\SerwisantCp;

use Nette\Caching\Cache as NetteCache;
use Nette\Caching\Storages\FileStorage as NetteFileStorage;

class StaticCache
{
  protected static ?NetteCache $instance = null;

  public static function getInstance(): NetteCache
  {
    if (null === self::$instance) {
      $cache_dir = getenv('TMPDIR') . '/serwisant_nette_cache_' . md5(__FILE__);
      if (!file_exists($cache_dir)) {
        mkdir($cache_dir);
      }
      self::$instance = new NetteCache(new NetteFileStorage($cache_dir));
    }

    return self::$instance;
  }
}