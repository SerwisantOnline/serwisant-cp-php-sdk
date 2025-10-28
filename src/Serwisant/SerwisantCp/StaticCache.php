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
      $tmp_dir = getenv('TMPDIR');
      if (trim($tmp_dir) === '') {
        $tmp_dir = sys_get_temp_dir();
      }
      $cache_dir = $tmp_dir . '/serwisant_nette_cache_' . md5(__FILE__);
      if (!file_exists($cache_dir)) {
        mkdir($cache_dir);
      }
      self::$instance = new NetteCache(new NetteFileStorage($cache_dir));
    }

    return self::$instance;
  }
}