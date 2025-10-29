<?php

namespace Serwisant\SerwisantCp;

use Nette\Caching\Cache as NetteCache;
use Nette\Caching\Storages\FileStorage as NetteFileStorage;
use Nette\Caching\Storages\DevNullStorage as NetteDevNullStorage;

class StaticCache
{
  protected static ?NetteCache $instance = null;

  public static function getInstance(): NetteCache
  {
    if (null === self::$instance) {
      try {
        if (is_writable(self::getTmpDir())) {
          $cache_dir = self::getTmpDir() . DIRECTORY_SEPARATOR . 'serwisant_nette_cache_' . md5(__FILE__);
          if (!file_exists($cache_dir)) {
            mkdir($cache_dir);
          }
          $storage = new NetteFileStorage($cache_dir);
        } else {
          $storage = new NetteDevNullStorage();
        }
      } catch (Exception $e) {
        $storage = new NetteDevNullStorage();
      }
      self::$instance = new NetteCache($storage);
    }
    return self::$instance;
  }

  public static function getTmpDir(): string
  {
    $tmp_dir = getenv('TMPDIR');
    if (trim($tmp_dir) === '') {
      $tmp_dir = sys_get_temp_dir();
    }
    if (trim($tmp_dir) === '') {
      throw new Exception('Unable to determine tmp dir - pass path to directory using TMPDIR env variable');
    }
    return $tmp_dir;
  }
}